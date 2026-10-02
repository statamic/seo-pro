<?php

namespace Statamic\SeoPro\BrokenLinks;

use Closure;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Term;
use Statamic\Facades\User;

class Reference
{
    private static array $editUrlResolvers = [];

    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly string $site,
        public readonly ?string $title = null,
        public readonly ?string $field = null,
    ) {}

    public static function resolveEditUrlsUsing(string $type, Closure $resolver): void
    {
        self::$editUrlResolvers[$type] = $resolver;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'site' => $this->site,
            'title' => $this->title,
            'field' => $this->field,
        ];
    }

    public function withField(string $field): self
    {
        return new self($this->type, $this->id, $this->site, $this->title, $field);
    }

    public function key(): string
    {
        return "{$this->type}::{$this->id}::{$this->site}";
    }

    public function is(Reference $reference): bool
    {
        return $this->key() === $reference->key();
    }

    public function editUrl(): ?string
    {
        if (isset(self::$editUrlResolvers[$this->type])) {
            return (self::$editUrlResolvers[$this->type])($this);
        }

        $item = match ($this->type) {
            'entry' => Entry::find($this->id),
            'term' => Term::find($this->id)?->in($this->site),
            'global' => GlobalSet::find($this->id)?->in($this->site),
            default => null,
        };

        if (! $item || ! User::current()->can('edit', $item)) {
            return null;
        }

        return $item->editUrl();
    }
}
