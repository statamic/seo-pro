<?php

namespace Statamic\SeoPro\DeadLinks;

use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;

class LinkExtractor
{
    const URL_REGEX = '/\bhttps?:\/\/[^\s"\'<>\)\]]+/i';

    /**
     * Walk every field defined on the blueprint (recursing into whatever
     * value it holds - strings, bard/replicator/grid structures, etc) and
     * return every external URL found, tagged with the top-level field it
     * came from.
     *
     * @return Collection<int, array{url: string, field_path: string}>
     */
    public static function extract(array $values, ?Blueprint $blueprint): Collection
    {
        $found = collect();

        $handles = $blueprint
            ? $blueprint->fields()->all()->keys()->all()
            : array_keys($values);

        foreach ($handles as $handle) {
            self::walkValue($values[$handle] ?? null, (string) $handle, $found);
        }

        $excluded = config('statamic.seo-pro.dead_links.excluded_hosts', []);

        return $found
            ->filter(fn ($row) => self::isExternal($row['url'], $excluded))
            ->unique(fn ($row) => $row['url'].'|'.$row['field_path'])
            ->values();
    }

    protected static function walkValue($value, string $path, Collection $found): void
    {
        if (is_string($value)) {
            foreach (self::urlsIn($value) as $url) {
                $found->push(['url' => $url, 'field_path' => $path]);
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                self::walkValue($item, is_int($key) ? "{$path}.{$key}" : $path, $found);
            }
        }
    }

    protected static function urlsIn(string $text): array
    {
        preg_match_all(self::URL_REGEX, $text, $matches);

        return array_map(fn ($url) => rtrim($url, '.,;:!?)"\''), $matches[0] ?? []);
    }

    public static function isExternal(string $url, array $excludedHosts = []): bool
    {
        $parts = parse_url($url);

        if (empty($parts['scheme']) || empty($parts['host']) || ! in_array(strtolower($parts['scheme']), ['http', 'https'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        foreach (array_merge(self::internalHosts(), $excludedHosts) as $blocked) {
            $blocked = strtolower(ltrim((string) $blocked, '.'));

            if ($blocked !== '' && ($host === $blocked || str_ends_with($host, '.'.$blocked))) {
                return false;
            }
        }

        return true;
    }

    protected static function internalHosts(): array
    {
        return Site::all()
            ->map(fn ($site) => parse_url($site->url(), PHP_URL_HOST))
            ->filter()
            ->map(fn ($host) => strtolower($host))
            ->unique()
            ->values()
            ->all();
    }
}
