<?php

namespace Statamic\SeoPro\Sitemap;

use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
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

    public static function paginatedPages(int $page)
    {
        $sitemap = new static;

        $perPage = config('statamic.seo-pro.sitemap.pagination.limit', 100);
        $offset = ($page - 1) * $perPage;
        $remaining = $perPage;

        $pages = collect([]);

        $entryCount = $sitemap->publishedEntriesCount() - 1;

        if ($offset < $entryCount) {
            $entries = $sitemap->publishedEntriesQuery()->offset($offset)->limit($perPage)->get();

            if ($entries->count() < $remaining) {
                $remaining -= $entries->count();
            }

            $pages = $pages->merge($entries);
        }

        if ($remaining > 0) {
            $offset = max($offset - $entryCount, 0);

            $pages = $pages->merge(
                collect($sitemap->publishedTerms())
                    ->merge($sitemap->publishedCollectionTerms())
                    ->skip($offset)
                    ->take($remaining)
            );
        }

        if ($pages->isEmpty()) {
            return [];
        }

        return $sitemap->getPages($pages)
            ->values()
            ->map
            ->toArray();
    }

    public static function paginatedSitemaps()
    {
        $sitemap = new static;

        // would be nice to make terms a count query rather than getting the count from the terms collection
        $count = $sitemap->publishedEntriesCount() + $sitemap->publishedTerms()->count() + $sitemap->publishedCollectionTerms()->count();

        $sitemapCount = ceil($count / config('statamic.seo-pro.sitemap.pagination.limit', 100));

        return collect(range(1, $sitemapCount))
            ->map(fn ($page) => ['url' => route('statamic.seo-pro.sitemap.page.show', ['page' => $page])])
            ->all();
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
                    ->forSitemap()
                    ->with($this->getSiteDefaults())
                    ->with($this->getSectionDefaults($content))
                    ->with($cascade ?: [])
                    ->withCurrent($content)
                    ->get();

                return (new Page)->with($data);
            })
            ->filter();
    }

    private function publishedEntriesQuery()
    {
        $collections = Collection::all()
            ->map(function ($collection) {
                return $collection->cascade('seo') !== false
                    ? $collection->handle()
                    : false;
            })
            ->filter()
            ->values()
            ->all();

        return Entry::query()
            ->whereIn('collection', $collections)
            ->whereNotNull('uri')
            ->whereStatus('published')
            ->orderBy('uri');
    }

    protected function publishedEntries()
    {
        return $this->publishedEntriesQuery()->lazy();
    }

    protected function publishedEntriesCount()
    {
        return $this->publishedEntriesQuery()->count();
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

    protected function getSiteDefaults()
    {
        return Blink::once('seo-pro.site-defaults', function () {
            return SiteDefaults::load()->all();
        });
    }
}
