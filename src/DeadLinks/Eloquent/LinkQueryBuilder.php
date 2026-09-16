<?php

namespace Statamic\SeoPro\DeadLinks\Eloquent;

use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\SeoPro\DeadLinks\LinkQueryBuilder as QueryBuilder;
use Statamic\SeoPro\Facades\DeadLink;

class LinkQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)->map(function ($model) {
            return DeadLink::fromModel($model);
        });
    }
}
