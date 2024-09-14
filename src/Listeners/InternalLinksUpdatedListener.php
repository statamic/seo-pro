<?php

namespace Statamic\SeoPro\Listeners;

use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinkCrawler;
use Statamic\SeoPro\Events\InternalLinksUpdated;

class InternalLinksUpdatedListener
{
    function __construct(
        protected LinkCrawler $crawler,
    ) {}

    public function handle(InternalLinksUpdated $event)
    {
        $event->changeSet->entries()->each(fn(Entry $entry) => $this->crawler->updateLinkStatistics($entry));
    }
}