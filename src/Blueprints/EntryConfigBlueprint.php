<?php

namespace Statamic\SeoPro\Blueprints;

use Statamic\Facades\Blueprint;

class EntryConfigBlueprint
{
    public static function make()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'can_be_suggested',
                            'field' => [
                                'display' => __('seo-pro::settings.can_be_suggested'),
                                'type' => 'toggle',
                                'default' => true,
                            ],
                        ],
                        [
                            'handle' => 'include_in_reporting',
                            'field' => [
                                'display' => __('seo-pro::settings.include_in_reporting'),
                                'visibility' => 'hidden',
                                'type' => 'toggle',
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
