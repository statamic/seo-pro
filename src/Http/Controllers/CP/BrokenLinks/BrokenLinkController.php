<?php

namespace Statamic\SeoPro\Http\Controllers\CP\BrokenLinks;

use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Http\Resources\BrokenLinks\ListedLink;

class BrokenLinkController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        $this->authorize('index', ExternalLink::class);

        if ($request->wantsJson()) {
            $query = $this->indexQuery()->whereNotNull('broken_since');
            $unchecked = $this->indexQuery()->whereNull('checked_at');

            $activeFilterBadges = $this->queryFilters($query, $request->filters);
            $this->queryFilters($unchecked, $request->filters);

            $sortField = OrderBy::column(request('sort'));
            $sortDirection = request('order', 'asc');

            if (! $sortField && ! request('search')) {
                $sortField = 'broken_since';
            }

            if ($sortField) {
                $query->orderBy($sortField, $sortDirection);
            }

            $links = $query->paginate(request('perPage'));

            return ListedLink::collection($links)->additional(['meta' => [
                'columns' => $this->columns(),
                'activeFilterBadges' => $activeFilterBadges,
                'uncheckedCount' => $unchecked->count(),
            ]]);
        }

        return Inertia::render('seo-pro::BrokenLinks/Index', [
            'columns' => $this->columns(),
            'filters' => Scope::filters('broken-links'),
            'recheckAllUrl' => cp_route('seo-pro.broken-links.recheck-all'),
        ]);
    }

    private function columns(): array
    {
        return [
            Column::make('url')->label(__('URL')),
            Column::make('broken_since')->label(__('seo-pro::messages.broken_since')),
            Column::make('response')->label(__('seo-pro::messages.response'))->sortable(false),
            Column::make('checked_at')->label(__('seo-pro::messages.last_checked_at')),
        ];
    }

    protected function indexQuery()
    {
        $query = Facades\ExternalLink::query();

        if (Site::multiEnabled()) {
            $query->whereIn('site', Site::authorized()->map->handle()->all());
        }

        if ($search = request('search')) {
            $query->where('url', 'LIKE', '%'.$search.'%');
        }

        return $query;
    }
}
