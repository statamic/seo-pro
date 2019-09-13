<?php

namespace Statamic\Addons\SeoPro\Sitemap;

use Statamic\Addons\SeoPro\Events\CollectSitemapPagesEvent;
use Statamic\API;
use Statamic\Addons\SeoPro\TagData;
use Statamic\Addons\SeoPro\Settings;
use Statamic\API\Config;
use Statamic\Extend\Extensible;

class Sitemap
{
    use Extensible;

    const CACHE_KEY = 'seo-pro.sitemap';

    public function pages()
    {
        $defaultSettings = Settings::load()->get('defaults');

        $pages = $this->items()->map(function ($content) use ($defaultSettings) {
            $cascade = $content->getWithCascade('seo', []);

            if ($cascade === false || array_get($cascade, 'sitemap') === false) {
                return false;
            }

            $data = (new TagData)
                ->with($defaultSettings)
                ->with($cascade)
                ->withCurrent($content)
                ->get();

            return (new Page)->with($data);
        })->filter()->sortBy(function ($page) {
            return substr_count(rtrim($page->path(), '/'), '/');
        });

        // Emit an event to customize the collected pages.
        $collectSitemapPagesEvent = new CollectSitemapPagesEvent($pages);
        $this->emitEvent('collect_sitemap_pages', $collectSitemapPagesEvent);

        return $collectSitemapPagesEvent->getPages();
    }

    protected function items()
    {
        $items = collect_content()
            ->merge(API\Page::all()->values())
            ->merge($this->entries()->values())
            ->merge($this->terms()->values())
            ->removeUnpublished()
            ->values();

        foreach (Config::getOtherLocales() as $locale) {
            $localizedItems = collect_content()
                ->merge(API\Page::all())
                ->merge($this->entries())
                ->merge($this->terms())
                ->localize($locale)
                ->removeUnpublished()
                ->values();
            $items = $items->merge($localizedItems);
        }

        return $items;
    }

    protected function entries()
    {
        return API\Collection::all()->flatMap(function ($collection) {
            if ($collection->get('seo.sitemap') === false) {
                return collect();
            }

            $entries = $collection->entries();

            if ($collection->get('seo.show_future') === false) {
                $entries = $entries->removeFuture();
            }

            if ($collection->get('seo.show_past') === false) {
                $entries = $entries->removePast();
            }

            return $entries;
        });
    }

    protected function terms()
    {
        return API\Taxonomy::all()->flatMap(function ($taxonomy) {
            return ($taxonomy->get('seo.sitemap') === false) ? collect() : $taxonomy->terms();
        });
    }
}
