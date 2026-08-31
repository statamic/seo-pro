<?php

namespace Statamic\SeoPro\Robots;

use Illuminate\Filesystem\Filesystem;
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

            if (! Robots::saveGenerated($policy, $contents, $generatedAt)) {
                throw new RuntimeException('Unable to save robots.txt settings.');
            }

            return $this->result($path, $contents, $generatedAt, false, true);
        }

        $existed = $this->files->exists($path);
        $previousContents = $existed ? $this->files->get($path) : null;

        try {
            $this->files->replace($path, $contents);
        } catch (Throwable $exception) {
            throw new RuntimeException("Unable to write robots.txt to [{$path}].", previous: $exception);
        }

        $generatedAt = now();

        try {
            if (! Robots::saveGenerated($policy, $contents, $generatedAt)) {
                throw new RuntimeException('Unable to save robots.txt settings.');
            }
        } catch (Throwable $exception) {
            $existed
                ? $this->files->replace($path, $previousContents)
                : $this->files->delete($path);

            throw $exception;
        }

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
