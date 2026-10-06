<?php

namespace Statamic\SeoPro\BrokenLinks\Eloquent;

use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\BrokenLinks\ExternalLinkQueryBuilder as QueryBuilder;
use Statamic\SeoPro\Facades;

class ExternalLinkQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)->map(function (ExternalLinkModel $model): ExternalLink {
            return Facades\ExternalLink::fromModel($model);
        });
    }
}
