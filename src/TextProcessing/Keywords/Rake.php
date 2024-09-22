<?php

namespace Statamic\SeoPro\TextProcessing\Keywords;

use DonatelloZa\RakePlus\RakePlus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\Extractor;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordRetriever;
use Statamic\SeoPro\Hooks\Keywords\StopWordsHook;

class Rake implements Extractor, KeywordRetriever
{
    protected string $locale = 'en_US';

    private function rake(): RakePlus
    {
        return RakePlus::create(
            null,
            stopwords: $this->getStopWords(),
            phrase_min_length: config('statamic.seo-pro.linking.rake.phrase_min_length', 0),
            filter_numerics: config('statamic.seo-pro.linking.rake.filter_numerics', true)
        );
    }

    public function getStopWords(): array
    {
        $path = base_path('vendor/donatello-za/rake-php-plus/lang/'.$this->locale.'.php');

        if (! file_exists($path)) {
            return $this->runStopWordsHook();
        }

        $stopWords = include $path;

        return $this->runStopWordsHook($stopWords);
    }

    protected function runStopWordsHook(array $stopWords = []): array
    {
        return (new StopWordsHook(new StopWordsBag($stopWords, $this->locale)))->getStopWords();
    }

    protected function runRake(string $content): RakePlus
    {
        return $this->rake()->extract($content);
    }

    protected function shouldKeepKeyword(string $keyword): bool
    {
        if (mb_strlen(trim($keyword)) <= 1) {
            return false;
        }

        if (Str::contains($keyword, ['/', '\\', '{', '}', '’', '<', '>', '=', '-'])) {
            return false;
        }

        return true;
    }

    protected function filterKeywords(array $keywords): array
    {
        $results = [];

        foreach ($keywords as $keyword) {
            if (! $this->shouldKeepKeyword($keyword)) {
                continue;
            }

            $results[] = $keyword;
        }

        return $results;
    }

    protected function filterKeywordScores(array $keywords): array
    {
        $results = [];

        foreach ($keywords as $keyword => $score) {
            if (! $this->shouldKeepKeyword($keyword)) {
                continue;
            }

            $results[$keyword] = $score;
        }

        return $results;
    }

    public function extractKeywords(string $content): Collection
    {
        return collect($this->filterKeywords($this->runRake($content)->get()));
    }

    public function extractRankedKeywords(string $content): Collection
    {
        return collect($this->filterKeywordScores($this->runRake($content)->scores()));
    }

    public function transform(string $content): array
    {
        $scores = $this->runRake($content)->scores();

        return array_values($scores);
    }

    public function inLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }
}
