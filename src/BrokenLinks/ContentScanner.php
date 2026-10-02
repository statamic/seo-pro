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
        string $title,
        array $values,
        ?string $site = null,
        ?Blueprint $blueprint = null,
    ): void {
        $item = new Reference($type, $id, $site ?? Site::default()->handle(), $title);

        $found = LinkExtractor::extract($values, $blueprint)->groupBy('url');

        foreach ($found as $url => $rows) {
            $link = Facades\ExternalLink::findByUrl($url, $item->site) ?? Facades\ExternalLink::make()
                ->site($item->site)
                ->url($url)
                ->nextCheckAt(now());

            $link->references($link->references()
                ->reject(fn (Reference $reference) => $reference->is($item))
                ->concat($rows->map(fn ($row) => $item->withField($row['field_path']))))
                ->save();
        }

        self::removeReferences($item, except: $found->keys()->all());
    }

    public static function scanEntry(Entry $entry): void
    {
        self::scan(
            type: 'entry',
            id: $entry->id(),
            title: $entry->get('title') ?? $entry->id(),
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
            title: $term->get('title') ?? $term->slug(),
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
            title: $variables->title(),
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
