<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Support\Facades\Cache;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;
use Statamic\Facades\Blink;
use Statamic\SeoPro\Facades\Redirect as RedirectFacade;

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
        if (
            ! config('statamic.seo-pro.redirects.automatic_redirects.enabled')
            || ! $this->shouldHandleEntry($entry = $event->entry)
        ) {
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

        Cache::put("seo-pro::original-url::{$entry->id()}", $originalUrl, 30);
    }

    public function handleEntrySaved(EntrySaved $event): void
    {
        $entry = $event->entry;
        $originalUrl = Cache::pull("seo-pro::original-url::{$entry->id()}");

        if (! $originalUrl) {
            return;
        }

        $newUrl = $entry->urlWithoutRedirect();

        if (! $newUrl || $originalUrl === $newUrl) {
            return;
        }

        $existing = RedirectFacade::query()->where('source', $originalUrl)->first();

        if ($existing) {
            $existing->destination($entry->reference())->save();
        } else {
            RedirectFacade::make()
                ->source($originalUrl)
                ->destination($entry->reference())
                ->responseCode(config('statamic.seo-pro.redirects.default_response_code', 301))
                ->enabled(true)
                ->save();
        }

        $selfReferencing = RedirectFacade::query()->where('source', $entry->urlWithoutRedirect())->first();
        $selfReferencing?->delete();
    }

    public function handleTermSaving(TermSaving $event): void
    {
        if (
            ! config('statamic.seo-pro.redirects.automatic_redirects.enabled')
            || ! $this->shouldHandleTerm($term = $event->term)
        ) {
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

        Cache::put("seo-pro::original-url::term::{$term->taxonomyHandle()}::{$newSlug}", $originalUrl, 30);
    }

    public function handleTermSaved(TermSaved $event): void
    {
        $term = $event->term;
        $originalUrl = Cache::pull("seo-pro::original-url::term::{$term->taxonomyHandle()}::{$term->slug()}");

        if (! $originalUrl) {
            return;
        }

        $newUrl = $term->in($term->defaultLocale())->urlWithoutRedirect();

        if (! $newUrl || $originalUrl === $newUrl) {
            return;
        }

        RedirectFacade::query()->get()
            ->filter(fn (Redirect $redirect) => $redirect->destination() === $originalUrl)
            ->each(fn (Redirect $redirect) => $redirect->destination($newUrl)->save());

        $existing = RedirectFacade::query()->where('source', $originalUrl)->first();

        if ($existing) {
            $existing->destination($newUrl)->save();
        } else {
            RedirectFacade::make()
                ->source($originalUrl)
                ->destination($newUrl)
                ->responseCode(config('statamic.seo-pro.redirects.default_response_code', 301))
                ->enabled(true)
                ->save();
        }

        $selfReferencing = RedirectFacade::query()->where('source', $newUrl)->first();

        if ($selfReferencing && $selfReferencing->destination() === $newUrl) {
            $selfReferencing->delete();
        }
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
