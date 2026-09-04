<?php

namespace Statamic\SeoPro;

use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsRenderCache;
use Statamic\SeoPro\Llms\LlmsTxtGenerator;
use Statamic\SeoPro\Sitemap\Sitemap;
use Statamic\StaticCaching\Cacher;
use Statamic\Support\Str;
use Throwable;

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
        Events\CollectionSaved::class => 'clearSitemapAndLlmsCache',
        Events\CollectionDeleted::class => 'clearSitemapAndLlmsCache',
        Events\EntrySaved::class => 'clearSitemapAndLlmsCache',
        Events\EntryDeleted::class => 'clearSitemapAndLlmsCache',
        Events\StacheCleared::class => 'clearLlmsContentCaches',
        Events\StacheWarmed::class => 'refreshLlmsContent',
        Events\TaxonomySaved::class => 'clearSitemapCache',
        Events\TaxonomyDeleted::class => 'clearSitemapCache',
        Events\TermSaved::class => 'clearSitemapCache',
        Events\TermDeleted::class => 'clearSitemapCache',
    ];

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
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
        Sitemap::invalidateCache();
    }

    /**
     * Clear generated content caches and refresh physical files managed by SEO Pro.
     */
    public function clearSitemapAndLlmsCache()
    {
        $this->clearSitemapCache();
        $this->refreshLlmsContent();
    }

    public function refreshLlmsContent()
    {
        $this->updateLlmsContent(regenerateManagedFiles: true);
    }

    public function clearLlmsContentCaches()
    {
        $this->updateLlmsContent(regenerateManagedFiles: false);
    }

    private function updateLlmsContent(bool $regenerateManagedFiles): void
    {
        Site::all()->each(function ($site) use ($regenerateManagedFiles) {
            $document = Llms::get($site);

            if (! $document->enabled() || ! $this->hasSelectedContent($document->all())) {
                return;
            }

            app(LlmsRenderCache::class)->forget($site);
            app(Cacher::class)->invalidateUrls([Llms::url($site)]);

            if (! $regenerateManagedFiles) {
                return;
            }

            $generator = app(LlmsTxtGenerator::class);

            if (! $generator->status($site)['managed']) {
                return;
            }

            try {
                $generator->generate($document, $site);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    private function hasSelectedContent(array $values): bool
    {
        return $values['mode'] === 'managed'
            && ($values['collections'] !== [] || $values['entries'] !== []);
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
