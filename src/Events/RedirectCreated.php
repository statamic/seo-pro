<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\Redirects\Redirect;

class RedirectCreated extends Event
{
    public function __construct(public Redirect $redirect) {}
}
