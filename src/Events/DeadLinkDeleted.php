<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\DeadLinks\Link;

class DeadLinkDeleted extends Event
{
    public function __construct(public Link $link) {}
}
