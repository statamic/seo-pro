<?php

namespace Statamic\SeoPro\Llms;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\LockableFile;
use Illuminate\Support\Carbon;
use RuntimeException;
use Statamic\SeoPro\Events\LlmsTxtGenerated;
use Statamic\Sites\Site as SiteObject;
use Throwable;

class LlmsTxtGenerator
{
    public const MAX_BYTES = LlmsRenderer::MAX_BYTES;

    public function __construct(
        private Filesystem $files,
        private LlmsRenderer $renderer,
        private LlmsRenderCache $cache,
    ) {}

    public function generate(?LlmsDocument $document = null, string|SiteObject|null $site = null): array
    {
        $site = Llms::site($site);

        return $this->locked(fn () => $this->generateWhileLocked($document ?? Llms::get($site), $site));
    }

    public function sync(LlmsDocument $document, string|SiteObject|null $site = null): array
    {
        $site = Llms::site($site);

        return $this->locked(function () use ($document, $site) {
            $status = $this->status($site);

            if (! $document->enabled()) {
                return $status['managed']
                    ? $this->removeManagedWhileLocked($document, $site)
                    : $this->saveOnly($document, $site, $status);
            }

            return $status['managed']
                ? $this->generateWhileLocked($document, $site)
                : $this->saveOnly($document, $site, $status);
        });
    }

    public function status(string|SiteObject|null $site = null): array
    {
        $site = Llms::site($site);
        $path = $this->path($site);

        if (is_link($path)) {
            return $this->statusResult($path, exists: true, issue: 'symlink');
        }

        if (! $this->files->exists($path)) {
            return $this->statusResult($path);
        }

        if (! $this->files->isFile($path)) {
            return $this->statusResult($path, exists: true, issue: 'unreadable');
        }

        try {
            if ($this->files->size($path) > self::MAX_BYTES) {
                return $this->statusResult($path, exists: true, issue: 'too_large');
            }

            $contents = $this->read($path);
        } catch (Throwable) {
            return $this->statusResult($path, exists: true, issue: 'unreadable');
        }

        if ($contents === false) {
            return $this->statusResult($path, exists: true, issue: 'unreadable');
        }

        if (strlen($contents) > self::MAX_BYTES) {
            return $this->statusResult($path, exists: true, issue: 'too_large');
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            return $this->statusResult($path, exists: true, issue: 'invalid_utf8');
        }

        $generated = Llms::generated($site) ?? [];
        $checksum = hash('sha256', $contents);
        $managed = is_string($generated['checksum'] ?? null)
            && hash_equals($generated['checksum'], $checksum);
        $outdated = false;

        if ($managed && Llms::get($site)->enabled()) {
            try {
                $outdated = hash('sha256', $this->renderer->render(Llms::get($site), $site)) !== $checksum;
            } catch (Throwable) {
                $outdated = true;
            }
        }

        return $this->statusResult(
            path: $path,
            exists: true,
            managed: $managed,
            contents: $contents,
            timestamp: $managed ? ($generated['timestamp'] ?? null) : null,
            issue: null,
            outdated: $outdated,
        );
    }

    public function path(string|SiteObject|null $site = null): string
    {
        return public_path(Llms::relativePath($site));
    }

    private function generateWhileLocked(LlmsDocument $document, SiteObject $site): array
    {
        if (! $document->enabled()) {
            throw new RuntimeException("llms.txt is not enabled for site [{$site->handle()}].");
        }

        $path = $this->path($site);
        $status = $this->status($site);

        if ($status['exists'] && ! $status['managed']) {
            throw new RuntimeException("The existing llms.txt file at [{$path}] is not managed by SEO Pro and will not be overwritten.");
        }

        $contents = $this->renderer->render($document, $site);
        $checksum = hash('sha256', $contents);
        $generated = Llms::generated($site) ?? [];
        $existingGeneratedAt = $this->existingGeneratedAt($generated, $checksum);
        $fileMatches = $this->fileMatches($path, $contents, $checksum);
        $settingsMatch = Llms::get($site)->all() === $document->all();

        if ($fileMatches && $settingsMatch && $existingGeneratedAt) {
            return $this->result($path, $contents, $existingGeneratedAt, false, false, false);
        }

        if ($fileMatches) {
            $generatedAt = $existingGeneratedAt ?? now();
            $settingsSnapshot = Llms::settingsSnapshot();

            try {
                $this->saveGeneratedSettings($document, $site, $contents, $path, $generatedAt);
            } catch (Throwable $exception) {
                $this->rollback($exception, $settingsSnapshot);
            }

            $this->cache->forget($site);

            return $this->result($path, $contents, $generatedAt, false, true, false);
        }

        $settingsSnapshot = Llms::settingsSnapshot();
        $fileSnapshot = $this->snapshotFile(
            $path,
            expectedChecksum: $status['exists'] ? ($generated['checksum'] ?? null) : null,
            expectMissing: ! $status['exists'],
        );
        $generatedAt = now();

        try {
            $this->writeFile($path, $contents);
            $this->saveGeneratedSettings($document, $site, $contents, $path, $generatedAt);
        } catch (Throwable $exception) {
            $this->rollback($exception, $settingsSnapshot, $fileSnapshot);
        }

        $this->discardFileSnapshot($fileSnapshot);
        $this->cache->forget($site);
        LlmsTxtGenerated::dispatch($document, $site, $path, $generatedAt);

        return $this->result($path, $contents, $generatedAt, true, true, false);
    }

    private function removeManagedWhileLocked(LlmsDocument $document, SiteObject $site): array
    {
        $path = $this->path($site);
        $settingsSnapshot = Llms::settingsSnapshot();
        $fileSnapshot = $this->snapshotFile(
            $path,
            expectedChecksum: Llms::generated($site)['checksum'] ?? null,
        );

        try {
            if ($this->files->exists($path) && ! $this->files->delete($path)) {
                throw new RuntimeException("Unable to remove the managed llms.txt file at [{$path}].");
            }

            if (! Llms::saveWithoutGenerated($document, $site)) {
                throw new RuntimeException('Unable to save llms.txt settings.');
            }
        } catch (Throwable $exception) {
            $this->rollback($exception, $settingsSnapshot, $fileSnapshot);
        }

        $this->discardFileSnapshot($fileSnapshot);
        $this->cache->forget($site);

        return $this->result($path, '', now(), true, true, true);
    }

    private function saveOnly(LlmsDocument $document, SiteObject $site, array $status): array
    {
        $contents = $document->enabled() ? $this->renderer->render($document, $site) : '';
        $settingsChanged = Llms::get($site)->all() !== $document->all()
            || Llms::generated($site) !== null;

        if ($settingsChanged) {
            $settingsSnapshot = Llms::settingsSnapshot();

            try {
                if (! Llms::saveWithoutGenerated($document, $site)) {
                    throw new RuntimeException('Unable to save llms.txt settings.');
                }
            } catch (Throwable $exception) {
                $this->rollback($exception, $settingsSnapshot);
            }
        }

        $this->cache->forget($site);

        return $this->result(
            $status['path'],
            $contents,
            now(),
            false,
            $settingsChanged,
            false,
        );
    }

    private function locked(\Closure $callback): array
    {
        $lock = null;

        try {
            $lock = new LockableFile(storage_path('framework/cache/seo-pro/llms-txt.lock'), 'c+');
            $lock->getExclusiveLock();
        } catch (Throwable $exception) {
            $lock?->close();

            throw new RuntimeException('Unable to acquire the llms.txt generation lock. Another generation may already be in progress.', previous: $exception);
        }

        try {
            return $callback();
        } finally {
            $lock->close();
        }
    }

    private function writeFile(string $path, string $contents): void
    {
        try {
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->replace($path, $contents);
        } catch (Throwable $exception) {
            throw new RuntimeException("Unable to write llms.txt to [{$path}].", previous: $exception);
        }
    }

    private function saveGeneratedSettings(
        LlmsDocument $document,
        SiteObject $site,
        string $contents,
        string $path,
        Carbon $generatedAt,
    ): void {
        if (! Llms::saveGenerated($document, $site, $contents, $path, $generatedAt)) {
            throw new RuntimeException('Unable to save llms.txt settings.');
        }

        $settings = Llms::settingsSnapshot()['raw']['llms']['sites'][$site->handle()] ?? [];

        if (($settings['policy'] ?? null) !== $document->all()
            || ($settings['generated']['timestamp'] ?? null) !== $generatedAt->toIso8601String()
            || ($settings['generated']['checksum'] ?? null) !== hash('sha256', $contents)) {
            throw new RuntimeException('The generated llms.txt settings could not be verified after saving.');
        }
    }

    private function snapshotFile(
        string $path,
        ?string $expectedChecksum = null,
        bool $expectMissing = false,
    ): array {
        if (is_link($path)) {
            throw new RuntimeException("SEO Pro will not manage an llms.txt symbolic link at [{$path}].");
        }

        $target = realpath($path) ?: $path;
        $exists = $this->files->exists($target);

        if ($expectMissing && $exists) {
            throw new RuntimeException("An unmanaged llms.txt file appeared at [{$path}] during generation and will not be overwritten.");
        }

        if (! $exists) {
            if ($expectedChecksum !== null) {
                throw new RuntimeException("The managed llms.txt file at [{$path}] changed during generation.");
            }

            return ['exists' => false, 'path' => $target, 'backup' => null, 'mode' => null];
        }

        if (! $this->files->isFile($target)) {
            throw new RuntimeException("Unable to back up llms.txt at [{$target}].");
        }

        if ($expectedChecksum !== null) {
            $currentChecksum = $this->files->hash($target, 'sha256');

            if (! is_string($currentChecksum) || ! hash_equals($expectedChecksum, $currentChecksum)) {
                throw new RuntimeException("The managed llms.txt file at [{$path}] changed during generation and will not be overwritten.");
            }
        }

        $directory = dirname($target);
        $backup = @tempnam($directory, '.seo-pro-llms-');
        $backupDirectory = is_string($backup) ? realpath(dirname($backup)) : false;
        $targetDirectory = realpath($directory);

        if ($backup === false || $backupDirectory === false || $backupDirectory !== $targetDirectory) {
            if (is_string($backup)) {
                $this->files->delete($backup);
            }

            throw new RuntimeException("Unable to create an llms.txt backup beside [{$target}].");
        }

        try {
            if (! $this->files->copy($target, $backup)) {
                throw new RuntimeException("Unable to back up llms.txt at [{$target}].");
            }

            $permissions = @fileperms($target);

            if ($permissions === false || ! $this->files->chmod($backup, $mode = $permissions & 0777)) {
                throw new RuntimeException("Unable to preserve llms.txt permissions for [{$target}].");
            }
        } catch (Throwable $exception) {
            $this->files->delete($backup);

            throw $exception;
        }

        return ['exists' => true, 'path' => $target, 'backup' => $backup, 'mode' => $mode];
    }

    private function rollback(Throwable $original, array $settingsSnapshot, ?array $fileSnapshot = null): never
    {
        $rollbackErrors = [];

        try {
            Llms::restoreSettings($settingsSnapshot);
        } catch (Throwable $exception) {
            $rollbackErrors[] = 'settings: '.$exception->getMessage();
        }

        if ($fileSnapshot) {
            try {
                $this->restoreFileSnapshot($fileSnapshot);
            } catch (Throwable $exception) {
                $rollbackErrors[] = 'llms.txt: '.$exception->getMessage();
            }
        }

        if ($rollbackErrors) {
            throw new RuntimeException(
                $original->getMessage().' Rollback also failed for '.implode('; ', $rollbackErrors),
                previous: $original,
            );
        }

        throw $original;
    }

    private function restoreFileSnapshot(array $snapshot): void
    {
        $path = $snapshot['path'];

        if (! $snapshot['exists']) {
            if ($this->files->exists($path) && ! $this->files->delete($path)) {
                throw new RuntimeException("Unable to remove the newly generated file at [{$path}].");
            }

            return;
        }

        $backup = $snapshot['backup'];

        if (! is_string($backup) || ! $this->files->isFile($backup)) {
            throw new RuntimeException("The llms.txt rollback backup for [{$path}] is unavailable.");
        }

        if (! @rename($backup, $path)) {
            if ($this->files->exists($path) && ! $this->files->delete($path)) {
                throw new RuntimeException("Unable to replace llms.txt at [{$path}] during rollback.");
            }

            if (! @rename($backup, $path)) {
                throw new RuntimeException("Unable to restore llms.txt at [{$path}]. The backup remains at [{$backup}].");
            }
        }

        if (! $this->files->chmod($path, $snapshot['mode'])) {
            throw new RuntimeException("Unable to restore llms.txt permissions at [{$path}].");
        }
    }

    private function discardFileSnapshot(array $snapshot): void
    {
        if (is_string($snapshot['backup']) && $this->files->exists($snapshot['backup'])) {
            if (! $this->files->delete($snapshot['backup'])) {
                report(new RuntimeException("Unable to remove the llms.txt backup at [{$snapshot['backup']}]."));
            }
        }
    }

    private function fileMatches(string $path, string $contents, string $checksum): bool
    {
        try {
            if (! $this->files->isFile($path) || $this->files->size($path) !== strlen($contents)) {
                return false;
            }

            $existingChecksum = $this->files->hash($path, 'sha256');

            return is_string($existingChecksum) && hash_equals($checksum, $existingChecksum);
        } catch (Throwable) {
            return false;
        }
    }

    private function existingGeneratedAt(array $generated, string $checksum): ?Carbon
    {
        if (($generated['checksum'] ?? null) !== $checksum || ! is_string($generated['timestamp'] ?? null)) {
            return null;
        }

        try {
            return Carbon::parse($generated['timestamp']);
        } catch (Throwable) {
            return null;
        }
    }

    private function result(
        string $path,
        string $contents,
        Carbon $generatedAt,
        bool $changed,
        bool $settingsChanged,
        bool $removed,
    ): array {
        return [
            'path' => $path,
            'timestamp' => $generatedAt->toIso8601String(),
            'contents' => $contents,
            'changed' => $changed,
            'settings_changed' => $settingsChanged,
            'removed' => $removed,
        ];
    }

    private function read(string $path): string|false
    {
        if (! $stream = @fopen($path, 'rb')) {
            return false;
        }

        try {
            return stream_get_contents($stream, self::MAX_BYTES + 1);
        } finally {
            fclose($stream);
        }
    }

    private function statusResult(
        string $path,
        bool $exists = false,
        bool $managed = false,
        ?string $contents = null,
        ?string $timestamp = null,
        ?string $issue = null,
        bool $outdated = false,
    ): array {
        return [
            'exists' => $exists,
            'managed' => $managed,
            'path' => $path,
            'contents' => $contents,
            'timestamp' => $timestamp,
            'issue' => $issue,
            'outdated' => $outdated,
        ];
    }
}
