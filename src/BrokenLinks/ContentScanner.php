<?php

namespace Statamic\SeoPro\BrokenLinks;

use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Facades;

class ContentScanner
{
    public static function scan(
        string $type,
        string $id,
        array $values,
        ?string $site = null,
        ?Blueprint $blueprint = null,
    ): void {
        $item = new Reference($type, $id, $site ?? Site::default()->handle());

        $urls = app(LinkExtractor::class)->extract($values, $blueprint);

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

    public static function forget(string $type, string $id, ?string $site = null): void
    {
        self::removeReferences(new Reference($type, $id, $site ?? Site::default()->handle()));
    }

    private static function removeReferences(Reference $item, array $except = []): void
    {
        Facades\ExternalLink::query()
            ->whereJsonContains('references', $item->key())
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
