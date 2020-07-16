<?php

namespace Statamic\Addons\SeoPro\Sitemap;

use Statamic\API;
use Statamic\Addons\SeoPro\TagData;
use Statamic\Addons\SeoPro\Settings;

class Sitemap
{
    const CACHE_KEY = 'seo-pro.sitemap';

    public function pages()
    {
        return $this->items()->map(function ($content) {
            $cascade = $content->getWithCascade('seo', []);

            if ($cascade === false || array_get($cascade, 'sitemap') === false) {
                return;
            }

            $data = (new TagData)
                ->with(Settings::load()->get('defaults'))
                ->with($cascade)
                ->withCurrent($content->toArray())
                ->get();

            return (new Page)->with($data);
        })->filter()->sortBy(function ($page) {
            return substr_count(rtrim($page->path(), '/'), '/');
        });
    }

    protected function items()
    {
        return collect_content()
            ->merge(API\Page::all())
            ->merge($this->entries())
            ->merge($this->terms())
            ->removeUnpublished();
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
