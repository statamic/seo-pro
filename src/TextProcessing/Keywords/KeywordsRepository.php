<?php

namespace Statamic\SeoPro\TextProcessing\Keywords;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Content\ContentRemoval;
use Statamic\SeoPro\Contracts\Content\ContentRetriever;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordRetriever;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordsRepository as KeywordsRepositoryContract;
use Statamic\SeoPro\TextProcessing\Concerns\ChecksForContentChanges;
use Statamic\SeoPro\TextProcessing\Models\EntryKeyword;
use Statamic\SeoPro\TextProcessing\Models\EntryLink;
use Statamic\SeoPro\TextProcessing\Models\SiteLinkSetting;
use Statamic\SeoPro\TextProcessing\Queries\EntryQuery;

class KeywordsRepository implements KeywordsRepositoryContract
{
    use ChecksForContentChanges;

    /** @var array<string, EntryKeyword> */
    protected array $keywordInstanceCache = [];

    public function __construct(
        protected readonly KeywordRetriever $keywordRetriever,
        protected readonly ContentRetriever $contentRetriever,
    ) {}

    public function generateKeywordsForAllEntries()
    {
        EntryQuery::query()->chunk(100, function ($entries) {
            $entryIds = $entries->pluck('id')->all();
            $this->fillKeywordInstanceCache($entryIds);

            /** @var array<string, EntryLink> $entryLinks */
            $entryLinks = EntryLink::query()
                ->whereIn('entry_id', $entryIds)
                ->get()
                ->keyBy('entry_id')
                ->all();

            foreach ($entries as $entry) {
                $entryId = $entry->id();

                // If the cached content is still good, let's skip generating keywords.
                if (
                    array_key_exists($entryId, $this->keywordInstanceCache) &&
                    array_key_exists($entryId, $entryLinks) &&
                    $entryLinks[$entryId]->content_hash === $this->keywordInstanceCache[$entryId]->content_hash
                ) {
                    continue;
                }

                $this->generateKeywordsForEntry($entry);
            }

            unset($entryLinks);
            $this->clearKeywordInstanceCache();
        });
    }

    protected function fillKeywordInstanceCache(array $entryIds): void
    {
        $this->keywordInstanceCache = EntryKeyword::query()
            ->whereIn('entry_id', $entryIds)
            ->get()
            ->keyBy('entry_id')
            ->all();
    }

    protected function clearKeywordInstanceCache(): void
    {
        $this->keywordInstanceCache = [];
    }

    protected function expandKeywords(array $keywords, $stopWords = []): array
    {
        $returnKeywords = [];

        foreach ($keywords as $keyword) {
            $returnKeywords[] = $keyword;

            if (! Str::contains($keyword, ' ')) {
                continue;
            }

            foreach (explode(' ', $keyword) as $newKeyword) {
                if (is_numeric($newKeyword) || mb_strlen($newKeyword) <= 2) {
                    continue;
                }

                if (in_array($newKeyword, $stopWords)) {
                    continue;
                }

                $returnKeywords[] = $newKeyword;
            }
        }

        return $returnKeywords;
    }

    protected function getMetaKeywords(Entry $entry, $stopWords = [])
    {
        $uri = str($entry->uri ?? '')
            ->afterLast('/')
            ->swap([
                '-' => ' ',
            ])->value();

        return [
            'title' => $this->expandKeywords($this->keywordRetriever->extractKeywords($entry->title ?? '')->all(), $stopWords),
            'uri' => $this->expandKeywords($this->keywordRetriever->extractKeywords($uri)->all(), $stopWords),
        ];
    }

    protected function getEntryKeyword(string $entryId): EntryKeyword
    {
        if (array_key_exists($entryId, $this->keywordInstanceCache)) {
            return $this->keywordInstanceCache[$entryId];
        }

        return EntryKeyword::firstOrNew(['entry_id' => $entryId]);
    }

    public function generateKeywordsForEntry(Entry $entry)
    {
        $id = $entry->id();

        $keywords = $this->getEntryKeyword($id);

        $content = $this->contentRetriever->getContent($entry, false);

        if ($this->isContentSame($keywords, $content)) {
            return;
        }

        $contentHash = $this->contentRetriever->hashContent($content);

        $content = $this->contentRetriever->stripTags($content);

        // Remove some extra stuff we wouldn't want to ultimately link to/suggest.
        $content = ContentRemoval::removeHeadings($content);
        $content = ContentRemoval::removePreCodeBlocks($content);

        $collection = $entry->collection()->handle();
        $site = $entry->site()->handle();
        $blueprint = $entry->blueprint()->handle();

        $keywords->collection = $collection;
        $keywords->site = $site;
        $keywords->blueprint = $blueprint;

        $keywords->content_hash = $contentHash;

        $stopWords = $this->keywordRetriever->inLocale($entry->site()?->locale() ?? 'en_US')->getStopWords();

        $keywords->meta_keywords = $this->getMetaKeywords($entry, $stopWords);
        $keywords->content_keywords = $this->keywordRetriever->extractRankedKeywords($content)->sortDesc()->take(30)->all();

        $keywords->saveQuietly();
    }

    public function deleteKeywordsForEntry(string $entryId)
    {
        EntryKeyword::query()->where('entry_id', $entryId)->delete();
    }

    /**
     * @return array<string, EntryKeyword>
     */
    public function getKeywordsForEntries(array $entryIds): array
    {
        return EntryKeyword::query()->whereIn('entry_id', $entryIds)->get()->keyBy('entry_id')->all();
    }

    public function getIgnoredKeywordsForEntry(Entry $entry): array
    {
        $site = $entry->site()?->handle() ?? 'default';
        $ignoredKeywords = [];

        /** @var EntryLink $entryLink */
        $entryLink = EntryLink::where('entry_id', $entry->id())->first();

        /** @var SiteLinkSetting $siteSettings */
        $siteSettings = SiteLinkSetting::where('site', $site)->first();

        if ($entryLink) {
            $ignoredKeywords = array_merge($ignoredKeywords, $entryLink->ignored_phrases ?? []);
        }

        if ($siteSettings) {
            $ignoredKeywords = array_merge($ignoredKeywords, $siteSettings->ignored_phrases ?? []);
        }

        return $ignoredKeywords;
    }

    public function deleteKeywordsForSite(string $handle): void
    {
        EntryKeyword::query()->where('site', $handle)->delete();
    }

    public function deleteKeywordsForCollection(string $handle): void
    {
        EntryKeyword::query()->where('collection', $handle)->delete();
    }
}
