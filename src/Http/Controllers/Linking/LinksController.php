<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Statamic\Facades\Entry;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\SeoPro\Auth\UserAccess;
use Statamic\SeoPro\Blueprints\EntryConfigBlueprint;
use Statamic\SeoPro\Blueprints\LinkBlueprint;
use Statamic\SeoPro\Content\ContentMapper;
use Statamic\SeoPro\Content\LinkReplacement;
use Statamic\SeoPro\Content\LinkReplacer;
use Statamic\SeoPro\Contracts\Content\ContentRetriever;
use Statamic\SeoPro\Contracts\Linking\ConfigurationRepository;
use Statamic\SeoPro\Hooks\CP\EntryLinksIndexQuery;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Concerns\ResolvesPermissions;
use Statamic\SeoPro\Http\Requests\InsertLinkRequest;
use Statamic\SeoPro\Http\Requests\UpdateEntryLinkRequest;
use Statamic\SeoPro\Http\Resources\Links\EntryLinksCollection;
use Statamic\SeoPro\Http\ValuesResponse;
use Statamic\SeoPro\Linking\Config\CollectionConfig;
use Statamic\SeoPro\Models\AutomaticLink;
use Statamic\SeoPro\Models\EntryLink;
use Statamic\SeoPro\Models\EntryLink as EntryLinksModel;
use Statamic\SeoPro\Reporting\Linking\ReportBuilder;

class LinksController extends CpController
{
    use MergesBlueprintFields, QueriesFilters, ResolvesPermissions;

    public function __construct(
        Request $request,
        protected readonly ConfigurationRepository $configurationRepository,
        protected readonly ReportBuilder $reportBuilder,
        protected readonly ContentRetriever $contentRetriever,
        protected readonly LinkReplacer $linkReplacer,
        protected readonly ContentMapper $contentMapper,
    ) {
        parent::__construct($request);
    }

    protected function assertCanAccessEntry($entry): void
    {
        abort_if($entry == null, 404);
        abort_unless(User::current()->can('view', $entry), 403);
    }

    protected function mergeEntryConfigBlueprint(array $target = []): array
    {
        return $this->mergeBlueprintIntoContext(
            EntryConfigBlueprint::make(),
            $target,
            callback: function (&$values) {
                $values['can_be_suggested'] = true;
                $values['include_in_reporting'] = true;
            }
        );
    }

    protected function makeDashboardResponse(string $entryId, string $tab, string $title)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        $baseReport = $this->reportBuilder
            ->forUser(User::current())
            ->getBaseReport(Entry::findOrFail($entryId));

        return view('seo-pro::linking.dashboard', $this->mergeEntryConfigBlueprint([
            'report' => $baseReport,
            'tab' => $tab,
            'title' => $title,
            'can_edit' => User::current()->can('edit', $entry),
        ]));
    }

    public function index(Request $request)
    {
        $site = $request->site ? Site::get($request->site) : Site::selected();

        return view('seo-pro::linking.index', $this->mergeEntryConfigBlueprint([
            'site' => $site->handle(),
            'filters' => Scope::filters('seo_pro.links', $this->makeFiltersContext()),
            ...$this->getLinkPermissions(),
        ]));
    }

    public function getLink(string $link)
    {
        return new ValuesResponse(
            EntryConfigBlueprint::make(),
            EntryLink::where('entry_id', $link)->firstOrFail()->toArray(),
        );
    }

    public function updateLink(UpdateEntryLinkRequest $request, string $link)
    {
        /** @var EntryLink $entryLink */
        $entryLink = EntryLink::where('entry_id', $link)->firstOrFail();

        $entryLink->can_be_suggested = $request->get('can_be_suggested');
        $entryLink->include_in_reporting = $request->get('include_in_reporting');

        $entryLink->save();
    }

    public function resetEntrySuggestions(string $link)
    {
        /** @var EntryLinksModel $entryLink */
        $entryLink = EntryLink::where('entry_id', $link)->firstOrFail();

        $entryLink->ignored_entries = [];
        $entryLink->ignored_phrases = [];

        $entryLink->save();
    }

    public function filter(FilteredRequest $request)
    {
        $sortField = $this->getSortField();
        $sortDirection = request('order', 'asc');

        $query = $this->indexQuery();

        $activeFilterBadges = $this->queryFilters($query, $request->filters);

        if ($sortField) {
            $query->orderBy($sortField, $sortDirection);
        }

        if (request('search')) {
            $query->where(function (Builder $q) {
                $q->where('analyzed_content', 'like', '%'.request('search').'%')
                    ->orWhere('cached_title', 'like', '%'.request('search').'%')
                    ->orWhere('cached_uri', 'like', '%'.request('search').'%');
            });
        }

        $links = (new EntryLinksIndexQuery($query))->paginate(request('perPage'));

        return (new EntryLinksCollection($links))
            ->blueprint(LinkBlueprint::make())
            ->additional(['meta' => [
                'activeFilterBadges' => $activeFilterBadges,
            ]]);
    }

    public function getSuggestions($entryId)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        if (request()->ajax()) {
            return $this->reportBuilder
                ->forUser(User::current())
                ->getSuggestionsReport(
                    $entry,
                    config('statamic.seo-pro.linking.suggestions.result_limit', 10),
                    config('statamic.seo-pro.linking.suggestions.related_entry_limit', 20),
                )->suggestions();
        }

        return $this->makeDashboardResponse(
            $entryId,
            'suggestions',
            __('seo-pro::messages.link_suggestions')
        );
    }

    public function getLinkFieldDetails($entryId, $fieldPath)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        return [
            'field_names' => $this->contentMapper->getFieldNames($fieldPath),
        ];
    }

    public function getRelatedContent($entryId)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        if (request()->ajax()) {
            return $this->reportBuilder
                ->forUser(User::current())
                ->getRelatedContentReport(
                    $entry,
                    config('statamic.seo-pro.linking.suggestions.related_entry_limit', 20),
                )
                ->getRelated();
        }

        return $this->makeDashboardResponse(
            $entryId,
            'related',
            __('seo-pro::messages.related_content')
        );
    }

    public function getInternalLinks($entryId)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        if (request()->ajax()) {
            return $this->reportBuilder
                ->forUser(User::current())
                ->getInternalLinks($entry)->getLinks();
        }

        return $this->makeDashboardResponse(
            $entryId,
            'internal',
            __('seo-pro::messages.internal_links')
        );
    }

    public function getExternalLinks($entryId)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        if (request()->ajax()) {
            return $this->reportBuilder
                ->forUser(User::current())
                ->getExternalLinks($entry)->getLinks();
        }

        return $this->makeDashboardResponse(
            $entryId,
            'external',
            __('seo-pro::messages.external_links')
        );
    }

    public function getInboundInternalLinks($entryId)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        if (request()->ajax()) {
            return $this
                ->reportBuilder
                ->forUser(User::current())
                ->getInboundInternalLinks($entry)
                ->getLinks();
        }

        return $this->makeDashboardResponse(
            $entryId,
            'inbound',
            __('seo-pro::messages.inbound_internal_links')
        );
    }

    public function getSections($entryId)
    {
        $entry = Entry::find($entryId);
        $this->assertCanAccessEntry($entry);

        return $this->contentRetriever->getSections($entry);
    }

    protected function makeReplacementFromRequest(): LinkReplacement
    {
        return new LinkReplacement(
            request('phrase') ?? '',
            request('section') ?? '',
            request('target') ?? '',
            request('field') ?? ''
        );
    }

    public function checkLinkReplacement(InsertLinkRequest $request)
    {
        $entry = Entry::findOrFail(request('entry'));

        if (! User::current()->can('edit', $entry)) {
            abort(403);
        }

        return [
            'can_replace' => $this->linkReplacer->canReplace(
                $entry,
                $this->makeReplacementFromRequest(),
            ),
        ];
    }

    protected function getSiteHandle($entry, $request): string
    {
        if ($site = $entry?->site()) {
            return $site->handle();
        }

        if ($request->site) {
            return $request->site->handle();
        }

        return Site::selected()->handle();
    }

    public function insertLink(InsertLinkRequest $request)
    {
        $entry = Entry::findOrFail(request('entry'));

        if (! User::current()->can('edit', $entry)) {
            abort(403);
        }

        if ($request->get('auto_link', false) === true && request('auto_link_entry')) {
            $autoLinkEntry = Entry::find(request('auto_link_entry'));

            $link = new AutomaticLink;
            $link->site = $this->getSiteHandle($entry, $request);
            $link->is_active = true;
            $link->link_text = request('phrase');
            $link->link_target = $autoLinkEntry->uri();
            $link->entry_id = request('auto_link_entry');

            $link->save();
        }

        $this->linkReplacer->replaceLink(
            $entry,
            $this->makeReplacementFromRequest(),
        );
    }

    private function getSortField(): string
    {
        return request('sort', 'cached_title');
    }

    protected function indexQuery(): Builder
    {
        $disabledCollections = $this->configurationRepository->getDisabledCollections();

        return EntryLinksModel::query()
            ->whereIn('collection', UserAccess::getCollectionsForCurrentUser()->all())
            ->whereNotIn('collection', $disabledCollections);
    }

    protected function makeFiltersContext(): array
    {
        $visibleCollections = UserAccess::getCollectionsForCurrentUser();

        $collections = $this->configurationRepository
            ->getCollections()
            ->filter(function (CollectionConfig $config) use ($visibleCollections) {
                return $config->linkingEnabled && $visibleCollections->contains($config->handle);
            })
            ->map(fn (CollectionConfig $config) => $config->handle)
            ->all();

        return [
            'collections' => $collections,
            'sites' => UserAccess::getSitesForCurrentUser()->all(),
        ];
    }
}
