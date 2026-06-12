<?php

namespace Statamic\SeoPro\Events;

use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Events\Event;

class TaxonomyDefaultsSaved extends Event
{
    public function __construct(public Taxonomy $taxonomy) {}
}
