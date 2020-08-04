<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Facades\Cache;
use Statamic\Events;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\SeoPro\Sitemap\Sitemap;
use Statamic\Support\Str;

class Subscriber
{
    /**
     * Subscribed events.
     *
     * @var array
     */
    protected $events = [
        Events\EntryBlueprintFound::class => 'addSeoFields',
        Events\TermBlueprintFound::class => 'addSeoFields',
        Events\CollectionSaved::class => 'clearSitemapCache',
        Events\EntrySaved::class => 'clearSitemapCache',
        Events\TaxonomySaved::class => 'clearSitemapCache',
        Events\TermSaved::class => 'clearSitemapCache',
    ];

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, self::class.'@'.$method);
        }
    }

    /**
     * Add SEO section and fields to entry blueprint.
     *
     * @param mixed $event
     */
    public function addSeoFields($event)
    {
        if ($this->seoIsEnabledForSection($event)) {
            Blueprint::on($event)->addSeoFields();
        }
    }

    /**
     * Clear sitemap cache.
     */
    public function clearSitemapCache()
    {
        Cache::forget(Sitemap::CACHE_KEY);
    }

    /**
     * Check if SEO is enabled for section.
     *
     * @param mixed $event
     * @return bool
     */
    protected function seoIsEnabledForSection($event)
    {
        $namespace = $event->blueprint->namespace();

        if (Str::startsWith($namespace, 'collections.')) {
            $section = Collection::findByHandle(Str::after($namespace, 'collections.'));
        } elseif (Str::startsWith($namespace, 'taxonomies.')) {
            $section = Taxonomy::findByHandle(Str::after($namespace, 'taxonomies.'));
        } else {
            throw new \Exception('Unknown section type.');
        }

        return $section->cascade('seo') !== false;
    }
}
