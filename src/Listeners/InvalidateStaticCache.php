<?php

namespace Statamic\SeoPro\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Statamic\Facades;
use Statamic\SeoPro\Events\SeoProSiteDefaultsSaved;
use Statamic\Sites\Site;
use Statamic\StaticCaching\Cacher;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class InvalidateStaticCache implements ShouldQueue
{
    private string|array $rules;

    public function __construct(private Cacher $cacher)
    {
        $this->rules = config('statamic.static_caching.invalidation.rules', []);
    }

    public function handle(SeoProSiteDefaultsSaved $event): void
    {
        if (! config('statamic.static_caching.strategy')) {
            return;
        }

        if ($this->rules === 'all') {
            $this->cacher->flush();

            return;
        }

        $rules = collect(Arr::get($this->rules, 'seo_pro_site_defaults.urls'));

        $absoluteUrls = $rules->filter(fn (string $rule): bool => $this->isAbsoluteUrl($rule))->all();

        $prefixedRelativeUrls = Facades\Site::all()->map(function (Site $site) use ($rules): Collection {
            return $rules
                ->reject(fn (string $rule): bool => $this->isAbsoluteUrl($rule))
                ->map(fn (string $rule): bool => Str::removeRight($site->url(), '/').Str::ensureLeft($rule, '/'));
        })->flatten()->all();

        $this->cacher->invalidateUrls([
            ...$absoluteUrls,
            ...$prefixedRelativeUrls,
        ]);
    }

    private function isAbsoluteUrl(string $url): bool
    {
        return isset(parse_url($url)['scheme']);
    }
}
