<?php

namespace Statamic\SeoPro\BrokenLinks;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Globals\Variables;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Facades;
use Statamic\Taxonomies\LocalizedTerm;

class ContentScanner
{
    /**
     * Find the external links in the given values, and make sure each one
     * references this item, removing any references to links it no longer
     * contains (and deleting links that are no longer referenced at all).
     */
    public static function scan(
        string $type,
        string $id,
        array $values,
        ?string $site = null,
        ?Blueprint $blueprint = null,
    ): void {
        $item = new Reference($type, $id, $site ?? Site::default()->handle());

        $urls = LinkExtractor::extract($values, $blueprint)->pluck('url')->unique();

        foreach ($urls as $url) {
            $link = Facades\ExternalLink::findByUrl($url, $item->site) ?? Facades\ExternalLink::make()
                ->site($item->site)
                ->url($url)
                ->nextCheckAt(now());

            if ($link->references()->contains(fn (Reference $reference) => $reference->is($item))) {
                continue;
            }

            $link->references($link->references()->push($item))->save();
        }

        self::removeReferences($item, except: $urls->all());
    }

    public static function scanEntry(Entry $entry): void
    {
        self::scan(
            type: 'entry',
            id: $entry->id(),
            values: $entry->data()->all(),
            site: $entry->locale(),
            blueprint: $entry->blueprint(),
        );
    }

    public static function scanTerm(LocalizedTerm $term): void
    {
        self::scan(
            type: 'term',
            id: $term->id(),
            values: $term->data()->all(),
            site: $term->locale(),
            blueprint: $term->blueprint(),
        );
    }

    public static function scanGlobal(Variables $variables): void
    {
        self::scan(
            type: 'global',
            id: $variables->handle(),
            values: $variables->data()->all(),
            site: $variables->locale(),
            blueprint: $variables->blueprint(),
        );
    }

    /**
     * Remove every reference to the given item, deleting any links that
     * are no longer referenced at all.
     */
    public static function forget(string $type, string $id, ?string $site = null): void
    {
        self::removeReferences(new Reference($type, $id, $site ?? Site::default()->handle()), except: []);
    }

    private static function removeReferences(Reference $item, array $except): void
    {
        Facades\ExternalLink::query()
            ->whereJsonContains('subjects', $item->key())
            ->get()
            ->reject(fn (ExternalLink $link) => in_array($link->url(), $except))
            ->each(function (ExternalLink $link) use ($item) {
                $remaining = $link->references()->reject(fn (Reference $reference) => $reference->is($item));

                if ($remaining->isEmpty()) {
                    $link->delete();

                    return;
                }

                $link->references($remaining)->save();
            });
    }
}
