<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResourceCollection;

class EntryLinksCollection extends ListedResourceCollection
{
    public $collects = ListedEntryLink::class;
}
