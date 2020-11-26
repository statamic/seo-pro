<?php

namespace Statamic\SeoPro;

trait HasAssetField
{
    /**
     * Get asset field config.
     *
     * @return array
     */
    protected static function getAssetFieldConfig()
    {
        if (! $container = config('statamic.seo-pro.assets.container')) {
            return false;
        }

        return [
            'type' => 'assets',
            'container' => $container,
            'max_files' => 1,
        ];
    }
}
