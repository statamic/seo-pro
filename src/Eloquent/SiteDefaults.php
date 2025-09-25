<?php

namespace Statamic\SeoPro\Eloquent;

use Illuminate\Support\Facades\Cache;
use Statamic\SeoPro\Events\SeoProSiteDefaultsSaved;
use Statamic\SeoPro\Models\SeoDefaults;
use Statamic\SeoPro\SiteDefaults as BaseDefaults;

class SiteDefaults extends BaseDefaults
{
    public $cacheKey = 'seo-pro-defaults';

    /**
     * Save site defaults collection to database.
     */
    public function save()
    {
        $model = SeoDefaults::first() ?? SeoDefaults::make();
        $model->data = $this->items;
        $model->save();

        Cache::forget($this->cacheKey);

        SeoProSiteDefaultsSaved::dispatch($this);
    }

    /**
     * Get site defaults from database.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return Cache::rememberForever($this->cacheKey, function () {
            return SeoDefaults::first()->data->toArray() ?? [];
        });
    }
}
