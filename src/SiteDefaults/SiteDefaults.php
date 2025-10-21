<?php

namespace Statamic\SeoPro\SiteDefaults;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Facades\Addon;
use Statamic\Facades\Site;

class SiteDefaults
{
    public static function get(): Collection
    {
        $data = Addon::get('statamic/seo-pro')->settings()->get('site_defaults', []);

        return Site::all()->map(function ($site) use ($data) {
            $values = Arr::get($data, Site::multiEnabled() ? $site->handle() : null);

            // When there are no values set, and it's a root localization, use defaults
            // from the blueprint as initial values.
            if (empty($values) && self::origins()->get($site->handle()) === null) {
                $values = self::defaultValues();
            }

            return new LocalizedSiteDefaults($site->handle(), collect($values));
        });
    }

    public static function origins(): Collection
    {
        return config()->collection(
            key: 'statamic.seo-pro.site_defaults.origins',
            default: Site::all()->mapWithKeys(fn ($site) => [$site->handle() => null])->all()
        );
    }

    public static function in(string $locale): ?LocalizedSiteDefaults
    {
        if (! self::get()->has($locale)) {
            return null;
        }

        return self::get()->get($locale);
    }

    public static function save(LocalizedSiteDefaults $localized): bool
    {
        $data = Addon::get('statamic/seo-pro')->settings()->get('site_defaults', []);

        if (Site::multiEnabled()) {
            $data[$localized->locale()] = $localized->all();
        } else {
            $data = $localized->all();
        }

        Addon::get('statamic/seo-pro')->settings()->set('site_defaults', $data)->save();

        return true;
    }

    public static function blueprint(): \Statamic\Fields\Blueprint
    {
        return Blueprint::get();
    }

    private static function defaultValues(): array
    {
        return [
            'site_name' => 'Site Name',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => '@seo:title',
            'description' => '@seo:content',
            'canonical_url' => '@seo:permalink',
            'priority' => 0.5,
            'change_frequency' => 'monthly',
        ];
    }
}
