<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResourceCollection;

class CollectionConfigCollection extends ListedResourceCollection
{
    public $collects = ListedCollectionConfig::class;
}
