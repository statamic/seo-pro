<?php

namespace Statamic\SeoPro\Redirects;

use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Fields\Blueprint as FieldsBlueprint;

class ErrorBlueprint
{
    public function __invoke(): FieldsBlueprint
    {
        return BlueprintFacade::make()->setContents([
            'tabs' => [
                'main' => [
                    'display' => __('General'),
                    'sections' => [
                        [
                            'display' => __('Redirect'),
                            'fields' => [
                                [
                                    'handle' => 'url',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => __('URL'),
                                        'listable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'hits',
                                    'field' => [
                                        'type' => 'integer',
                                        'display' => __('Hits'),
                                        'default' => 0,
                                        'listable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'last_hit_at',
                                    'field' => [
                                        'type' => 'date',
                                        'display' => __('Last Hit At'),
                                        'time_enabled' => true,
                                        'listable' => true,
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
