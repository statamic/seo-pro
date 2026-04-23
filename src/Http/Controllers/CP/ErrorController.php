<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\CP\PublishForm;
use Statamic\Facades\Scope;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Http\Resources\Redirects\Errors;
use Statamic\SeoPro\Http\Resources\Redirects\Redirects;
use Statamic\SeoPro\Redirects\Error;

class ErrorController extends CpController
{
    public function index(FilteredRequest $request)
    {
        $this->authorize('index', Error::class);

        if ($request->wantsJson()) {
            $query = $this->indexQuery();

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
                ->columnPreferenceKey('seo-pro.errors.columns');
        }

        $blueprint = Facades\Error::blueprint();

        $columns = $blueprint
            ->columns()
            ->put('actions', Column::make('actions')
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
        ]);
    }

    protected function indexQuery()
    {
        $query = Facades\Error::query();

        if ($search = request('search')) {
            $query->where('url', 'LIKE', '%'.$search.'%');
        }

        return $query;
    }
}
