<?php

namespace Statamic\SeoPro\Contracts\Linking\Links;

use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Models\EntryLink;
use Statamic\SeoPro\Linking\Links\IgnoredSuggestion;
use Statamic\SeoPro\Linking\Links\LinkScanOptions;

interface LinksRepository
{
    public function ignoreSuggestion(IgnoredSuggestion $suggestion): void;

    public function isLinkingEnabledForEntry(Entry $entry): bool;

    public function scanEntry(Entry $entry, ?LinkScanOptions $options = null): ?EntryLink;

    public function deleteLinksForSite(string $handle): void;

    public function deleteLinksForEntry(string $entryId): void;

    public function deleteLinksForCollection(string $handle): void;
}
