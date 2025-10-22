<?php

namespace Statamic\SeoPro\UpdateScripts;

use Illuminate\Support\Facades\File;
use Statamic\Facades\Addon;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Statamic\UpdateScripts\UpdateScript;

class MigrateToAddonSettings extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('7.0.0');
    }

    public function update()
    {
        if (! File::exists($path = config('statamic.seo-pro.site_defaults.path', base_path('content/seo.yaml')))) {
            return $this;
        }

        Addon::get('statamic/seo-pro')->settings()->set($this->getSettingsData($path))->save();

        File::delete($path);
    }

    private function getSettingsData(string $path): array
    {
        if (Site::multiEnabled()) {
            return [
                'site_defaults' => [
                    Site::default()->handle() => YAML::file($path)->parse(),
                ],
                'site_defaults_sites' => Site::all()->mapWithKeys(function ($site) {
                    return [$site->handle() => $site->isDefault() ? null : Site::default()->handle()];
                })->all(),
            ];
        }

        return ['site_defaults' => YAML::file($path)->parse()];
    }
}
