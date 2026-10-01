<?php

namespace Statamic\SeoPro\Http\Controllers\CP\DeadLinks;

use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Http\Resources\DeadLinks\DeadLinks;

class DeadLinkController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        $this->authorize('index', Link::class);

        if ($request->wantsJson()) {
            $query = $this->indexQuery();

            $activeFilterBadges = $this->queryFilters($query, $request->filters);

            $sortField = OrderBy::column(request('sort'));
            $sortDirection = request('order', 'asc');

            if (! $sortField && ! request('search')) {
                $sortField = 'status';
            }

            if ($sortField) {
                $query->orderBy($sortField, $sortDirection);
            }

            if ($sortField === 'status') {
                $query->orderBy('failing_since', $sortDirection);
            }

            $links = $query->paginate(request('perPage'));

            return (new DeadLinks($links))
                ->blueprint(Facades\DeadLink::blueprint())
                ->columnPreferenceKey('seo-pro.dead-links.columns')
                ->additional(['meta' => [
                    'activeFilterBadges' => $activeFilterBadges,
                ]]);
        }

        $blueprint = Facades\DeadLink::blueprint();

        $columns = $blueprint
            ->columns()
            ->put('status', Column::make('status')
                ->listable(true)
                ->visible(true)
                ->defaultVisibility(true)
                ->defaultOrder(0))
            ->setPreferred('seo-pro.dead-links.columns')
            ->rejectUnlisted()
            ->values();

        return Inertia::render('seo-pro::DeadLinks/Index', [
            'blueprint' => $blueprint,
            'columns' => $columns,
            'filters' => Scope::filters('dead-links'),
            'canManage' => User::current()->can('manage seo dead links'),
            'recheckAllUrl' => cp_route('seo-pro.dead-links.recheck-all'),
        ]);
    }

    protected function indexQuery()
    {
        $query = Facades\DeadLink::query();

        if (Site::multiEnabled()) {
            $query->whereIn('site', Site::authorized()->map->handle()->all());
        }

        if ($search = request('search')) {
            $query->where('url', 'LIKE', '%'.$search.'%');
        }

        return $query;
    }
}
