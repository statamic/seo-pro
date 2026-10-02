<?php

namespace Statamic\SeoPro\BrokenLinks;

use Closure;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\Support\Str;

class Reference
{
    private static array $resolvers = [];

    private $content;

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
        if (isset(self::$resolvers[$this->type])) {
            return self::$resolvers[$this->type]['title']($this);
        }

        return match ($this->type) {
            'entry' => $this->content()?->value('title'),
            'term', 'global' => $this->content()?->title(),
            default => null,
        };
    }

    public function editUrl(): ?string
    {
        if (isset(self::$resolvers[$this->type])) {
            return self::$resolvers[$this->type]['editUrl']($this);
        }

        $content = $this->content();

        if (! $content || ! User::current()->can('edit', $content)) {
            return null;
        }

        return $content->editUrl();
    }

    private function content()
    {
        return $this->content ??= match ($this->type) {
            'entry' => Entry::find($this->id),
            'term' => Term::find($this->id)?->in($this->site),
            'global' => GlobalSet::find($this->id)?->in($this->site),
            default => null,
        };
    }
}
