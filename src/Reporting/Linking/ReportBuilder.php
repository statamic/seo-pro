<?php

namespace Statamic\SeoPro\Reporting\Linking;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as EntryApi;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Models\EntryLink;
use Statamic\SeoPro\Reporting\Linking\Concerns\ResolvesSimilarItems;
use Statamic\SeoPro\TextProcessing\Config\ConfigurationRepository;
use Statamic\SeoPro\TextProcessing\Config\SiteConfig;
use Statamic\SeoPro\TextProcessing\Keywords\KeywordsRepository;
use Statamic\SeoPro\TextProcessing\Similarity\ResolverOptions;
use Statamic\SeoPro\TextProcessing\Suggestions\SuggestionEngine;

class ReportBuilder
{
    use ResolvesSimilarItems;

    private ?EntryLink $lastLinks = null;

    private ?SiteConfig $siteConfig = null;

    public function __construct(
        protected readonly ConfigurationRepository $configurationRepository,
        protected readonly SuggestionEngine $suggestionEngine,
        protected readonly EntryEmbeddingsRepository $embeddingsRepository,
        protected readonly KeywordsRepository $keywordsRepository,
    ) {}

    protected function fillBaseReportData(Entry $entry, BaseLinkReport $report): void
    {
        // Fetch overview data.
        $overviewData = EntryLink::query()->where('entry_id', $entry->id())->first();

        $this->lastLinks = $overviewData;

        $report->internalLinkCount($overviewData?->internal_link_count ?? 0);
        $report->externalLinkCount($overviewData?->external_link_count ?? 0);
        $report->inboundInternalLinkCount($overviewData?->inbound_internal_link_count ?? 0);

        $siteConfig = $this->configurationRepository->getSiteConfiguration($entry->site()?->handle() ?? 'default');

        $report->minInternalLinkCount($siteConfig->minInternalLinks);
        $report->maxInternalLinkCount($siteConfig->maxInternalLinks);
        $report->minExternalLinkCount($siteConfig->minExternalLinks);
        $report->maxExternalLinkCount($siteConfig->maxExternalLinks);

        $report->entry($entry);
    }

    public function getBaseReport(Entry $entry): BaseLinkReport
    {
        $baseReport = new BaseLinkReport;

        $this->fillBaseReportData($entry, $baseReport);

        return $baseReport;
    }

    protected function getResolvedSimilarItems(Entry $entry, int $resultLimit, int $relatedEntryLimit, ?ResolverOptions $options = null): Collection
    {
        return $this->addKeywordsToResults(
            $entry,
            $this->findSimilarTo($entry, $relatedEntryLimit, $options),
            $options
        )->take($resultLimit);
    }

    public function getSuggestionsReport(Entry $entry, int $resultLimit = 10, int $similarEntryLimit = 20): SuggestionsReport
    {
        $report = new SuggestionsReport;

        $siteConfig = $this->configurationRepository->getSiteConfiguration($entry->site()?->handle() ?? 'default');

        $resolverOptions = new ResolverOptions(
            keywordThreshold: $siteConfig->keywordThreshold / 100,
            preventCircularLinks: $siteConfig->preventCircularLinks,
        );

        $suggestions = $this->suggestionEngine
            ->withResults($this->getResolvedSimilarItems($entry, $similarEntryLimit, $resultLimit, $resolverOptions))
            ->suggest($entry);

        $this->fillBaseReportData($entry, $report);

        $report->suggestions($suggestions->all());

        return $report;
    }

    public function getRelatedContentReport(Entry $entry, int $relatedEntryLimit = 20): RelatedContentReport
    {
        $report = new RelatedContentReport;

        $report->relatedContent(
            $this->getResolvedSimilarItems(
                $entry,
                $relatedEntryLimit,
                $relatedEntryLimit,
                new ResolverOptions(keywordThreshold: -1)
            )->all()
        );

        $this->fillBaseReportData($entry, $report);

        return $report;
    }

    public function getExternalLinks(Entry $entry): ExternalLinksReport
    {
        $report = new ExternalLinksReport;
        $this->fillBaseReportData($entry, $report);

        if (! $this->lastLinks) {
            return $report;
        }

        $report->externalLinks($this->lastLinks->external_links);

        return $report;
    }

    public function getInboundInternalLinks(Entry $entry): InternalLinksReport
    {
        $report = new InternalLinksReport;
        $this->fillBaseReportData($entry, $report);

        if (! $this->lastLinks) {
            return $report;
        }

        $targetUri = $entry->uri;
        /** @var EntryLink[] $entryLinks */
        $entryLinks = EntryLink::query()->whereJsonContains('internal_links', $targetUri)->get();
        $matches = [];
        $lookupIds = [];

        foreach ($entryLinks as $link) {
            if (str_starts_with($link, '#')) {
                continue;
            }

            if ($link->id === $this->lastLinks->id) {
                continue;
            }

            if (in_array($targetUri, $link->internal_links)) {
                $lookupIds[$link->entry_id] = 1;

                $matches[] = [
                    'entry_id' => $link->entry_id,
                    'uri' => $targetUri,
                ];
            }
        }

        if (! $matches) {
            return $report;
        }

        $entries = EntryApi::query()
            ->whereIn('id', array_keys($lookupIds))
            ->get()
            ->keyBy('id')
            ->all();

        $results = [];

        foreach ($matches as $match) {
            $results[] = [
                'entry' => $entries[$match['entry_id']] ?? null,
                'uri' => $match['uri'],
            ];
        }

        $report->internalLinks($results);

        return $report;
    }

    public function getInternalLinks(Entry $entry): InternalLinksReport
    {
        $report = new InternalLinksReport;
        $this->fillBaseReportData($entry, $report);

        if (! $this->lastLinks) {
            return $report;
        }

        $toLookup = [];
        $uris = [];

        foreach ($this->lastLinks->internal_links as $link) {
            if (str_starts_with($link, '#')) {
                continue;
            }

            $uri = Str::before($link, '#');

            if (str_ends_with($uri, '/')) {
                $uri = mb_substr($uri, 0, mb_strlen($uri) - 1);
            }

            $toLookup[] = [
                'original' => $link,
                'uri' => $uri,
            ];

            $uris[] = $uri;
        }

        $entries = EntryApi::query()
            ->whereIn('uri', $uris)
            ->get()
            ->keyBy('uri')
            ->all();

        $report->internalLinks(
            collect($toLookup)->map(function ($link) use ($entries) {
                return [
                    'entry' => $entries[$link['uri']] ?? null,
                    'uri' => $link['original'],
                ];
            })->all()
        );

        return $report;
    }
}
