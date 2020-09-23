<?php

namespace Statamic\SeoPro\Sitemap;

use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults;

class Sitemap
{
    use GetsSectionDefaults;

    const CACHE_KEY = 'seo-pro.sitemap';

    public static function pages()
    {
        $sitemap = new static;

        return collect()
            ->merge($sitemap->getPages($sitemap->publishedEntries()))
            ->merge($sitemap->getPages($sitemap->publishedTerms()))
            ->merge($sitemap->getPages($sitemap->publishedCollectionTerms()))
            ->sortBy(function ($page) {
                return substr_count(rtrim($page->path(), '/'), '/');
            })
            ->values()
            ->map
            ->toArray();
    }

    protected function getPages($items)
    {
        return $items
            ->map(function ($content) {
                $cascade = $content->value('seo');

                if ($cascade === false || collect($cascade)->get('sitemap') === false) {
                    return;
                }

                $data = (new Cascade)
                    ->with(SiteDefaults::load()->all())
                    ->with($this->getSectionDefaults($content))
                    ->with($cascade ?: [])
                    ->withCurrent($content)
                    ->get();

                return (new Page)->with($data);
            })
            ->filter();
    }

    protected function publishedEntries()
    {
        return Collection::all()
            ->flatMap(function ($collection) {
                return $collection->cascade('seo') !== false
                    ? $collection->queryEntries()->get()
                    : collect();
            })
            ->filter(function ($entry) {
                return $entry->status() === 'published';
            });
    }

    protected function publishedTerms()
    {
        return Taxonomy::all()
            ->flatMap(function ($taxonomy) {
                return $taxonomy->cascade('seo') !== false
                    ? $taxonomy->queryTerms()->get()
                    : collect();
            })
            ->filter
            ->published()
            ->filter(function ($term) {
                return view()->exists($term->template());
            });
    }

    protected function publishedCollectionTerms()
    {
        return Collection::all()
            ->flatMap(function ($collection) {
                return $collection->cascade('seo') !== false
                    ? $collection->taxonomies()->map->collection($collection)
                    : collect();
            })
            ->flatMap(function ($taxonomy) {
                return $taxonomy->cascade('seo') !== false
                    ? $taxonomy->queryTerms()->get()->map->collection($taxonomy->collection())
                    : collect();
            })
            ->filter
            ->published()
            ->filter(function ($term) {
                return view()->exists($term->template());
            });
    }
}
