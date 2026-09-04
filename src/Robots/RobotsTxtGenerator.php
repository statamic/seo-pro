<?php

namespace Statamic\SeoPro\Robots;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\LockableFile;
use Illuminate\Support\Carbon;
use RuntimeException;
use Statamic\SeoPro\Events\RobotsTxtGenerated;
use Throwable;

class RobotsTxtGenerator
{
    public const MAX_IMPORT_BYTES = 512000;

    public function __construct(
        private Filesystem $files,
        private RobotsRenderer $renderer,
    ) {}

    public function generate(?RobotsPolicy $policy = null): array
    {
        $lock = null;

        try {
            $lock = new LockableFile(storage_path('framework/cache/seo-pro/robots-txt.lock'), 'c+');
            $lock->getExclusiveLock();
        } catch (Throwable $exception) {
            $lock?->close();

            throw new RuntimeException('Unable to acquire the robots.txt generation lock. Another generation may already be in progress.', previous: $exception);
        }

        try {
            return $this->generateWhileLocked($policy);
        } finally {
            $lock->close();
        }
    }

    private function generateWhileLocked(?RobotsPolicy $policy): array
    {
        $policy ??= Robots::get();
        $contents = $this->renderer->render($policy);
        $path = $this->path();
        $checksum = hash('sha256', $contents);
        $generated = Robots::generated() ?? [];
        $existingGeneratedAt = $this->existingGeneratedAt($generated, $checksum);
        $fileMatches = $this->fileMatches($path, $contents, $checksum);
        $settingsMatch = Robots::get()->all() === $policy->all();

        if ($fileMatches && $settingsMatch && $existingGeneratedAt) {
            return $this->result($path, $contents, $existingGeneratedAt, false, false);
        }

        if ($fileMatches) {
            $generatedAt = $existingGeneratedAt ?? now();
            $settingsSnapshot = Robots::settingsSnapshot();

            try {
                $this->saveGeneratedSettings($policy, $contents, $generatedAt);
            } catch (Throwable $exception) {
                $this->rollback($exception, $settingsSnapshot);
            }

            return $this->result($path, $contents, $generatedAt, false, true);
        }

        $settingsSnapshot = Robots::settingsSnapshot();
        $fileSnapshot = $this->snapshotFile($path);
        $generatedAt = now();

        try {
            $this->writeFile($path, $contents);
            $this->saveGeneratedSettings($policy, $contents, $generatedAt);
        } catch (Throwable $exception) {
            $this->rollback($exception, $settingsSnapshot, $fileSnapshot);
        }

        $this->discardFileSnapshot($fileSnapshot);
        RobotsTxtGenerated::dispatch($policy, $path, $generatedAt);

        return $this->result($path, $contents, $generatedAt, true, true);
    }

    public function status(): array
    {
        $path = $this->path();

        if (! $this->files->exists($path)) {
            return [
                'exists' => false,
                'managed' => false,
                'path' => $path,
                'contents' => null,
                'timestamp' => null,
                'importable' => false,
                'import_issue' => null,
                'outdated' => false,
            ];
        }

        if (! $this->files->isFile($path)) {
            return $this->unavailableStatus($path, 'unreadable');
        }

        try {
            if ($this->files->size($path) > self::MAX_IMPORT_BYTES) {
                return $this->unavailableStatus($path, 'too_large');
            }

            $contents = $this->readForImport($path);
        } catch (Throwable) {
            return $this->unavailableStatus($path, 'unreadable');
        }

        if ($contents === false) {
            return $this->unavailableStatus($path, 'unreadable');
        }

        // Guard against the file growing between the size check and the read.
        if (strlen($contents) > self::MAX_IMPORT_BYTES) {
            return $this->unavailableStatus($path, 'too_large');
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            return $this->unavailableStatus($path, 'invalid_utf8');
        }

        $generated = Robots::generated();
        $checksum = hash('sha256', $contents);
        $managed = ($generated['checksum'] ?? null) === $checksum;
        $outdated = $managed && hash('sha256', $this->renderer->render(Robots::get())) !== $checksum;

        return [
            'exists' => true,
            'managed' => $managed,
            'path' => $path,
            'contents' => $contents,
            'timestamp' => $managed ? ($generated['timestamp'] ?? null) : null,
            'importable' => ! $managed,
            'import_issue' => null,
            'outdated' => $outdated,
        ];
    }

    public function path(): string
    {
        return public_path('robots.txt');
    }

    private function writeFile(string $path, string $contents): void
    {
        try {
            $this->files->replace($path, $contents);
        } catch (Throwable $exception) {
            throw new RuntimeException("Unable to write robots.txt to [{$path}].", previous: $exception);
        }
    }

    private function saveGeneratedSettings(RobotsPolicy $policy, string $contents, Carbon $generatedAt): void
    {
        if (! Robots::saveGenerated($policy, $contents, $generatedAt)) {
            throw new RuntimeException('Unable to save robots.txt settings.');
        }

        $settings = Robots::settingsSnapshot()['raw']['robots'] ?? [];

        if (($settings['policy'] ?? null) !== $policy->all()
            || ($settings['generated']['timestamp'] ?? null) !== $generatedAt->toIso8601String()
            || ($settings['generated']['checksum'] ?? null) !== hash('sha256', $contents)) {
            throw new RuntimeException('The generated robots.txt settings could not be verified after saving.');
        }
    }

    private function snapshotFile(string $path): array
    {
        $target = realpath($path) ?: $path;

        if (! $this->files->exists($target)) {
            return ['exists' => false, 'path' => $target, 'backup' => null, 'mode' => null];
        }

        if (! $this->files->isFile($target)) {
            throw new RuntimeException("Unable to back up robots.txt at [{$target}].");
        }

        $directory = dirname($target);
        $backup = @tempnam($directory, '.seo-pro-robots-');
        $backupDirectory = is_string($backup) ? realpath(dirname($backup)) : false;
        $targetDirectory = realpath($directory);

        if ($backup === false || $backupDirectory === false || $backupDirectory !== $targetDirectory) {
            if (is_string($backup)) {
                $this->files->delete($backup);
            }

            throw new RuntimeException("Unable to create a robots.txt backup beside [{$target}].");
        }

        try {
            if (! $this->files->copy($target, $backup)) {
                throw new RuntimeException("Unable to back up robots.txt at [{$target}].");
            }

            $permissions = @fileperms($target);

            if ($permissions === false || ! $this->files->chmod($backup, $mode = $permissions & 0777)) {
                throw new RuntimeException("Unable to preserve robots.txt permissions for [{$target}].");
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
            Robots::restoreSettings($settingsSnapshot);
        } catch (Throwable $exception) {
            $rollbackErrors[] = 'settings: '.$exception->getMessage();
        }

        if ($fileSnapshot) {
            try {
                $this->restoreFileSnapshot($fileSnapshot);
            } catch (Throwable $exception) {
                $rollbackErrors[] = 'robots.txt: '.$exception->getMessage();
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
            throw new RuntimeException("The robots.txt rollback backup for [{$path}] is unavailable.");
        }

        if (! @rename($backup, $path)) {
            if ($this->files->exists($path) && ! $this->files->delete($path)) {
                throw new RuntimeException("Unable to replace robots.txt at [{$path}] during rollback.");
            }

            if (! @rename($backup, $path)) {
                throw new RuntimeException("Unable to restore robots.txt at [{$path}]. The backup remains at [{$backup}].");
            }
        }

        if (! $this->files->chmod($path, $snapshot['mode'])) {
            throw new RuntimeException("Unable to restore robots.txt permissions at [{$path}].");
        }
    }

    private function discardFileSnapshot(array $snapshot): void
    {
        if (is_string($snapshot['backup']) && $this->files->exists($snapshot['backup'])) {
            if (! $this->files->delete($snapshot['backup'])) {
                report(new RuntimeException("Unable to remove the robots.txt backup at [{$snapshot['backup']}]."));
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
    ): array {
        return [
            'path' => $path,
            'timestamp' => $generatedAt->toIso8601String(),
            'contents' => $contents,
            'changed' => $changed,
            'settings_changed' => $settingsChanged,
        ];
    }

    private function readForImport(string $path): string|false
    {
        if (! $stream = @fopen($path, 'rb')) {
            return false;
        }

        try {
            return stream_get_contents($stream, self::MAX_IMPORT_BYTES + 1);
        } finally {
            fclose($stream);
        }
    }

    private function unavailableStatus(string $path, string $issue): array
    {
        return [
            'exists' => true,
            'managed' => false,
            'path' => $path,
            'contents' => null,
            'timestamp' => null,
            'importable' => false,
            'import_issue' => $issue,
            'outdated' => false,
        ];
    }
}
