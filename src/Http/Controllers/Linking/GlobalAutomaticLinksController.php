<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\Blueprints\GlobalAutomaticLinksBlueprint;
use Statamic\SeoPro\Hooks\CP\AutomaticLinksIndexQuery;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Concerns\ResolvesPermissions;
use Statamic\SeoPro\Http\Resources\Links\GlobalAutomaticLinksCollection;
use Statamic\SeoPro\Http\ValuesResponse;
use Statamic\SeoPro\Models\AutomaticLink;

class GlobalAutomaticLinksController extends CpController
{
    use MergesBlueprintFields, QueriesFilters, ResolvesPermissions;

    public function index(Request $request)
    {
        abort_unless(User::current()->can('edit global links'), 403);

        $site = $request->site ? Site::get($request->site) : Site::selected();

        return view('seo-pro::linking.automatic', $this->mergeBlueprintIntoContext(
            GlobalAutomaticLinksBlueprint::make(),
            [
                'site' => $site->handle(),
                ...$this->getLinkPermissions(),
            ],
        ));
    }

    public function getValues($automaticLink)
    {
        abort_unless(User::current()->can('edit global links'), 403);

        return new ValuesResponse(
            GlobalAutomaticLinksBlueprint::make(),
            AutomaticLink::findOrFail($automaticLink)->toArray(),
        );
    }

    public function create(Request $request)
    {
        abort_unless(User::current()->can('edit global links'), 403);

        GlobalAutomaticLinksBlueprint::make()
            ->fields()
            ->addValues($request->all())
            ->validate();

        $data = $request->all();

        if (! isset($data['is_active'])) {
            $data['is_active'] = false;
        }

        $link = new AutomaticLink($data);

        $link->save();
    }

    public function update(Request $request, $automaticLink)
    {
        abort_unless(User::current()->can('edit global links'), 403);

        GlobalAutomaticLinksBlueprint::make()
            ->fields()
            ->addValues($request->all())
            ->validate();

        /** @var AutomaticLink $link */
        $link = AutomaticLink::findOrFail($automaticLink);

        $link->link_target = request('link_target');
        $link->link_text = request('link_text');
        $link->entry_id = request('entry_id');
        $link->is_active = request('is_active', false);

        $link->save();
    }

    public function filter(FilteredRequest $request)
    {
        abort_unless(User::current()->can('edit global links'), 403);

        $sortField = $this->getSortField();
        $sortDirection = request('order', 'asc');

        $query = $this->indexQuery();

        $activeFilterBadges = $this->queryFilters($request, $request->filters);

        if ($sortField) {
            $query->orderBy($sortField, $sortDirection);
        }

        $links = (new AutomaticLinksIndexQuery($query))->paginate(request('perPage'));

        return (new GlobalAutomaticLinksCollection($links))
            ->blueprint(GlobalAutomaticLinksBlueprint::make())
            ->additional(['meta' => [
                'activeFilterBadges' => $activeFilterBadges,
            ]]);
    }

    public function delete($automaticLink)
    {
        abort_unless(User::current()->can('edit global links'), 403);

        AutomaticLink::find($automaticLink)?->delete();
    }

    private function getSortField(): string
    {
        return request('sort', 'link_text');
    }

    protected function indexQuery()
    {
        return AutomaticLink::query();
    }
}
