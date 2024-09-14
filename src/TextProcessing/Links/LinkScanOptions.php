<?php

namespace Statamic\SeoPro\TextProcessing\Links;

readonly class LinkScanOptions
{
    function __construct(
        public bool $withInternalChangeSets = false,
    ) {}
}