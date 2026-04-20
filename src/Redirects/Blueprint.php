<?php

namespace Statamic\SeoPro\Redirects;

use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Fields\Blueprint as FieldsBlueprint;

class Blueprint
{
    public function __invoke(): FieldsBlueprint
    {
        return BlueprintFacade::make()->setContents([
            'tabs' => [
                'main' => [
                    'display' => __('General'),
                    'sections' => [
                        [
                            'fields' => [
                                [
                                    'handle' => 'source_url',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => __('Source URL'),
                                        'validate' => ['required', 'new \Statamic\SeoPro\Rules\ValidRedirectUrl'],
                                        'listable' => true,
                                        'focus' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'destination_url',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => __('Destination URL'),
                                        'validate' => ['required', 'new \Statamic\SeoPro\Rules\ValidRedirectUrl'],
                                        'listable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'status_code',
                                    'field' => [
                                        'type' => 'select',
                                        'display' => __('Status Code'),
                                        'options' => [
                                            301 => '301 - Permanent',
                                            302 => '302 - Temporary',
                                        ],
                                        'default' => 301,
                                        'clearable' => false,
                                        'max_items' => 1,
                                        'validate' => ['required'],
                                        'listable' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sidebar' => [
                    'sections' => [
                        [
                            'fields' => [
                                [
                                    'handle' => 'enabled',
                                    'field' => [
                                        'type' => 'toggle',
                                        'display' => __('Enabled'),
                                        'default' => true,
                                        'listable' => false,
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
