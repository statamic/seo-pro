<?php

namespace Statamic\SeoPro\Linking\Similarity;

class TextSimilarity
{
    protected static function normalizeValue(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', '', $value);

        return trim($value);
    }

    public static function similarToAny(string $valueA, array $values): bool
    {
        foreach ($values as $value) {
            if (self::isSimilar($valueA, $value)) {
                return true;
            }
        }

        return false;
    }

    public static function isSimilar(string $valueA, string $valueB): bool
    {
        $valueA = self::normalizeValue($valueA);
        $valueB = self::normalizeValue($valueB);

        $maxLen = max(
            mb_strlen($valueA),
            mb_strlen($valueB),
        );

        if ($maxLen == 0) {
            return true;
        }

        $distance = levenshtein($valueA, $valueB);

        similar_text($valueA, $valueB, $similarity);

        $distanceThreshold = ceil($maxLen * 0.1);
        $similarityThreshold = 100 - ($distanceThreshold / $maxLen * 100);

        if ($distance <= $distanceThreshold || $similarity >= $similarityThreshold) {
            return true;
        }

        return false;
    }
}
