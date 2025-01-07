<?php

namespace Statamic\SeoPro\Contracts\Linking\Embeddings;

use Generator;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Linking\Similarity\ResolverOptions;
use Statamic\SeoPro\Linking\Vectors\Vector;

interface EntryEmbeddingsRepository
{
    public function getRelatedEmbeddingsForEntry(Entry $entry, ResolverOptions $options, int $chunkSize = 100): Generator;

    public function generateEmbeddingsForEntry(Entry $entry): void;

    public function generateEmbeddingsForAllEntries(int $chunkSize = 100): void;

    public function getEmbeddingsForEntry(Entry $entry): ?Vector;

    public function getEmbeddingsForCollection(string $handle, string $site = 'default'): Collection;

    public function getEmbeddingsForSite(string $handle): Collection;

    public function deleteEmbeddingsForEntry(string $entryId): void;

    public function deleteEmbeddingsForCollection(string $handle): void;

    public function deleteEmbeddingsForSite(string $handle): void;
}
