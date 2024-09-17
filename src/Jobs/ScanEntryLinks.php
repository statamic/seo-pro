<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Facades\Entry;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinkCrawler;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinksRepository;
use Statamic\SeoPro\Jobs\Concerns\DispatchesSeoProJobs;
use Statamic\SeoPro\TextProcessing\Links\LinkScanOptions;

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
