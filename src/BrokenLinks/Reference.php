<?php

namespace Statamic\SeoPro\BrokenLinks;

use Closure;
use Statamic\Support\Str;

class Reference
{
    private static array $resolvers = [];

    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly string $site,
    ) {}

    public static function resolveUsing(string $type, Closure $title, Closure $editUrl): void
    {
        self::$resolvers[$type] = ['title' => $title, 'editUrl' => $editUrl];
    }

    public static function fromKey(string $key): self
    {
        return new self(
            type: Str::before($key, '::'),
            id: Str::of($key)->after('::')->beforeLast('::')->toString(),
            site: Str::afterLast($key, '::'),
        );
    }

    public function key(): string
    {
        return "{$this->type}::{$this->id}::{$this->site}";
    }

    public function is(Reference $reference): bool
    {
        return $this->key() === $reference->key();
    }

    public function title(): ?string
    {
        $resolver = self::$resolvers[$this->type]['title'] ?? null;

        return $resolver ? $resolver($this) : null;
    }

    public function editUrl(): ?string
    {
        $resolver = self::$resolvers[$this->type]['editUrl'] ?? null;

        return $resolver ? $resolver($this) : null;
    }
}
