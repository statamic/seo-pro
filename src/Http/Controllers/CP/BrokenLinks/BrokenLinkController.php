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
use Statamic\SeoPro\Http\Resources\BrokenLinks\BrokenLinks;

class BrokenLinkController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        $this->authorize('index', ExternalLink::class);

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

            return (new BrokenLinks($links))
                ->blueprint(Facades\ExternalLink::blueprint())
                ->columnPreferenceKey('seo-pro.broken-links.columns')
                ->additional(['meta' => [
                    'activeFilterBadges' => $activeFilterBadges,
                ]]);
        }

        $blueprint = Facades\ExternalLink::blueprint();

        $columns = $blueprint
            ->columns()
            ->put('status', Column::make('status')
                ->listable(true)
                ->visible(true)
                ->defaultVisibility(true)
                ->defaultOrder(0))
            ->setPreferred('seo-pro.broken-links.columns')
            ->rejectUnlisted()
            ->values();

        return Inertia::render('seo-pro::BrokenLinks/Index', [
            'blueprint' => $blueprint,
            'columns' => $columns,
            'filters' => Scope::filters('broken-links'),
            'recheckAllUrl' => cp_route('seo-pro.broken-links.recheck-all'),
        ]);
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
