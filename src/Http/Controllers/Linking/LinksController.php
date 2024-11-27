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
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Hooks\CP\EntryLinksIndexQuery;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Requests\InsertLinkRequest;
use Statamic\SeoPro\Http\Requests\UpdateEntryLinkRequest;
use Statamic\SeoPro\Http\Resources\Links\EntryLinks;
use Statamic\SeoPro\Models\AutomaticLink;
use Statamic\SeoPro\Models\EntryLink;
use Statamic\SeoPro\Models\EntryLink as EntryLinksModel;
use Statamic\SeoPro\Reporting\Linking\ReportBuilder;
use Statamic\SeoPro\TextProcessing\Config\CollectionConfig;

class LinksController extends CpController
{
    use MergesBlueprintFields, QueriesFilters;

    protected array $sortFieldMappings = [
        'title' => 'cached_title',
        'slug' => 'cached_slug',
    ];

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

    protected function mergeEntryConfigBlueprint(array $target = []): array
    {
        return $this->mergeBlueprintIntoContext(
            EntryConfigBlueprint::blueprint(),
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
        abort_unless($entry, 404);
        abort_unless(User::current()->can('view', $entry), 403);

        return view('seo-pro::linking.dashboard', $this->mergeEntryConfigBlueprint([
            'report' => $this->reportBuilder->getBaseReport(Entry::findOrFail($entryId)),
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
        ]));
    }

    public function getLink(string $link)
    {
        return EntryLink::where('entry_id', $link)->firstOrFail();
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
        /** @var \Statamic\SeoPro\Models\EntryLink $entryLink */
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

        return (new EntryLinks($links))
            ->blueprint(LinkBlueprint::make())
            ->additional(['meta' => [
                'activeFilterBadges' => $activeFilterBadges,
            ]]);
    }

    public function getOverview()
    {
        // TODO: Revisit this.
        $entriesAnalyzed = EntryLinksModel::query()->count();
        $orphanedEntries = EntryLinksModel::query()->where('inbound_internal_link_count', 0)->count();

        $entriesNeedingMoreLinks = EntryLinksModel::query()
            ->where('include_in_reporting', true)
            ->where('internal_link_count', '=', 0)->count();

        return [
            'total' => $entriesAnalyzed,
            'orphaned' => $orphanedEntries,
            'needs_links' => $entriesNeedingMoreLinks,
        ];
    }

    public function getSuggestions($entryId)
    {
        if (request()->ajax()) {
            return $this->reportBuilder
                ->getSuggestionsReport(
                    Entry::findOrFail($entryId),
                    config('statamic.seo-pro.linking.suggestions.result_limit', 10),
                    config('statamic.seo-pro.linking.suggestions.related_entry_limit', 20),
                )->suggestions();
        }

        return $this->makeDashboardResponse($entryId, 'suggestions', 'Link Suggestions');
    }

    public function getLinkFieldDetails($entryId, $fieldPath)
    {
        $entry = Entry::findOrFail($entryId);

        return [
            'field_names' => $this->contentMapper->getFieldNames($fieldPath),
        ];
    }

    public function getRelatedContent($entryId)
    {
        if (request()->ajax()) {
            return $this->reportBuilder
                ->getRelatedContentReport(
                    Entry::findOrFail($entryId),
                    config('statamic.seo-pro.linking.suggestions.related_entry_limit', 20),
                )
                ->forUser(User::current())
                ->getRelated();
        }

        return $this->makeDashboardResponse($entryId, 'related', 'Related Content');
    }

    public function getInternalLinks($entryId)
    {
        if (request()->ajax()) {
            return $this->reportBuilder->getInternalLinks(Entry::findOrFail($entryId))->getLinks();
        }

        return $this->makeDashboardResponse($entryId, 'internal', 'Internal Links');
    }

    public function getExternalLinks($entryId)
    {
        if (request()->ajax()) {
            return $this->reportBuilder->getExternalLinks(Entry::findOrFail($entryId))->getLinks();
        }

        return $this->makeDashboardResponse($entryId, 'external', 'External Links');
    }

    public function getInboundInternalLinks($entryId)
    {
        if (request()->ajax()) {
            return $this->reportBuilder->getInboundInternalLinks(Entry::findOrFail($entryId))->getLinks();
        }

        return $this->makeDashboardResponse($entryId, 'inbound', 'Inbound Internal Links');
    }

    public function getSections($entryId)
    {
        $entry = Entry::find($entryId);

        if (! $entry) {
            return [];
        }

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
        $sortField = request('sort', 'title');

        if (! $sortField) {
            return $sortField;
        }

        $checkField = strtolower($sortField);

        if (array_key_exists($checkField, $this->sortFieldMappings)) {
            $sortField = $this->sortFieldMappings[$checkField];
        }

        return $sortField;
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

        $sites = Site::all()
            ->filter(function ($site) {
                return User::current()->can('view', $site);
            })
            ->map(fn ($site) => $site->handle())
            ->values()
            ->all();

        return [
            'collections' => $collections,
            'sites' => $sites,
        ];
    }
}
