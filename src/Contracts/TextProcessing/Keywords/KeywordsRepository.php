<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Keywords;

use Statamic\Contracts\Entries\Entry;

interface KeywordsRepository
{
    public function getIgnoredKeywordsForEntry(Entry $entry): array;

    public function generateKeywordsForAllEntries(int $chunkSize = 100): void;

    public function getKeywordsForEntries(array $entryIds): array;

    public function generateKeywordsForEntry(Entry $entry): void;

    public function deleteKeywordsForEntry(string $entryId): void;

    public function deleteKeywordsForSite(string $handle): void;

    public function deleteKeywordsForCollection(string $handle): void;
}
