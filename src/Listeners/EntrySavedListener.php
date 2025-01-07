<?php

namespace Statamic\SeoPro\Listeners;

use Statamic\Events\EntrySaved;
use Statamic\SeoPro\Jobs\ScanEntryLinks;

class EntrySavedListener
{
    public function handle(EntrySaved $event)
    {
        if (! $event->entry->url()) {
            return;
        }

        ScanEntryLinks::dispatchSeoProJob($event->entry->id());
    }
}
