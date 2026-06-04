<?php

namespace Statamic\SeoPro\Events;

use Statamic\Events\Event;
use Statamic\SeoPro\SectionDefaults\LocalizedSectionDefaults;

class SectionDefaultsSaved extends Event
{
    public function __construct(public LocalizedSectionDefaults $defaults) {}
}
