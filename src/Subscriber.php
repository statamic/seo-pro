<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Facades\Cache;
use Statamic\Events;
use Statamic\SeoPro\Sitemap\Sitemap;

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
     * Add SEO section and fields to blueprint.
     *
     * @param mixed $event
     */
    public function addSeoFields($event)
    {
        Blueprint::on($event)->addSeoFields();
    }

    /**
     * Clear sitemap cache.
     */
    public function clearSitemapCache()
    {
        Cache::forget(Sitemap::CACHE_KEY);
    }
}
