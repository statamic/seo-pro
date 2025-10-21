<?php

namespace Statamic\SeoPro\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Statamic\Facades;
use Statamic\Facades\URL;
use Statamic\SeoPro\Events\SiteDefaultsSaved;
use Statamic\Sites\Site;
use Statamic\StaticCaching\Cacher;
use Statamic\Support\Arr;

class InvalidateStaticCache implements ShouldQueue
{
    private string|array $rules;

    public function __construct(private Cacher $cacher)
    {
        $this->rules = config('statamic.static_caching.invalidation.rules', []);
    }

    public function handle(SiteDefaultsSaved $event): void
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
}
