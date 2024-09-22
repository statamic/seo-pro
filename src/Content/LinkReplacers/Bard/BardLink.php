<?php

namespace Statamic\SeoPro\Content\LinkReplacers\Bard;

class BardLink
{
    public function __construct(
        public string $href,
        public ?string $rel = null,
        public ?string $target = null,
        public ?string $title = null,
    ) {}
}
