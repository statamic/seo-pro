<?php

namespace Statamic\SeoPro\Listeners;

use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Contracts\Linking\Links\LinkCrawler;
use Statamic\SeoPro\Events\InternalLinksUpdated;

class InternalLinksUpdatedListener
{
    public function __construct(
        protected LinkCrawler $crawler,
    ) {}

    public function handle(InternalLinksUpdated $event)
    {
        $event->changeSet->entries()->each(fn (Entry $entry) => $this->crawler->updateLinkStatistics($entry));
    }
}
