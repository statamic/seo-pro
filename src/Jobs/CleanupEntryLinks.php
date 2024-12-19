<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\SeoPro\Contracts\Linking\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\Linking\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\Linking\Links\LinksRepository;
use Statamic\SeoPro\Jobs\Concerns\DispatchesSeoProJobs;

class CleanupEntryLinks implements ShouldBeUnique, ShouldQueue
{
    use DispatchesSeoProJobs;

    public function __construct(
        public string $entryId,
    ) {}

    public function handle(
        LinksRepository $linksRepository,
        KeywordsRepository $keywordsRepository,
        EntryEmbeddingsRepository $entryEmbeddingsRepository,
    ): void {
        $linksRepository->deleteLinksForEntry($this->entryId);
        $keywordsRepository->deleteKeywordsForEntry($this->entryId);
        $entryEmbeddingsRepository->deleteEmbeddingsForEntry($this->entryId);
    }
}
