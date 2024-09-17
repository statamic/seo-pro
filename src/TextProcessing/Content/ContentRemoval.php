<?php

namespace Statamic\SeoPro\TextProcessing\Content;

use Illuminate\Support\Str;

class ContentRemoval
{
    public static function removeLinks(string $value): string
    {
        $pattern = '/<a\s+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i';

        preg_match_all($pattern, $value, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $value = Str::replace($match[0], '', $value);
        }

        return $value;
    }

    public static function removePreCodeBlocks(string $value): string
    {
        $preCodePattern = '/<pre><code[^>]*>.*?<\/code><\/pre>/is';

        return preg_replace($preCodePattern, '', $value);
    }

    public static function removeHeadings(string $value): string
    {
        $headingPattern = '/<h[1-6][^>]*>.*?<\/h[1-6]>/is';

        return preg_replace($headingPattern, '', $value);
    }
}
