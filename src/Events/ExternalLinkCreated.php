<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\BrokenLinks\ExternalLink;

class ExternalLinkCreated extends Event
{
    public function __construct(public ExternalLink $link) {}
}
