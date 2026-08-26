<?php

namespace Statamic\SeoPro\Robots;

use Illuminate\Filesystem\Filesystem;
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
        $existed = $this->files->exists($path);
        $previousContents = $existed ? $this->files->get($path) : null;

        try {
            $this->files->replace($path, $contents);
        } catch (Throwable $exception) {
            throw new RuntimeException("Unable to write robots.txt to [{$path}].", previous: $exception);
        }

        $generatedAt = now();

        try {
            Robots::saveGenerated($policy, $contents, $generatedAt);
        } catch (Throwable $exception) {
            $existed
                ? $this->files->replace($path, $previousContents)
                : $this->files->delete($path);

            throw $exception;
        }

        RobotsTxtGenerated::dispatch($policy, $path, $generatedAt);

        return [
            'path' => $path,
            'timestamp' => $generatedAt->toIso8601String(),
            'contents' => $contents,
        ];
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
        $managed = ($generated['checksum'] ?? null) === hash('sha256', $contents);

        return [
            'exists' => true,
            'managed' => $managed,
            'path' => $path,
            'contents' => $contents,
            'timestamp' => $managed ? ($generated['timestamp'] ?? null) : null,
            'importable' => ! $managed,
            'import_issue' => null,
        ];
    }

    public function path(): string
    {
        return public_path('robots.txt');
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
        ];
    }
}
