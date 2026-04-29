<?php

namespace Statamic\SeoPro\Redirects\Eloquent;

use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\SeoPro\Facades\Error;
use Statamic\SeoPro\Redirects\ErrorQueryBuilder as QueryBuilder;

class ErrorQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)->map(function ($model) {
            return Error::fromModel($model);
        });
    }
}
