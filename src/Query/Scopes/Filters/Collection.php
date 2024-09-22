<?php

namespace Statamic\SeoPro\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filters\Collection as CoreCollection;

class Collection extends CoreCollection
{
    public function visibleTo($key)
    {
        return $key === 'seo_pro.links';
    }
}
