<?php

namespace Statamic\SeoPro\Config;

use Exception;

class EnvWriter
{
    protected string $contents = '';
    protected ?string $path = null;
    protected bool $madeChanges = false;

    public function open(string $path): void
    {
        $this->path = $path;
        $this->contents = file_get_contents($path);
    }

    public function setValue(string $key, string $value, string $configKey): static
    {
        $this->madeChanges = true;

        $pattern = $this->getReplacementPattern($key, $value, $configKey);

        if ($this->keyExists($key)) {
            $this->replaceValue($pattern, "{$key}={$value}");
        } else {
            $this->appendValue($key, $value);
        }

        return $this;
    }

    protected function appendValue(string $key, string $value): void
    {
        $this->contents .= "\n{$key}={$value}";
    }

    protected function replaceValue(string $pattern, string $newValue): void
    {
        $this->contents = preg_replace(
            $pattern,
            $newValue,
            $this->contents,
        );
    }

    protected function keyExists(string $key): bool
    {
        return preg_match('/^'.$key.'=/m', $this->contents);
    }

    protected function getReplacementPattern(string $key, string $value, string $configKey): string
    {
        $escaped = preg_quote('='.config($configKey), '/');

        return "/^{$key}{$escaped}/m";
    }

    public function save(): void
    {
        if (! $this->madeChanges) {
            return;
        }

        if (! $this->path) {
            throw new Exception('No environment file has been opened.');
        }

        file_put_contents($this->path, $this->contents);
    }
}
