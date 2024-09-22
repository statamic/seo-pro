<?php

namespace Statamic\SeoPro\TextProcessing\Similarity;

class CosineSimilarity
{
    public static function calculate(array $vectorA, array $vectorB): float|int
    {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        $allKeys = array_unique(array_merge(array_keys($vectorA), array_keys($vectorB)));

        foreach ($allKeys as $key) {
            $value1 = $vectorA[$key] ?? 0;
            $value2 = $vectorB[$key] ?? 0;

            $dotProduct += $value1 * $value2;
            $magnitude1 += pow($value1, 2);
            $magnitude2 += pow($value2, 2);
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }
}
