<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\Blueprints\GlobalAutomaticLinksBlueprint;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Requests\AutomaticLinkRequest;
use Statamic\SeoPro\Http\Resources\Links\AutomaticLinks;
use Statamic\SeoPro\TextProcessing\Models\AutomaticLink;

class GlobalAutomaticLinksController extends CpController
{
    use MergesBlueprintFields, QueriesFilters;

    public function index(Request $request)
    {
        $site = $request->site ? Site::get($request->site) : Site::selected();

        return view('seo-pro::linking.automatic', $this->mergeBlueprintIntoContext(
            GlobalAutomaticLinksBlueprint::blueprint(),
            [
                'site' => $site->handle(),
            ],
        ));
    }

    public function create(AutomaticLinkRequest $request)
    {
        $link = new AutomaticLink($request->all());

        $link->save();
    }

    public function update(AutomaticLinkRequest $request, $automaticLink)
    {
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
        $sortField = $this->getSortField();
        $sortDirection = request('order', 'asc');

        $query = $this->indexQuery();

        $activeFilterBadges = $this->queryFilters($request, $request->filters);

        if ($sortField) {
            $query->orderBy($sortField, $sortDirection);
        }

        $links = $query->paginate(request('perPage'));

        return (new AutomaticLinks($links))
            ->additional(['meta' => [
                'activeFilterBadges' => $activeFilterBadges,
            ]]);
    }

    public function delete($automaticLink)
    {
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
