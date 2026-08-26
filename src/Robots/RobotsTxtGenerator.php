<?php

namespace Statamic\SeoPro\Robots;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Statamic\SeoPro\Events\RobotsTxtGenerated;
use Throwable;

class RobotsTxtGenerator
{
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
            ];
        }

        $contents = $this->files->get($path);
        $generated = Robots::generated();
        $managed = ($generated['checksum'] ?? null) === hash('sha256', $contents);

        return [
            'exists' => true,
            'managed' => $managed,
            'path' => $path,
            'contents' => $contents,
            'timestamp' => $managed ? ($generated['timestamp'] ?? null) : null,
        ];
    }

    public function path(): string
    {
        return public_path('robots.txt');
    }
}
