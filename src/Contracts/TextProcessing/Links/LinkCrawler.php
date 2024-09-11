<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Links;

use Statamic\Contracts\Entries\Entry;

interface LinkCrawler
{
    public function scanAllEntries(): void;

    public function scanEntry(Entry $entry): void;

    public function updateInboundInternalLinkCount(Entry $entry): void;

}