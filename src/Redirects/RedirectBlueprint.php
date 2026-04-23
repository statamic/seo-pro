<?php

namespace Statamic\SeoPro\Redirects;

use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Fields\Blueprint as FieldsBlueprint;
use Statamic\SeoPro\Rules\ValidRedirectUrl;

class RedirectBlueprint
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
                                    'handle' => 'source',
                                    'field' => [
                                        'type' => 'redirect_source',
                                        'display' => __('Source'),
                                        'instructions' => __('seo-pro::messages.redirect_source'),
                                        'validate' => ['required', new ValidRedirectUrl],
                                        'listable' => true,
                                        'focus' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'destination',
                                    'field' => [
                                        'type' => 'link',
                                        'display' => __('Destination'),
                                        'instructions' => __('seo-pro::messages.redirect_destination'),
                                        'validate' => ['required'],
                                        'listable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'response_code',
                                    'field' => [
                                        'type' => 'select',
                                        'display' => __('Response Code'),
                                        'instructions' => __('seo-pro::messages.redirect_response_code'),
                                        'options' => [
                                            301 => '301 - Moved Permanently',
                                            302 => '302 - Found',
                                            307 => '307 - Temporary Redirect',
                                            308 => '308 - Permanent Redirect',
                                            410 => '410 - Gone',
                                        ],
                                        'default' => config('statamic.seo-pro.redirects.default_response_code', 301),
                                        'clearable' => false,
                                        'max_items' => 1,
                                        'validate' => ['required'],
                                        'listable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'description',
                                    'field' => [
                                        'type' => 'textarea',
                                        'display' => __('Description'),
                                        'instructions' => __('seo-pro::messages.redirect_description'),
                                        'listable' => 'hidden',
                                    ],
                                ],
                                [
                                    'handle' => 'hits',
                                    'field' => [
                                        'type' => 'integer',
                                        'display' => __('Hits'),
                                        'default' => 0,
                                        'listable' => true,
                                        'read_only' => true,
                                        'visibility' => 'hidden',
                                    ],
                                ],
                                [
                                    'handle' => 'last_hit_at',
                                    'field' => [
                                        'type' => 'date',
                                        'display' => __('Last Hit At'),
                                        'time_enabled' => true,
                                        'listable' => true,
                                        'read_only' => true,
                                        'visibility' => 'hidden',
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
