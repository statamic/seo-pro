<?php

namespace Statamic\SeoPro\Linking\Links;

readonly class LinkScanOptions
{
    public function __construct(
        public bool $withInternalChangeSets = false,
    ) {}
}
