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
                                'display' => 'Title',
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'cached_uri',
                            'field' => [
                                'display' => 'URI',
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'site',
                            'field' => [
                                'display' => 'Site',
                                'type' => 'sites',
                                'config' => [
                                    'max_items' => 1,
                                ],
                            ],
                        ],
                        [
                            'handle' => 'collection',
                            'field' => [
                                'display' => 'Collection',
                                'type' => 'collections',
                                'config' => [
                                    'max_items' => 1,
                                ],
                            ],
                        ],
                        [
                            'handle' => 'internal_link_count',
                            'field' => [
                                'display' => 'Internal Link Count',
                                'type' => 'integer',
                            ],
                        ],
                        [
                            'handle' => 'external_link_count',
                            'field' => [
                                'display' => 'External Link Count',
                                'type' => 'integer',
                            ],
                        ],
                        [
                            'handle' => 'inbound_internal_link_count',
                            'field' => [
                                'display' => 'Inbound Internal Link Count',
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
