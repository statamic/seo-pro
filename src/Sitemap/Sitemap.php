<?php

namespace Statamic\SeoPro\Sitemap;

use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\LazyCollection;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Entries\Entry;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Config;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Taxonomy;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults;

class Sitemap
{
    use GetsSectionDefaults;

    const CACHE_KEY = 'seo-pro.sitemap';

    private IlluminateCollection $sites;

    public function __construct()
    {
        $this->sites = collect();
    }

    public function pages(): array
    {
        return collect()
            ->merge($this->publishedEntries())
            ->merge($this->publishedTerms())
            ->merge($this->publishedCollectionTerms())
            ->pipe(fn ($pages) => $this->getPages($pages))
            ->sortBy(fn ($page) => substr_count(rtrim($page->path(), '/'), '/'))
            ->values()
            ->map
            ->toArray()
            ->all();
    }

    public function paginatedPages(int $page): array
    {
        $perPage = config('statamic.seo-pro.sitemap.pagination.limit', 100);
        $offset = ($page - 1) * $perPage;
        $remaining = $perPage;

        $pages = collect();

        $entryCount = $this->publishedEntriesCount() - 1;

        if ($offset < $entryCount) {
            $entries = $this->publishedEntriesForPage($page, $perPage);

            if ($entries->count() < $remaining) {
                $remaining -= $entries->count();
            }

            $pages = $pages->merge($entries);
        }

        if ($remaining > 0) {
            $offset = max($offset - $entryCount, 0);

            $pages = $pages->merge(
                collect()
                    ->merge($this->publishedTerms())
                    ->merge($this->publishedCollectionTerms())
                    ->skip($offset)
                    ->take($remaining)
            );
        }

        if ($pages->isEmpty()) {
            return [];
        }

        return $this
            ->getPages($pages)
            ->values()
            ->map
            ->toArray()
            ->all();
    }

    public function paginatedSitemaps(): array
    {
        // would be nice to make terms a count query rather than getting the count from the terms collection
        $count = $this->publishedEntriesCount() + $this->publishedTerms()->count() + $this->publishedCollectionTerms()->count();

        $sitemapCount = ceil($count / config('statamic.seo-pro.sitemap.pagination.limit', 100));

        return collect(range(1, $sitemapCount))
            ->map(fn ($page) => ['url' => route('statamic.seo-pro.sitemap.page.show', ['page' => $page])])
            ->all();
    }

    public function forSites(IlluminateCollection $sites): self
    {
        $this->sites = $sites;

        return $this;
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

                return (new Page)->with(array_merge($data, ['hreflangs' => $this->hrefLangs($content)]));
            })
            ->filter();
    }

    protected function publishedEntriesQuery()
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

        return EntryFacade::query()
            ->when(
                $this->sites->isNotEmpty(),
                fn (QueryBuilder $query) => $query->whereIn('site', $this->sites->map->handle()->all())
            )->whereIn('collection', $collections)
            ->whereNotNull('uri')
            ->whereStatus('published')
            ->orderBy('uri');
    }

    protected function publishedEntries(): LazyCollection
    {
        return $this->publishedEntriesQuery()->lazy();
    }

    protected function publishedEntriesForPage(int $page, int $perPage): IlluminateCollection
    {
        $offset = ($page - 1) * $perPage;

        return $this
            ->publishedEntriesQuery()
            ->offset($offset)
            ->limit($perPage)
            ->get();
    }

    protected function publishedEntriesCount(): int
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

    protected function hrefLangs(Entry $entry): array
    {
        if (config('statamic.seo-pro.alternate_locales') === false) {
            return [];
        }
        if (config('statamic.seo-pro.alternate_locales.enabled') === false) {
            return [];
        }

        $alternateLocales = collect(Config::getLocales())
            ->filter(fn ($locale) => $entry->in($locale))
            ->filter(fn ($locale) => $entry->in($locale)->status() === 'published')
            ->reject(fn ($locale) => collect(config('statamic.seo-pro.alternate_locales.excluded_sites'))->contains($locale))
            ->map(function ($locale) use ($entry) {
                return [
                    'site' => Config::getSite($locale),
                    'href' => $entry->in($locale)->absoluteUrl(),
                ];
            });

        $duplicates = $alternateLocales
            ->groupBy(fn ($locale) => $locale['site']->shortLocale())
            ->filter(fn ($locales) => $locales->count() > 1)
            ->keys();

        $alternateLocales->transform(function ($locale) use ($duplicates) {
            return array_merge($locale, [
                'hreflang' => $duplicates->contains($locale['site']->shortLocale())
                    ? strtolower(str_replace('_', '-', $locale['site']->locale()))
                    : $locale['site']->shortLocale(),
            ]);
        });

        return $alternateLocales->all();
    }
}
