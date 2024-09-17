<?php

namespace Statamic\SeoPro\TextProcessing\Links;

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
