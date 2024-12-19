<?php

namespace Statamic\SeoPro\Content;

use Statamic\Facades\Entry as EntryApi;

class LinkReplacement
{
    protected ?string $cachedTarget = null;

    public function __construct(
        public readonly string $phrase,
        public readonly string $section,
        public readonly string $target,
        public readonly string $fieldHandle,
    ) {}

    public function getTarget(): ?string
    {
        if ($this->cachedTarget) {
            return $this->cachedTarget;
        }

        $linkTarget = $this->target;

        if (str_starts_with($this->target, 'entry::')) {
            $targetEntry = EntryApi::find(substr($this->target, 7));

            if (! $targetEntry) {
                return null;
            }

            $linkTarget = $targetEntry->uri();
        }

        if ($this->section !== '--none--') {
            $linkTarget .= '#'.$this->section;
        }

        return $this->cachedTarget = $linkTarget;
    }
}
