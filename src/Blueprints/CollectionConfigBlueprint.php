<?php

namespace Statamic\SeoPro\Blueprints;

use Statamic\Facades\Blueprint;

class CollectionConfigBlueprint
{
    public static function make()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'collection_handle',
                            'field' => [
                                'display' => 'Collection',
                                'type' => 'collections',
                                'visibility' => 'hidden',
                                'config' => [
                                    'max_items' => 1,
                                ],
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'linking_enabled',
                            'field' => [
                                'display' => __('seo-pro::settings.linking_enabled'),
                                'type' => 'toggle',
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'allow_cross_site_linking',
                            'field' => [
                                'display' => __('seo-pro::settings.allow_cross_site_suggestions'),
                                'type' => 'toggle',
                                'default' => false,
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'allow_cross_collection_suggestions',
                            'field' => [
                                'display' => __('seo-pro::settings.allow_suggestions_from_all_collections'),
                                'type' => 'toggle',
                                'validate' => [
                                    'required',
                                ],
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
