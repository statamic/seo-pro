<?php

namespace Statamic\SeoPro\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Facades\URL;
use Statamic\SeoPro\Events\CollectionDefaultsSaved;
use Statamic\SeoPro\Events\SiteDefaultsSaved;
use Statamic\SeoPro\Events\TaxonomyDefaultsSaved;
use Statamic\StaticCaching\Cacher;
use Statamic\StaticCaching\Invalidator;
use Statamic\Support\Arr;

class InvalidateStaticCache implements ShouldQueue
{
    private string|array $rules;

    public function __construct(
        private Cacher $cacher,
        private Invalidator $invalidator
    ) {
        $this->rules = config('statamic.static_caching.invalidation.rules', []);
    }

    public function handleSiteDefaultsSaved(SiteDefaultsSaved $event): void
    {
        if (! config('statamic.static_caching.strategy')) {
            return;
        }

        if ($this->rules === 'all') {
            $this->cacher->flush();

            return;
        }

        $rules = collect(Arr::get($this->rules, 'seo_pro_site_defaults.urls'));

        $absoluteUrls = $rules->filter(fn (string $rule): bool => URL::isAbsolute($rule))->all();

        $prefixedRelativeUrls = $rules
            ->reject(fn (string $rule): bool => URL::isAbsolute($rule))
            ->map(fn (string $rule) => URL::tidy($event->defaults->site()->url().'/'.$rule, withTrailingSlash: false));

        $this->cacher->invalidateUrls([
            ...$absoluteUrls,
            ...$prefixedRelativeUrls,
        ]);
    }

    public function handleCollectionDefaultsSaved(CollectionDefaultsSaved $event): void
    {
        if (! config('statamic.static_caching.strategy')) {
            return;
        }

        if ($this->rules === 'all') {
            $this->cacher->flush();

            return;
        }

        $this->invalidator->invalidate($event->collection);
    }

    public function handleTaxonomyDefaultsSaved(TaxonomyDefaultsSaved $event): void
    {
        if (! config('statamic.static_caching.strategy')) {
            return;
        }

        if ($this->rules === 'all') {
            $this->cacher->flush();

            return;
        }

        $event->taxonomy->queryTerms()->get()->each(function ($term) {
            $this->invalidator->invalidate($term);
        });
    }
}
