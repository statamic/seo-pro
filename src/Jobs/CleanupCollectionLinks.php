<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\SeoPro\Contracts\Linking\ConfigurationRepository;
use Statamic\SeoPro\Contracts\Linking\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\Linking\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\Linking\Links\LinksRepository;
use Statamic\SeoPro\Jobs\Concerns\DispatchesSeoProJobs;

class CleanupCollectionLinks implements ShouldBeUnique, ShouldQueue
{
    use DispatchesSeoProJobs;

    public function __construct(
        public string $handle,
    ) {}

    public function handle(
        ConfigurationRepository $configurationRepository,
        LinksRepository $linksRepository,
        KeywordsRepository $keywordsRepository,
        EntryEmbeddingsRepository $entryEmbeddingsRepository,
    ): void {
        if (! $this->handle) {
            return;
        }

        $configurationRepository->deleteCollectionConfiguration($this->handle);
        $linksRepository->deleteLinksForCollection($this->handle);
        $keywordsRepository->deleteKeywordsForCollection($this->handle);
        $entryEmbeddingsRepository->deleteEmbeddingsForCollection($this->handle);
    }
}
