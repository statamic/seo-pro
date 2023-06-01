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
        Events\EntryBlueprintFound::class => 'ensureSeoFields',
        Events\TermBlueprintFound::class => 'ensureSeoFields',
        Events\CollectionSaved::class => 'clearSitemapCache',
        Events\EntrySaved::class => 'clearSitemapCache',
        Events\TaxonomySaved::class => 'clearSitemapCache',
        Events\TermSaved::class => 'clearSitemapCache',
    ];

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, self::class.'@'.$method);
        }
    }

    /**
     * Ensure section blueprint has (or doesn't have) SEO fields.
     *
     * @param  mixed  $event
     */
    public function ensureSeoFields($event)
    {
        Blueprint::on($event)->ensureSeoFields(
            $this->seoIsEnabledForSection($event)
        );
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
     * @param  mixed  $event
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
