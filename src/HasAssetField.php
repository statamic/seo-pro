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
            return static::getAssetFieldContainerError();
        }

        return [
            'type' => 'assets',
            'container' => $container,
            'folder' => config('statamic.seo-pro.assets.folder'),
            'max_files' => 1,
        ];
    }

    /**
     * Show helpful asset field config error.
     *
     * @return array
     */
    protected static function getAssetFieldContainerError()
    {
        return [
            'type' => 'html',
            'html' => <<<'HTML'
<div class="mt-2 text-sm text-red-600 [&_a]:underline [&_a:hover]:text-red-700 dark:[&_a:hover]:text-red-300">
    Asset container not configured.
    <a href="https://statamic.com/addons/statamic/seo-pro/docs#advanced-configuration" target="_blank">
        Learn more
    </a>
</div>
HTML,
        ];
    }
}
