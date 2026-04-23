<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\Redirects\Error;

class ErrorCreated extends Event
{
    public function __construct(public Error $error) {}
}
