<?php

namespace Statamic\SeoPro\TextProcessing\Links;

readonly class LinkScanOptions
{
    public function __construct(
        public bool $withInternalChangeSets = false,
    ) {}
}
