<?php

namespace Statamic\SeoPro\DeadLinks;

use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Fields\Blueprint as FieldsBlueprint;

class LinkBlueprint
{
    public function __invoke(): FieldsBlueprint
    {
        return BlueprintFacade::make()->setContents([
            'tabs' => [
                'main' => [
                    'display' => __('General'),
                    'sections' => [
                        [
                            'display' => __('seo-pro::messages.dead_link'),
                            'fields' => [
                                [
                                    'handle' => 'url',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => __('URL'),
                                        'listable' => true,
                                        'focus' => true,
                                        'read_only' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'response',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => __('seo-pro::messages.response'),
                                        'listable' => true,
                                        'sortable' => false,
                                        'read_only' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'checked_at',
                                    'field' => [
                                        'type' => 'date',
                                        'display' => __('seo-pro::messages.last_checked_at'),
                                        'time_enabled' => true,
                                        'listable' => true,
                                        'read_only' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
