<?php

namespace Statamic\SeoPro\Blueprints;

use Statamic\Facades\Blueprint;

class LinkBlueprint
{
    public static function make()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'filters' => [
                    'fields' => [
                        [
                            'handle' => 'cached_title',
                            'field' => [
                                'display' => __('Title'),
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'cached_uri',
                            'field' => [
                                'display' => __('URL'),
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'site',
                            'field' => [
                                'display' => __('Site'),
                                'type' => 'sites',
                                'config' => [
                                    'max_items' => 1,
                                ],
                            ],
                        ],
                        [
                            'handle' => 'collection',
                            'field' => [
                                'display' => __('Collection'),
                                'type' => 'collections',
                                'config' => [
                                    'max_items' => 1,
                                ],
                            ],
                        ],
                        [
                            'handle' => 'internal_link_count',
                            'field' => [
                                'display' => __('seo-pro::messages.internal_link_count'),
                                'type' => 'integer',
                            ],
                        ],
                        [
                            'handle' => 'external_link_count',
                            'field' => [
                                'display' => __('seo-pro::messages.external_link_count'),
                                'type' => 'integer',
                            ],
                        ],
                        [
                            'handle' => 'inbound_internal_link_count',
                            'field' => [
                                'display' => __('seo-pro::messages.inbound_internal_link_count'),
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
