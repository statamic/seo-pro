<?php

namespace Statamic\SeoPro\BrokenLinks;

use Illuminate\Support\Collection;
use Statamic\Facades\URL;
use Statamic\Fields\Blueprint;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class LinkExtractor
{
    const URL_REGEX = '/\bhttps?:\/\/[^\s"\'<>\]]+/i';

    public function extract(array $values, ?Blueprint $blueprint = null): Collection
    {
        if ($blueprint) {
            $values = Arr::only($values, $blueprint->fields()->all()->keys()->all());
        }

        return collect(Arr::flatten($values))
            ->filter(fn ($value) => is_string($value))
            ->flatMap(fn (string $value) => $this->urlsIn($value))
            ->filter(fn (string $url) => $this->isExternal($url))
            ->unique()
            ->values();
    }

    private function urlsIn(string $text): array
    {
        preg_match_all(self::URL_REGEX, $text, $matches);

        return array_map(fn ($url) => $this->trimTrailingPunctuation($url), $matches[0]);
    }

    private function trimTrailingPunctuation(string $url): string
    {
        $url = rtrim($url, '.,;:!?"\'');

        while (str_ends_with($url, ')') && substr_count($url, '(') < substr_count($url, ')')) {
            $url = rtrim(substr($url, 0, -1), '.,;:!?"\'');
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
            ->map(fn ($excluded) => strtolower(ltrim($excluded, '.')))
            ->filter()
            ->doesntContain(fn ($excluded) => $host === $excluded || str_ends_with($host, ".{$excluded}"));
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
