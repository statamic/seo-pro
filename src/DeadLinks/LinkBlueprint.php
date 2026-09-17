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
                                    'handle' => 'status_code',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => __('seo-pro::messages.status_code'),
                                        'listable' => true,
                                        'read_only' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'consecutive_failures',
                                    'field' => [
                                        'type' => 'integer',
                                        'display' => __('seo-pro::messages.consecutive_failures'),
                                        'default' => 0,
                                        'listable' => true,
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
                                [
                                    'handle' => 'error',
                                    'field' => [
                                        'type' => 'textarea',
                                        'display' => __('seo-pro::messages.error'),
                                        'listable' => 'hidden',
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
