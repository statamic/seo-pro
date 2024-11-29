<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResourceCollection;

class GlobalAutomaticLinksCollection extends ListedResourceCollection
{
    public $collects = ListedAutomaticLink::class;
}
