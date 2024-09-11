<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Keywords;

use Statamic\Contracts\Entries\Entry;

interface KeywordsRepository
{
    public function getIgnoredKeywordsForEntry(Entry $entry): array;

    public function generateKeywordsForAllEntries();

    public function getKeywordsForEntries(array $entryIds): array;

    public function generateKeywordsForEntry(Entry $entry);

    public function deleteKeywordsForEntry(string $entryId);

    public function deleteKeywordsForSite(string $handle): void;

    public function deleteKeywordsForCollection(string $handle): void;
}