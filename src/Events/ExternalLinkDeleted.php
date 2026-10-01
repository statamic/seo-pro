<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\BrokenLinks\ExternalLink;

class ExternalLinkDeleted extends Event
{
    public function __construct(public ExternalLink $link) {}
}
