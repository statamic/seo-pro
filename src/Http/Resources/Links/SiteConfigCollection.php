<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResourceCollection;

class SiteConfigCollection extends ListedResourceCollection
{
    public $collects = ListedSiteConfig::class;
}
