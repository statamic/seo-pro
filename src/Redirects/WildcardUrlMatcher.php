<?php

namespace Statamic\SeoPro\Redirects;

class WildcardUrlMatcher
{
    public static function match(string $pattern, string $path): ?array
    {
        $regex = self::toRegex($pattern);

        if (preg_match($regex, $path, $matches)) {
            return array_slice($matches, 1);
        }

        return null;
    }

    public static function resolveDestination(string $destination, array $captures): string
    {
        foreach ($captures as $index => $value) {
            $destination = str_replace('$'.($index + 1), $value, $destination);
        }

        return $destination;
    }

    public static function wildcardCount(string $pattern): int
    {
        return substr_count($pattern, '*');
    }

    private static function toRegex(string $pattern): string
    {
        $segments = explode('*', $pattern);
        $segments = array_map(fn ($segment) => preg_quote($segment, '#'), $segments);

        return '#^'.implode('(.+)', $segments).'$#';
    }
}
