<?php

namespace Statamic\SeoPro\Events;

use Statamic\Contracts\Entries\Collection;
use Statamic\Events\Event;

class CollectionDefaultsSaved extends Event
{
    public function __construct(public Collection $collection) {}
}
