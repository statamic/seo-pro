<?php

namespace Statamic\SeoPro\Blueprints;

use Statamic\Facades\Blueprint;

class CollectionConfigBlueprint
{
    public static function blueprint()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'linking_enabled',
                            'field' => [
                                'display' => __('seo-pro::settings.linking_enabled'),
                                'type' => 'toggle',
                            ],
                        ],
                        [
                            'handle' => 'allow_cross_site_linking',
                            'field' => [
                                'display' => __('seo-pro::settings.allow_cross_site_suggestions'),
                                'type' => 'toggle',
                                'default' => false,
                            ],
                        ],
                        [
                            'handle' => 'allow_cross_collection_suggestions',
                            'field' => [
                                'display' => __('seo-pro::settings.allow_suggestions_from_all_collections'),
                                'type' => 'toggle',
                            ],
                        ],
                        [
                            'handle' => 'allowed_collections',
                            'field' => [
                                'display' => __('seo-pro::settings.allowed_collections'),
                                'type' => 'collections',
                                'mode' => 'select',
                                'if' => [
                                    'allow_cross_collection_suggestions' => 'equals false',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
