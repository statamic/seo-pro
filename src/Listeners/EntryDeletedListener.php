<?php

namespace Statamic\SeoPro\Listeners;

use Statamic\Events\EntryDeleted;
use Statamic\SeoPro\Jobs\CleanupEntryLinks;

class EntryDeletedListener
{
    public function handle(EntryDeleted $event)
    {
        CleanupEntryLinks::dispatchSeoProJob($event->entry->id());
    }
}
