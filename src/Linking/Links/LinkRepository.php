<?php

namespace Statamic\SeoPro\Linking\Links;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as EntryApi;
use Statamic\SeoPro\Contracts\Content\ContentRetriever;
use Statamic\SeoPro\Contracts\Linking\Links\LinksRepository as LinkRepositoryContract;
use Statamic\SeoPro\Events\InternalLinksUpdated;
use Statamic\SeoPro\Models\CollectionLinkSettings;
use Statamic\SeoPro\Models\EntryLink;
use Statamic\SeoPro\Models\SiteLinkSetting;

readonly class LinkRepository implements LinkRepositoryContract
{
    public function __construct(
        protected ContentRetriever $contentRetriever,
    ) {}

    public function ignoreSuggestion(IgnoredSuggestion $suggestion): void
    {
        if ($suggestion->action === 'ignore_entry') {
            $this->ignoreEntrySuggestion($suggestion);
        } elseif ($suggestion->action === 'ignore_phrase') {
            $this->ignorePhraseSuggestion($suggestion);
        }
    }

    protected function whenEntryExists(string $entryId, callable $callback): void
    {
        $entry = EntryApi::find($entryId);

        if (! $entry) {
            return;
        }

        $callback($entry);
    }

    protected function ignorePhraseSuggestion(IgnoredSuggestion $suggestion): void
    {
        if ($suggestion->scope === 'all_entries') {
            $this->addIgnoredPhraseToSite($suggestion);
        } elseif ($suggestion->scope === 'entry') {
            $this->whenEntryExists($suggestion->entry, fn ($entry) => $this->addIgnoredPhraseToEntry($entry, $suggestion->phrase));
        }
    }

    protected function addIgnoredPhraseToSite(IgnoredSuggestion $suggestion): void
    {
        /** @var SiteLinkSetting $siteSettings */
        $siteSettings = SiteLinkSetting::query()->firstOrNew(['site' => $suggestion->site]);

        $phrase = trim($suggestion->phrase);

        if (mb_strlen($phrase) === 0) {
            return;
        }

        $phrases = $siteSettings->ignored_phrases ?? [];

        if (in_array($phrase, $phrases)) {
            return;
        }

        $phrases[] = $phrase;

        $siteSettings->ignored_phrases = $phrases;

        $siteSettings->saveQuietly();
    }

    protected function ignoreEntrySuggestion(IgnoredSuggestion $suggestion): void
    {
        if ($suggestion->scope === 'all_entries') {
            $this->whenEntryExists($suggestion->ignoredEntry, fn ($entry) => $this->ignoreEntry($entry));
        } elseif ($suggestion->scope === 'entry') {
            $this->whenEntryExists($suggestion->entry, fn ($entry) => $this->addIgnoredEntryToEntry($entry, $suggestion->ignoredEntry));
        }
    }

    protected function getEntryLink(Entry $entry): ?EntryLink
    {
        $entryLink = EntryLink::query()->where('entry_id', $entry->id())->first();

        if (! $entryLink) {
            $entryLink = $this->scanEntry($entry);
        }

        return $entryLink;
    }

    protected function updateEntryLink(Entry $entry, callable $callback): void
    {
        $entryLink = $this->getEntryLink($entry);

        if (! $entryLink) {
            return;
        }

        $callback($entryLink);
    }

    protected function addIgnoredEntryToEntry(Entry $entry, string $ignoredEntryId): void
    {
        $this->updateEntryLink($entry, function (EntryLink $entryLink) use ($ignoredEntryId) {
            $ignoredEntries = $entryLink->ignored_entries ?? [];

            if (in_array($ignoredEntryId, $ignoredEntries)) {
                return;
            }

            $ignoredEntries[] = $ignoredEntryId;

            $entryLink->ignored_entries = $ignoredEntries;

            $entryLink->saveQuietly();
            $entryLink->saveQuietly();
        });
    }

    protected function addIgnoredPhraseToEntry(Entry $entry, string $phrase): void
    {
        $this->updateEntryLink($entry, function (EntryLink $entryLink) use ($phrase) {
            $phrase = trim($phrase);

            if (mb_strlen($phrase) === 0) {
                return;
            }

            $ignoredPhrases = $entryLink->ignored_phrases ?? [];

            if (in_array($phrase, $ignoredPhrases)) {
                return;
            }

            $ignoredPhrases[] = $phrase;

            $entryLink->ignored_phrases = $ignoredPhrases;

            $entryLink->saveQuietly();
        });
    }

    protected function ignoreEntry(Entry $entry): void
    {
        $entryLink = $this->getEntryLink($entry);

        if (! $entryLink) {
            return;
        }

        $entryLink->can_be_suggested = false;

        $entryLink->saveQuietly();
    }

    public function scanEntry(Entry $entry, ?LinkScanOptions $options = null): ?EntryLink
    {
        if (! $options) {
            $options = new LinkScanOptions;
        }

        /** @var \Statamic\SeoPro\Models\EntryLink $entryLinks */
        $entryLinks = EntryLink::query()->firstOrNew(['entry_id' => $entry->id()]);
        $linkContent = $this->contentRetriever->getContent($entry, false);
        $contentMapping = $this->contentRetriever->getContentMapping($entry);
        $linkResults = LinkCrawler::getLinkResults($linkContent);
        $collection = $entry->collection()->handle();
        $site = $entry->site()->handle();

        $uri = $entry->uri;

        $entryLinks->cached_title = $entry->title ?? $uri ?? '';
        $entryLinks->cached_uri = $uri ?? '';
        $entryLinks->site = $site;
        $entryLinks->analyzed_content = $linkContent;
        $entryLinks->content_mapping = $contentMapping;
        $entryLinks->collection = $collection;
        $entryLinks->external_link_count = count($linkResults->externalLinks());
        $entryLinks->internal_link_count = count($linkResults->internalLinks());
        $entryLinks->content_hash = $this->contentRetriever->hashContent($linkContent);

        if (! $entryLinks->exists) {
            $entryLinks->ignored_entries = [];
            $entryLinks->ignored_phrases = [];
            $entryLinks->normalized_internal_links = [];
            $entryLinks->normalized_external_links = [];
        }

        $entryLinks->inbound_internal_link_count = 0;

        $externalLinks = collect($linkResults->externalLinks())->pluck('href');
        $internalLinks = collect($linkResults->internalLinks())->pluck('href');

        $entryLinks->external_links = $externalLinks->all();
        $entryLinks->internal_links = $internalLinks->all();
        $entryLinks->normalized_external_links = $this->normalizeLinks($externalLinks);
        $entryLinks->normalized_internal_links = $this->normalizeLinks($internalLinks);

        $linkChangeSet = null;

        if ($options->withInternalChangeSets && $entryLinks->isDirty('internal_links')) {
            $linkChangeSet = $this->makeLinkChangeSet(
                $entryLinks->entry_id,
                $entryLinks->getOriginal('internal_links') ?? [],
                $entryLinks->internal_links ?? [],
            );
        }

        $entryLinks->saveQuietly();

        if ($linkChangeSet) {
            InternalLinksUpdated::dispatch($linkChangeSet);
        }

        return $entryLinks;
    }

    protected function makeLinkChangeSet(string $entryId, array $original, array $new): LinkChangeSet
    {
        return new LinkChangeSet(
            $entryId,
            array_diff($new, $original),
            array_diff($original, $new),
        );
    }

    protected function normalizeLinks(Collection $links): array
    {
        return $links->map(fn (string $link) => $this->normalizeLink($link))->unique()->values()->all();
    }

    protected function normalizeLink(string $link): string
    {
        while (Str::contains($link, ['?', '#', '&'])) {
            $link = str($link)
                ->before('?')
                ->before('#')
                ->before('&')
                ->value();
        }

        return $link;
    }

    public function isLinkingEnabledForEntry(Entry $entry): bool
    {
        /** @var CollectionLinkSettings $collectionSetting */
        $collectionSetting = CollectionLinkSettings::query()->where('collection', $entry->collection()->handle())->first();

        if ($collectionSetting && ! $collectionSetting->linking_enabled) {
            return false;
        }

        /** @var \Statamic\SeoPro\Models\EntryLink $entryLink */
        $entryLink = EntryLink::query()->where('entry_id', $entry->id())->first();

        if ($entryLink && ! $entryLink->can_be_suggested) {
            return false;
        }

        return true;
    }

    public function deleteLinksForEntry(string $entryId): void
    {
        EntryLink::query()->where('entry_id', $entryId)->delete();
    }

    public function deleteLinksForSite(string $handle): void
    {
        EntryLink::query()->where('site', $handle)->delete();
    }

    public function deleteLinksForCollection(string $handle): void
    {
        EntryLink::query()->where('collection', $handle)->delete();
    }
}
