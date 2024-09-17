<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinksRepository;
use Statamic\SeoPro\Jobs\Concerns\DispatchesSeoProJobs;

class CleanupSiteLinks implements ShouldBeUnique, ShouldQueue
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
        $configurationRepository->deleteSiteConfiguration($this->handle);
        $linksRepository->deleteLinksForSite($this->handle);
        $keywordsRepository->deleteKeywordsForSite($this->handle);
        $entryEmbeddingsRepository->deleteEmbeddingsForSite($this->handle);
    }
}
