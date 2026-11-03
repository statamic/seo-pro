<?php

namespace Statamic\SeoPro\BrokenLinks;

use Illuminate\Support\Collection;
use Statamic\Facades\URL;
use Statamic\Fields\Blueprint;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class LinkExtractor
{
    const string URL_REGEX = '/\bhttps?:\/\/[^\s"\'<>\]]+/i';

    public function extract(array $values, ?Blueprint $blueprint = null): Collection
    {
        if ($blueprint) {
            $values = Arr::only($values, $blueprint->fields()->all()->keys()->all());
        }

        return collect(Arr::flatten($values))
            ->filter(fn ($value): bool => is_string($value))
            ->flatMap(fn (string $value): array => $this->urlsIn($value))
            ->filter(fn (string $url): string => $this->isExternal($url))
            ->unique()
            ->values();
    }

    private function urlsIn(string $text): array
    {
        preg_match_all(self::URL_REGEX, $text, $matches);

        return array_map(fn (string $url): string => rtrim($this->trimUnmatchedParentheses($url), '.,;:!?"\''), $matches[0]);
    }

    private function trimUnmatchedParentheses(string $url): string
    {
        $depth = 0;

        foreach (str_split($url) as $position => $character) {
            if ($character === '(') {
                $depth++;
            }

            if ($character === ')') {
                if ($depth === 0) {
                    return substr($url, 0, $position);
                }

                $depth--;
            }
        }

        return $url;
    }

    private function isExternal(string $url): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (! $this->isPublicHost($host) || ! URL::isExternalToApplication($url)) {
            return false;
        }

        return collect(config('statamic.seo-pro.broken_links.excluded_hosts', []))
            ->map(fn ($excluded): string => strtolower(ltrim($excluded, '.')))
            ->filter()
            ->doesntContain(fn ($excluded): bool => $host === $excluded || str_ends_with($host, ".{$excluded}"));
    }

    private function isPublicHost(string $host): bool
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return str_contains($host, '.') && ! Str::endsWith($host, ['.local', '.localhost', '.internal']);
    }
}
