<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\SeoPro\Reporting\Rule;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class SiteName extends Rule
{
    protected $validatesPages = false;
    protected $passes;

    public function description()
    {
        return Site::hasMultiple()
            ? __('seo-pro::messages.rules.site_name_multisite')
            : __('seo-pro::messages.rules.site_name');
    }

    public function process()
    {
        $this->passes = $this->siteNames()->filter(fn ($siteName) => empty(trim($siteName)))->isEmpty();
    }

    public function status()
    {
        return $this->passes ? 'pass' : 'fail';
    }

    public function save()
    {
        return $this->passes;
    }

    public function load($data)
    {
        $this->passes = $data;
    }

    public function comment()
    {
        return Site::multiEnabled() ? null : $this->siteNames()->first();
    }

    private function siteNames(): Collection
    {
        return SiteDefaults::get()
            ->map->augmented()
            ->pluck('site_name')
            ->values();
    }

    public function maxPoints()
    {
        return 10;
    }

    public function demerits()
    {
        if (! $this->passes) {
            return $this->maxPoints();
        }
    }
}
