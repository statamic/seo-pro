<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Statamic\Events;
use Statamic\Facades\Data;
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
     * Add SEO section and fields to entry blueprint.
     *
     * @param mixed $event
     */
    public function addSeoFields($event)
    {
        if ($this->isAllowedPublishForm() && $this->seoIsEnabledForSection($event)) {
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
     * Check if on an allowed published form.
     *
     * @return bool
     */
    protected function isAllowedPublishForm()
    {
        $allowedRoutes = [
            'statamic.cp.collections.entries.create',
            'statamic.cp.collections.entries.edit',
            'statamic.cp.taxonomies.terms.create',
            'statamic.cp.taxonomies.terms.edit',
        ];

        return in_array(Request::route()->getName(), $allowedRoutes);
    }

    /**
     * Check if SEO is enabled for section.
     *
     * @param mixed $event
     * @return bool
     */
    protected function seoIsEnabledForSection($event)
    {
        // We can't check `value('seo')` because when creating an entry/term, we don't have an instance yet.
        $section = $event->blueprint->namespace();

        // So we convert the blueprint's namespace to a proper collection/taxonomy id to find the section,
        $section = str_replace('collections.', 'collection::', $section);
        $section = str_replace('taxonomies.', 'taxonomy::', $section);

        // Temporary fix for `Data::('collection::handle')` until beta 43,
        if (\Statamic\Support\Str::startsWith('collection::', $section)) {
            return \Statamic\Facades\Collection::find(str_replace('collection::', '', $section))->cascade('seo') !== false;
        }

        // And then grab the setting right off the collection/taxonomy.
        return Data::find($section)->cascade('seo') !== false;
    }
}
