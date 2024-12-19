<?php

namespace Statamic\SeoPro\Listeners;

use Statamic\Events\SiteDeleted;
use Statamic\SeoPro\Jobs\CleanupSiteLinks;

class SiteDeletedListener
{
    public function handle(SiteDeleted $event)
    {
        CleanupSiteLinks::dispatchSeoProJob($event->site->handle());
    }
}
