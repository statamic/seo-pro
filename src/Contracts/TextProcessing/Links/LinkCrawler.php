<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Links;

use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\TextProcessing\Links\LinkScanOptions;

interface LinkCrawler
{
    public function scanAllEntries(): void;

    public function scanEntry(Entry $entry, ?LinkScanOptions $options = null): void;

    public function updateInboundInternalLinkCount(Entry $entry): void;

    public function updateLinkStatistics(Entry $entry): void;
}
