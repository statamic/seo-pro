<?php

namespace Statamic\SeoPro\Blueprints;

use Statamic\Facades\Blueprint;

class GlobalAutomaticLinksBlueprint
{
    public static function make()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'link_text',
                            'field' => [
                                'display' => __('seo-pro::messages.link_text'),
                                'type' => 'text',
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'link_target',
                            'field' => [
                                'display' => __('seo-pro::messages.link_target'),
                                'type' => 'text',
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'is_active',
                            'field' => [
                                'display' => __('seo-pro::messages.is_active_link'),
                                'type' => 'toggle',
                                'default' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
