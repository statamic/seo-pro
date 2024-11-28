<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\BaseResourceCollection;

class EntryLinks extends BaseResourceCollection
{
    public $collects = ListedEntryLink::class;
}
