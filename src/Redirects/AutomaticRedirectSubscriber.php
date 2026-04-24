<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Support\Facades\Cache;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\SeoPro\Facades\Redirect as RedirectFacade;
use Statamic\Sites\Site as SiteInstance;
use Statamic\Support\Str;

class AutomaticRedirectSubscriber
{
    protected array $events = [
        EntrySaving::class => 'handleEntrySaving',
        EntrySaved::class => 'handleEntrySaved',
        TermSaving::class => 'handleTermSaving',
        TermSaved::class => 'handleTermSaved',
    ];

    public function subscribe($events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, self::class.'@'.$method);
        }
    }

    public function handleEntrySaving(EntrySaving $event): void
    {
        if (! $this->shouldHandleEntry($entry = $event->entry)) {
            return;
        }

        $initialPath = $entry->initialPath();

        if (! $initialPath || $initialPath === $entry->buildPath()) {
            return;
        }

        $newSlug = $entry->slug();
        $originalSlug = pathinfo($initialPath, PATHINFO_FILENAME);
        $originalSlug = preg_replace('/^\d{4}-\d{2}-\d{2}(-\d{4,6})?\./', '', $originalSlug);

        if ($originalSlug === $newSlug) {
            return;
        }

        $entry->slug($originalSlug);
        Blink::store('entry-uris')->forget($entry->id());
        $originalUrl = $entry->urlWithoutRedirect();
        Blink::store('entry-uris')->forget($entry->id());
        $entry->slug($newSlug);

        if (! $originalUrl) {
            return;
        }

        Cache::put(
            key: "seo-pro::original-url::{$entry->id()}",
            value: $this->stripSitePrefix($originalUrl, $entry->site()),
            ttl: 30,
        );
    }

    public function handleEntrySaved(EntrySaved $event): void
    {
        $entry = $event->entry;
        $originalUrl = Cache::pull("seo-pro::original-url::{$entry->id()}");

        if (! $originalUrl || ! $entry->urlWithoutRedirect()) {
            return;
        }

        $newUrl = $this->stripSitePrefix($entry->urlWithoutRedirect(), $entry->site());

        if ($originalUrl === $newUrl) {
            return;
        }

        $this->createOrUpdateRedirect($originalUrl, $entry->reference(), $entry->locale());
        $this->deleteRedirectWithSource($newUrl, $entry->locale());
    }

    public function handleTermSaving(TermSaving $event): void
    {
        if (! $this->shouldHandleTerm($term = $event->term)) {
            return;
        }

        $originalSlug = $term->getOriginal('slug');

        if (! $originalSlug || $originalSlug === $term->slug()) {
            return;
        }

        $newSlug = $term->slug();
        $localizedTerm = $term->in($term->defaultLocale());

        $localizedTerm->slug($originalSlug);
        $originalUrl = $localizedTerm->urlWithoutRedirect();
        $localizedTerm->slug($newSlug);

        if (! $originalUrl) {
            return;
        }

        $site = Site::get($term->defaultLocale());

        Cache::put("seo-pro::original-url::term::{$term->taxonomyHandle()}::{$newSlug}", [
            'url' => $this->stripSitePrefix($originalUrl, $site),
            'slug' => $originalSlug,
        ], 30);
    }

    public function handleTermSaved(TermSaved $event): void
    {
        $term = $event->term;
        $cached = Cache::pull("seo-pro::original-url::term::{$term->taxonomyHandle()}::{$term->slug()}");

        if (! $cached) {
            return;
        }

        $originalUrl = $cached['url'];
        $originalSlug = $cached['slug'];
        $siteHandle = $term->defaultLocale();
        $site = Site::get($siteHandle);

        $newUrl = $this->stripSitePrefix(
            $term->in($siteHandle)->urlWithoutRedirect(),
            $site,
        );

        if (! $newUrl || $originalUrl === $newUrl) {
            return;
        }

        $this->rewriteExistingDestinations($originalUrl, $newUrl, $siteHandle);
        $this->createOrUpdateRedirect($originalUrl, $newUrl, $siteHandle);
        $this->deleteSelfReferencingRedirect($newUrl, $siteHandle);
        $this->createRedirectsForTermLocalizations($term, $originalSlug);
    }

    private function createOrUpdateRedirect(string $source, string $destination, string $siteHandle): void
    {
        $existing = RedirectFacade::query()
            ->where('source', $source)
            ->where('site', $siteHandle)
            ->first();

        if ($existing) {
            $existing->destination($destination)->save();

            return;
        }

        RedirectFacade::make()
            ->source($source)
            ->destination($destination)
            ->site($siteHandle)
            ->responseCode(config('statamic.seo-pro.redirects.default_response_code', 301))
            ->enabled(true)
            ->save();
    }

    private function rewriteExistingDestinations(string $oldDestination, string $newDestination, string $siteHandle): void
    {
        RedirectFacade::query()
            ->where('site', $siteHandle)
            ->get()
            ->filter(fn (Redirect $redirect) => $redirect->destination() === $oldDestination)
            ->each(fn (Redirect $redirect) => $redirect->destination($newDestination)->save());
    }

    private function deleteRedirectWithSource(string $source, string $siteHandle): void
    {
        RedirectFacade::query()
            ->where('source', $source)
            ->where('site', $siteHandle)
            ->first()
            ?->delete();
    }

    private function deleteSelfReferencingRedirect(string $url, string $siteHandle): void
    {
        $redirect = RedirectFacade::query()
            ->where('source', $url)
            ->where('site', $siteHandle)
            ->first();

        if ($redirect && $redirect->destination() === $url) {
            $redirect->delete();
        }
    }

    private function createRedirectsForTermLocalizations($term, string $originalSlug): void
    {
        $term->taxonomy()->sites()
            ->reject(fn ($siteHandle) => $siteHandle === $term->defaultLocale())
            ->each(function ($siteHandle) use ($term, $originalSlug) {
                $localized = $term->in($siteHandle);

                if (! $localized || ! $localized->urlWithoutRedirect()) {
                    return;
                }

                $site = Site::get($siteHandle);

                $localized->slug($originalSlug);
                $originalUrl = $this->stripSitePrefix($localized->urlWithoutRedirect(), $site);
                $localized->slug($term->slug());

                $newUrl = $this->stripSitePrefix($localized->urlWithoutRedirect(), $site);

                if (! $originalUrl || $originalUrl === $newUrl) {
                    return;
                }

                $this->createOrUpdateRedirect($originalUrl, $newUrl, $siteHandle);
            });
    }

    private function stripSitePrefix(string $absoluteUrl, SiteInstance $site): string
    {
        $sitePrefix = rtrim(parse_url($site->url(), PHP_URL_PATH) ?? '', '/');

        $path = parse_url($absoluteUrl, PHP_URL_PATH) ?? $absoluteUrl;

        if ($sitePrefix && Str::startsWith($path, $sitePrefix)) {
            $path = Str::removeLeft($path, $sitePrefix);
        }

        return $path ?: '/';
    }

    private function shouldHandleEntry($entry): bool
    {
        $collections = config('statamic.seo-pro.redirects.automatic_redirects.collections', []);

        return in_array('*', $collections) || in_array($entry->collectionHandle(), $collections);
    }

    private function shouldHandleTerm($term): bool
    {
        $taxonomies = config('statamic.seo-pro.redirects.automatic_redirects.taxonomies', []);

        return in_array('*', $taxonomies) || in_array($term->taxonomyHandle(), $taxonomies);
    }
}
