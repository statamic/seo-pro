<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;

class SiteDefaultsSaved extends Event
{
    public $defaults;

    public function __construct($defaults)
    {
        $this->defaults = $defaults;
    }

}
