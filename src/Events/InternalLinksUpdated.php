<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\TextProcessing\Links\LinkChangeSet;

class InternalLinksUpdated extends Event
{
    public function __construct(
        public readonly LinkChangeSet $changeSet
    ) {}
}
