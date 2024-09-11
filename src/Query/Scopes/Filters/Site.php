<?php

namespace Statamic\SeoPro\Query\Scopes\Filters;

use Statamic\Query\scopes\Filters\Site as CoreSite;

class Site extends CoreSite
{
    public function visibleTo($key)
    {
        return $key === 'seo_pro.links' && $this->availableSites()->count() > 0;
    }
}