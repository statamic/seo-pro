<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\Redirects\Error;

class ErrorDeleted extends Event
{
    public function __construct(public Error $error) {}
}
