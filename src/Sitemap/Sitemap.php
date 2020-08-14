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
        return (new static)->getPages();
    }

    public function getPages()
    {
        return $this->publishedContent()
            ->map(function ($content) {
                $cascade = $content->value('seo');

                if ($cascade === false || collect($cascade)->get('sitemap') === false) {
                    return;
                }

                $data = (new Cascade)
                    ->with(SiteDefaults::load()->all())
                    ->with($this->getAugmentedSectionDefaults($content))
                    ->with($cascade ?: [])
                    ->withCurrent($content)
                    ->get();

                return (new Page)->with($data);
            })
            ->filter()
            ->sortBy(function ($page) {
                return substr_count(rtrim($page->path(), '/'), '/');
            })
            ->values()
            ->map
            ->toArray();
    }

    protected function publishedContent()
    {
        return collect()
            ->merge($this->publishedEntries())
            ->merge($this->publishedTerms())
            ->values();
    }

    protected function publishedEntries()
    {
        return Collection::all()
            ->flatMap(function ($collection) {
                return $collection->cascade('seo') !== false ? $collection->queryEntries()->get() : collect();
            })
            ->filter(function ($entry) {
                return $entry->status() === 'published';
            });
    }

    protected function publishedTerms()
    {
        return Taxonomy::all()
            ->flatMap(function ($taxonomy) {
                return $taxonomy->cascade('seo') !== false ? $taxonomy->queryTerms()->get() : collect();
            })
            ->filter
            ->published();
    }
}
