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
        Events\BlueprintFound::class => 'addSeoFields',
        Events\Data\CollectionSaved::class => 'clearSitemapCache',
        Events\Data\EntrySaved::class => 'clearSitemapCache',
        Events\Data\TaxonomySaved::class => 'clearSitemapCache',
        Events\Data\TermSaved::class => 'clearSitemapCache',
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
     * @param Events\BlueprintFound $blueprintFound
     */
    public function addSeoFields(Events\BlueprintFound $blueprintFound)
    {
        if (! in_array($blueprintFound->type, ['page', 'entry', 'term'])) {
            return;
        }

        Blueprint::on($blueprintFound)->addSeoFields();
    }

    /**
     * Clear sitemap cache.
     */
    public function clearSitemapCache()
    {
        Cache::forget(Sitemap::CACHE_KEY);
    }
}
