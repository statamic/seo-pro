<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\SiteDefaults\LocalizedSiteDefaults;

class SiteDefaultsSaved extends Event
{
    public function __construct(public LocalizedSiteDefaults $defaults) {}
}
