<?php

namespace Statamic\SeoPro\Listeners;

use Statamic\Events\CollectionDeleted;
use Statamic\SeoPro\Jobs\CleanupCollectionLinks;

class CollectionDeletedListener
{
    public function handle(CollectionDeleted $event)
    {
        CleanupCollectionLinks::dispatchSeoProJob($event->collection->handle());
    }
}