<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Facades\Entry;
use Statamic\SeoPro\Contracts\Linking\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\Linking\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\Linking\Links\LinkCrawler;
use Statamic\SeoPro\Contracts\Linking\Links\LinksRepository;
use Statamic\SeoPro\Jobs\Concerns\DispatchesSeoProJobs;
use Statamic\SeoPro\Linking\Links\LinkScanOptions;

class ScanEntryLinks implements ShouldQueue
{
    use DispatchesSeoProJobs;

    public function __construct(
        public string $entryId,
    ) {}

    public function handle(
        LinksRepository $linksRepository,
        KeywordsRepository $keywordsRepository,
        LinkCrawler $linkCrawler,
        EntryEmbeddingsRepository $entryEmbeddingsRepository,
    ): void {
        $entry = Entry::find($this->entryId);

        if (! $entry) {
            return;
        }

        $linkCrawler->scanEntry($entry, new LinkScanOptions(
            withInternalChangeSets: true
        ));

        $linkCrawler->updateInboundInternalLinkCount($entry);

        if ($linksRepository->isLinkingEnabledForEntry($entry)) {
            $keywordsRepository->generateKeywordsForEntry($entry);
            $entryEmbeddingsRepository->generateEmbeddingsForEntry($entry);
        }
    }
}
