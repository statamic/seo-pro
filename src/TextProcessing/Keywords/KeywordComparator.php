<?php

namespace Statamic\SeoPro\TextProcessing\Keywords;

use Statamic\SeoPro\TextProcessing\Similarity\Result;

class KeywordComparator
{
    protected array $weights = [
        'title' => 3,
        'uri' => 3,
        'content' => 1,
    ];

    protected int $keywordThreshold = 70;

    protected array $primaryKeywords = [];

    protected function keywordMatch(string $keywordA, string $keywordB): bool
    {
        similar_text($keywordA, $keywordB, $percent);

        return $percent >= $this->keywordThreshold;
    }

    protected function getAdjustedScores(array $contentKeywords): array
    {
        $base = 0;

        if (count($contentKeywords) > 0) {
            $base = max($contentKeywords);
        }

        if ($base == 0) {
            $base = 1;
        }

        $titleScore = $base * 200;
        $uriScore = $base * 100;

        return [
            'title' => intval($titleScore),
            'uri' => intval($uriScore),
        ];
    }

    protected function getAdjustedKeywords(array $keywords, bool $includeMeta = true)
    {
        $contentKeywords = $keywords['content'] ?? [];

        if (! $includeMeta) {
            return $contentKeywords;
        }

        $adjustedScores = $this->getAdjustedScores($contentKeywords);

        foreach ($keywords['uri'] as $keyword) {
            $contentKeywords[$keyword] = $adjustedScores['uri'];
        }

        foreach ($keywords['title'] as $keyword) {
            $contentKeywords[$keyword] = $adjustedScores['title'];
        }

        return $contentKeywords;
    }

    public function compare(array $primaryKeywords): static
    {
        $this->primaryKeywords = $this->getAdjustedKeywords($primaryKeywords, false);

        return $this;
    }

    protected function compareKeywords(array $keywordsA, $keywordsB): array
    {
        $scoreValues = $this->getAdjustedScores($keywordsB);
        $score = 0;
        $keywords = [];

        foreach ($keywordsA as $keywordA => $scoreA) {
            foreach ($keywordsB as $keywordB => $scoreB) {
                if ($this->keywordMatch($keywordA, $keywordB)) {
                    $source = ($scoreB == $scoreValues['title']) ? 'title' : (($scoreB == $scoreValues['uri']) ? 'uri' : 'content');
                    $weightedScore = $this->weights[$source] * $scoreA;

                    $score += $weightedScore;
                    $keywords[] = [
                        'keyword' => $keywordA,
                        'score' => $weightedScore,
                        'source' => $source,
                    ];
                }
            }
        }

        return [
            'keywords' => $keywords,
            'score' => $score,
        ];
    }

    /**
     * @param Result[] $results
     * @return array
     */
    public function to(array $results): array
    {
        foreach ($results as $result) {
            $keywordResults = $this->compareKeywords(
                $this->primaryKeywords,
                $this->getAdjustedKeywords($result->keywords())
            );

            $result->keywordScore($keywordResults['score']);
            $result->similarKeywords($keywordResults['keywords']);
        }

        return $results;
    }
}