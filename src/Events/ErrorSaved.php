<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\Redirects\Error;

class ErrorSaved extends Event
{
    public function __construct(public Error $error) {}
}
