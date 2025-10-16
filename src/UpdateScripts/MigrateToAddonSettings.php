<?php

namespace Statamic\SeoPro\UpdateScripts;

use Illuminate\Support\Facades\File;
use Statamic\Facades\Addon;
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
        if (! File::exists($path = base_path('content/seo.yaml'))) {
            return $this;
        }

        Addon::get('statamic/seo-pro')
            ->settings()
            ->set([
                'site_defaults' => YAML::file($path)->parse(),
            ])
            ->save();

        File::delete($path);
    }
}
