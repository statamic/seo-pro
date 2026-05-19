<?php

namespace Statamic\SeoPro\Http\Controllers\CP\Redirects;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\CP\PublishForm;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Http\Resources\Redirects\Redirects;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\SeoPro\Rules\UniqueRedirectUrl;

class RedirectController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        $this->authorize('index', Redirect::class);

        if ($request->wantsJson()) {
            $query = $this->indexQuery();

            $activeFilterBadges = $this->queryFilters($query, $request->filters);

            $sortField = OrderBy::column(request('sort'));
            $sortDirection = request('order', 'asc');

            if (! $sortField && ! request('search')) {
                $sortField = 'source';
                $sortDirection = 'desc';
            }

            if ($sortField) {
                $query->orderBy($sortField, $sortDirection);
            }

            $redirects = $query->paginate(request('perPage'));

            return (new Redirects($redirects))
                ->blueprint(Facades\Redirect::blueprint())
                ->columnPreferenceKey('seo-pro.redirects.columns')
                ->additional(['meta' => [
                    'activeFilterBadges' => $activeFilterBadges,
                ]]);
        }

        $blueprint = Facades\Redirect::blueprint();

        $columns = $blueprint
            ->columns()
            ->put('status', Column::make('status')
                ->listable(true)
                ->visible(true)
                ->defaultVisibility(true)
                ->defaultOrder($blueprint->columns()->count() + 1)
                ->sortable(false))
            ->setPreferred('seo-pro.redirects.columns')
            ->rejectUnlisted()
            ->values();

        if (Facades\Redirect::query()->count() === 0) {
            return Inertia::render('seo-pro::Redirects/Empty', [
                'createUrl' => cp_route('seo-pro.redirects.create'),
            ]);
        }

        return Inertia::render('seo-pro::Redirects/Index', [
            'blueprint' => $blueprint,
            'columns' => $columns,
            'filters' => Scope::filters('redirects'),
            'canCreate' => User::current()->can('create', Redirect::class),
            'createUrl' => cp_route('seo-pro.redirects.create'),
        ]);
    }

    protected function indexQuery()
    {
        $query = Facades\Redirect::query();

        if (Site::multiEnabled()) {
            $query->whereIn('site', Site::authorized()->map->handle()->all());
        }

        if ($search = request('search')) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('source', 'LIKE', '%'.$search.'%')
                    ->orWhere('destination', 'LIKE', '%'.$search.'%');
            });
        }

        return $query;
    }

    public function create(Request $request)
    {
        $this->authorize('create', Redirect::class);

        $blueprint = Facades\Redirect::blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues(array_filter([
                'source' => $request->query('source'),
            ]))
            ->preProcess();

        return Inertia::render('seo-pro::Redirects/Create', [
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
            'submitUrl' => cp_route('seo-pro.redirects.store'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('store', Redirect::class);

        $siteHandle = Site::selected()->handle();

        $request->validate([
            'source' => [new UniqueRedirectUrl(site: $siteHandle)],
        ]);

        $values = PublishForm::make(Facades\Redirect::blueprint())->submit($request->all());

        $redirect = Facades\Redirect::make()
            ->site($siteHandle)
            ->source(Arr::pull($values, 'source'))
            ->destination(Arr::pull($values, 'destination'))
            ->responseCode(Arr::pull($values, 'response_code'))
            ->enabled(Arr::pull($values, 'enabled'))
            ->data($values);

        $redirect->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function edit(Request $request, Redirect $redirect)
    {
        $this->authorize('view', $redirect);

        $blueprint = Facades\Redirect::blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($redirect->data()->merge([
                'source' => $redirect->source(),
                'destination' => $redirect->destination(),
                'response_code' => $redirect->responseCode(),
                'enabled' => $redirect->enabled(),
            ])->all())
            ->preProcess();

        return Inertia::render('seo-pro::Redirects/Edit', [
            'title' => $redirect->source(),
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
            'submitUrl' => $redirect->updateUrl(),
            'readOnly' => $request->user()->cant('edit', $redirect),
        ]);
    }

    public function update(Request $request, Redirect $redirect)
    {
        $this->authorize('update', $redirect);

        $request->validate([
            'source' => [new UniqueRedirectUrl($redirect->id(), $redirect->site())],
        ]);

        $values = PublishForm::make(Facades\Redirect::blueprint())->submit($request->all());

        $redirect
            ->source(Arr::pull($values, 'source'))
            ->destination(Arr::pull($values, 'destination'))
            ->responseCode(Arr::pull($values, 'response_code'))
            ->enabled(Arr::pull($values, 'enabled'))
            ->data($values);

        $redirect->save();
    }

    public function destroy(Request $request, Redirect $redirect)
    {
        $this->authorize('delete', $redirect);

        $redirect->delete();
    }
}
