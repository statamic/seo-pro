<?php

namespace Statamic\SeoPro\Redirects\Eloquent;

use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\SeoPro\Facades\Redirect;
use Statamic\SeoPro\Redirects\RedirectQueryBuilder as QueryBuilder;

class RedirectQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)->map(function ($model) {
            return Redirect::fromModel($model);
        });
    }
}