<?php

namespace Statamic\SeoPro\Reporting\Linking\Concerns;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as EntryApi;
use Statamic\SeoPro\TextProcessing\Keywords\KeywordComparator;
use Statamic\SeoPro\TextProcessing\Models\EntryKeyword;
use Statamic\SeoPro\TextProcessing\Similarity\CosineSimilarity;
use Statamic\SeoPro\TextProcessing\Similarity\ResolverOptions;
use Statamic\SeoPro\TextProcessing\Similarity\Result;
use Statamic\SeoPro\TextProcessing\Vectors\Vector;

trait ResolvesSimilarItems
{
    protected function filterKeywords(array $keywords, array $ignoredKeywords): array
    {
        return array_diff_key($keywords, $ignoredKeywords);
    }

    protected function getOptions(?ResolverOptions $options): ResolverOptions
    {
        if (! $options) {
            return new ResolverOptions;
        }

        return $options;
    }

    protected function convertEntryKeywords(?EntryKeyword $keywords, array $ignoredKeywords): array
    {
        if ($keywords == null) {
            return [
                'title' => [],
                'uri' => [],
                'content' => [],
            ];
        }

        $metaKeywords = $keywords->meta_keywords ?? [];

        return [
            'title' => $this->filterKeywords($metaKeywords['title'] ?? [], $ignoredKeywords),
            'uri' => $this->filterKeywords($metaKeywords['uri'] ?? [], $ignoredKeywords),
            'content' => $this->filterKeywords($keywords->content_keywords ?? [], $ignoredKeywords),
        ];
    }

    protected function findSimilarTo(Entry $entry, int $limit, ?ResolverOptions $options = null): Collection
    {
        $options = $this->getOptions($options);

        $entryId = $entry->id();
        $targetVectors = $this->embeddingsRepository->getEmbeddingsForEntry($entry)?->vector() ?? [];

        $tmpMapping = [];

        /** @var Vector $vector */
        foreach ($this->embeddingsRepository->getRelatedEmbeddingsForEntryLazy($entry, $options) as $vector) {
            if ($vector->id() === $entryId) {
                continue;
            }

            $score = CosineSimilarity::calculate($targetVectors, $vector->vector());

            if ($score <= 0) {
                continue;
            }

            $tmpMapping[$vector->id()] = $score;
        }

        arsort($tmpMapping);
        $tmpMapping = array_slice($tmpMapping, 0, $limit, true);

        /** @var Result[] $results */
        $results = [];

        $entries = EntryApi::query()->whereIn('id', array_keys($tmpMapping))->get()->keyBy('id')->all();

        foreach ($tmpMapping as $id => $score) {
            if (! array_key_exists($id, $entries)) {
                continue;
            }

            $result = new Result;

            $result->entry($entries[$id]);
            $result->score($score);

            $results[$id] = $result;
        }

        unset($entries, $tmpMapping);

        return collect(array_values($results));
    }

    protected function addKeywordsToResults(Entry $entry, Collection $results, ?ResolverOptions $options = null): Collection
    {
        $options = $this->getOptions($options);

        $entryIds = array_merge([$entry->id()], $results->map(fn (Result $result) => $result->entry()->id())->all());
        $ignoredKeywords = array_flip($this->keywordsRepository->getIgnoredKeywordsForEntry($entry));
        $keywords = $this->keywordsRepository->getKeywordsForEntries($entryIds);
        $primaryKeywords = $keywords[$entry->id()] ?? null;

        if (! $primaryKeywords) {
            return collect();
        }

        $primaryKeywords = $this->convertEntryKeywords($primaryKeywords, $ignoredKeywords);

        $results->each(function (Result $result) use (&$keywords, &$ignoredKeywords) {
            $result->keywords($this->convertEntryKeywords(
                $keywords[$result->entry()->id()] ?? null,
                $ignoredKeywords
            ));
        });

        return collect((new KeywordComparator)->compare($primaryKeywords)->to($results->all()))
            ->each(function (Result $result) use ($options) {
                // Reset the keywords.
                $result->similarKeywords(
                    collect($result->similarKeywords())->sortByDesc('score')->mapWithKeys(function ($item) {
                        return [$item['keyword'] => $item['score']];
                    })->take($options->keywordLimit)->all()
                );
            })
            ->where(fn (Result $result) => $result->keywordScore() > $options->keywordThreshold)
            ->sortByDesc(fn (Result $result) => $result->keywordScore())
            ->values();
    }
}
