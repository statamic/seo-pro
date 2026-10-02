<?php

namespace Statamic\SeoPro\BrokenLinks\Stache;

use Illuminate\Support\Collection;
use Statamic\Query\OrderBy;
use Statamic\SeoPro\BrokenLinks\ExternalLinkQueryBuilder as QueryBuilder;
use Statamic\Stache\Query\Builder;

class ExternalLinkQueryBuilder extends Builder implements QueryBuilder
{
    protected function getFilteredKeys()
    {
        if (! empty($this->wheres)) {
            return $this->getKeysWithWheres($this->wheres);
        }

        return collect($this->store->paths()->keys());
    }

    protected function getKeysWithWheres($wheres)
    {
        return collect($wheres)->reduce(function (?Collection $ids, array $where): Collection {
            $keys = $where['type'] == 'Nested'
                ? $this->getKeysWithWheres($where['query']->wheres)
                : $this->getKeysWithWhere($where);

            return $this->intersectKeysFromWhereClause($ids, $keys, $where);
        });
    }

    protected function getKeysWithWhere($where)
    {
        $items = app('stache')
            ->store('seo_pro_external_links')
            ->index($where['column'])->items();

        $method = 'filterWhere'.$where['type'];

        return $this->{$method}($items, $where)->keys();
    }

    protected function getOrderKeyValuesByIndex()
    {
        return collect($this->orderBys)->mapWithKeys(function (OrderBy $orderBy): array {
            $items = $this->store->index($orderBy->sort)->items()->all();

            return [$orderBy->sort => $items];
        });
    }
}
