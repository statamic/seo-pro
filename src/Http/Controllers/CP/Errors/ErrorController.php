<?php

namespace Statamic\SeoPro\Http\Controllers\CP\Errors;

use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Http\Resources\Redirects\Errors;
use Statamic\SeoPro\Redirects\Error;

class ErrorController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        $this->authorize('index', Error::class);

        if ($request->wantsJson()) {
            $query = $this->indexQuery();

            $activeFilterBadges = $this->queryFilters($query, $request->filters);

            $sortField = OrderBy::column(request('sort'));
            $sortDirection = request('order', 'asc');

            if (! $sortField && ! request('search')) {
                $sortField = 'last_hit_at';
                $sortDirection = 'desc';
            }

            if ($sortField) {
                $query->orderBy($sortField, $sortDirection);
            }

            $errors = $query->paginate(request('perPage'));

            return (new Errors($errors))
                ->blueprint(Facades\Error::blueprint())
                ->columnPreferenceKey('seo-pro.errors.columns')
                ->additional(['meta' => [
                    'activeFilterBadges' => $activeFilterBadges,
                ]]);
        }

        $blueprint = Facades\Error::blueprint();

        $columns = $blueprint
            ->columns()
            ->put('create_redirect', Column::make('create_redirect')
                ->label('')
                ->listable(true)
                ->visible(true)
                ->defaultVisibility(true)
                ->defaultOrder($blueprint->columns()->count() + 1)
                ->sortable(false))
            ->setPreferred('seo-pro.errors.columns')
            ->rejectUnlisted()
            ->values();

        return Inertia::render('seo-pro::Errors/Index', [
            'blueprint' => $blueprint,
            'columns' => $columns,
            'filters' => Scope::filters('errors'),
        ]);
    }

    protected function indexQuery()
    {
        $query = Facades\Error::query();

        if (Site::multiEnabled()) {
            $query->whereIn('site', Site::authorized()->map->handle()->all());
        }

        if ($search = request('search')) {
            $query->where('url', 'LIKE', '%'.$search.'%');
        }

        return $query;
    }
}
