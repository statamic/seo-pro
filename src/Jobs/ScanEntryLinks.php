<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Facades\Entry;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinkCrawler;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinksRepository;
use Statamic\SeoPro\Jobs\Concerns\DispatchesSeoProJobs;

class ScanEntryLinks implements ShouldQueue
{
    use DispatchesSeoProJobs;

    public function __construct(
        public string $entryId,
    ) {}

    public function handle(
        LinksRepository $linksRepository,
        KeywordsRepository $keywordsRepository,
        EntryEmbeddingsRepository $entryEmbeddingsRepository,
        LinkCrawler $linkCrawler,
    ): void
    {
        $entry = Entry::find($this->entryId);

        if (! $entry) {
            return;
        }

        $linkCrawler->scanEntry($entry);
        $linkCrawler->updateInboundInternalLinkCount($entry);

        if ($linksRepository->isLinkingEnabledForEntry($entry)) {
            $keywordsRepository->generateKeywordsForEntry($entry);
            $entryEmbeddingsRepository->generateEmbeddingsForEntry($entry);
        }
    }
}