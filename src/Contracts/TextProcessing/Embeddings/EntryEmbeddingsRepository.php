<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Embeddings;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\TextProcessing\Vectors\Vector;

interface EntryEmbeddingsRepository
{
    public function getRelatedEmbeddingsForEntryLazy(Entry $entry);

    public function getRelatedEmbeddingsForEntry(Entry $entry): Collection;

    public function generateEmbeddingsForEntry(Entry $entry): void;

    public function generateEmbeddingsForAllEntries(): void;

    public function getEmbeddingsForEntry(Entry $entry): ?Vector;

    public function getEmbeddingsForCollection(string $handle, string $site = 'default'): Collection;

    public function getEmbeddingsForSite(string $handle): Collection;

    public function deleteEmbeddingsForEntry(string $entryId): void;

    public function deleteEmbeddingsForCollection(string $handle): void;

    public function deleteEmbeddingsForSite(string $handle): void;
}
