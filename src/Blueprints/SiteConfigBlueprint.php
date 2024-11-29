<?php

namespace Statamic\SeoPro\Blueprints;

use Statamic\Facades\Blueprint;

class SiteConfigBlueprint
{
    public static function make()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'general' => [
                    'fields' => [
                        [
                            'handle' => 'site_handle',
                            'field' => [
                                'display' => 'Site',
                                'type' => 'sites',
                                'visibility' => 'hidden',
                                'config' => [
                                    'max_items' => 1,
                                ],
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                    ],
                ],
                'thresholds' => [
                    'fields' => [
                        [
                            'handle' => 'keyword_threshold',
                            'field' => [
                                'display' => __('seo-pro::settings.keyword_threshold'),
                                'type' => 'range',
                                'default' => config('statamic.seo-pro.linking.keyword_threshold', 65),
                                'width' => 50,
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'prevent_circular_links',
                            'field' => [
                                'display' => __('seo-pro::settings.prevent_circular_links'),
                                'type' => 'toggle',
                                'width' => 50,
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                    ],
                ],
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'min_internal_links',
                            'field' => [
                                'display' => __('seo-pro::settings.min_internal_links'),
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.linking.internal_links.min_desired', 3),
                                'width' => 50,
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'max_internal_links',
                            'field' => [
                                'display' => __('seo-pro::settings.max_internal_links'),
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.linking.internal_links.max_desired', 6),
                                'width' => 50,
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'min_external_links',
                            'field' => [
                                'display' => __('seo-pro::settings.min_external_links'),
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.linking.external_links.min_desired', 0),
                                'width' => 50,
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                        [
                            'handle' => 'max_external_links',
                            'field' => [
                                'display' => __('seo-pro::settings.max_external_links'),
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.linking.external_links.max_desired', 0),
                                'width' => 50,
                                'validate' => [
                                    'required',
                                ],
                            ],
                        ],
                    ],
                ],
                'phrases' => [
                    'fields' => [
                        [
                            'handle' => 'ignored_phrases',
                            'field' => [
                                'display' => __('seo-pro::settings.ignored_phrases'),
                                'type' => 'list',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
