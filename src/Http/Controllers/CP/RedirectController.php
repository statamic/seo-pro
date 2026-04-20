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
                $sortField = 'source_url';
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

        // TODO: Empty state?

        return Inertia::render('seo-pro::Redirects/Index', [
            'blueprint' => $blueprint,
            'columns' => $columns,
            'filters' => Scope::filters('redirects'),
            'createUrl' => cp_route('seo-pro.redirects.create'),
        ]);
    }

    protected function indexQuery()
    {
        $query = Facades\Redirect::query();

        if ($search = request('search')) {
            $query
                ->where('source_url', 'LIKE', '%'.$search.'%')
                ->orWhere('destination_url', 'LIKE', '%'.$search.'%');
        }

        return $query;
    }

    public function create(Request $request)
    {
        $this->authorize('create', Redirect::class);

        return PublishForm::make(Facades\Redirect::blueprint())
            ->icon('moved')
            ->title(__('seo-pro::messages.create_redirect'))
            ->submittingTo(cp_route('seo-pro.redirects.store'), 'POST');
    }

    public function store(Request $request)
    {
        $this->authorize('store', Redirect::class);

        $request->validate(['source_url' => [new UniqueRedirectUrl]]);

        $values = PublishForm::make(Facades\Redirect::blueprint())->submit($request->all());

        $redirect = Facades\Redirect::make()
            ->sourceUrl($values['source_url'])
            ->destinationUrl($values['destination_url'])
            ->responseCode($values['response_code'])
            ->enabled($values['enabled']);

        $redirect->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function edit(Request $request, Redirect $redirect)
    {
        $this->authorize('edit', $redirect);

        return PublishForm::make(Facades\Redirect::blueprint())
            ->icon('moved')
            ->title($redirect->sourceUrl())
            ->values($redirect->data()->merge([
                'source_url' => $redirect->sourceUrl(),
                'destination_url' => $redirect->destinationUrl(),
                'response_code' => $redirect->responseCode(),
                'enabled' => $redirect->enabled(),
            ])->all())
            ->submittingTo($redirect->updateUrl());
    }

    public function update(Request $request, Redirect $redirect)
    {
        $this->authorize('update', $redirect);

        $request->validate(['source_url' => [new UniqueRedirectUrl($redirect->id())]]);

        $values = PublishForm::make(Facades\Redirect::blueprint())->submit($request->all());

        $redirect
            ->sourceUrl($values['source_url'])
            ->destinationUrl($values['destination_url'])
            ->responseCode($values['response_code'])
            ->enabled($values['enabled']);

        $redirect->save();
    }

    public function destroy(Request $request, Redirect $redirect)
    {
        $this->authorize('delete', $redirect);

        $redirect->delete();
    }
}
