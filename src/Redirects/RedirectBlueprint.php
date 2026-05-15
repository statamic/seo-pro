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
                            'display' => __('seo-pro::messages.redirect'),
                            'fields' => [
                                [
                                    'handle' => 'source',
                                    'field' => [
                                        'type' => 'redirect_source',
                                        'display' => __('seo-pro::messages.source'),
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
                                        'display' => __('seo-pro::messages.destination'),
                                        'instructions' => __('seo-pro::messages.redirect_destination'),
                                        'validate' => ['required'],
                                        'listable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'response_code',
                                    'field' => [
                                        'type' => 'select',
                                        'display' => __('seo-pro::messages.response_code'),
                                        'instructions' => __('seo-pro::messages.redirect_response_code'),
                                        'options' => [
                                            301 => '301 - Moved Permanently',
                                            302 => '302 - Moved Temporarily',
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
                                        'display' => __('seo-pro::messages.description'),
                                        'instructions' => __('seo-pro::messages.redirect_description'),
                                        'listable' => 'hidden',
                                    ],
                                ],
                                [
                                    'handle' => 'hits',
                                    'field' => [
                                        'type' => 'integer',
                                        'display' => __('seo-pro::messages.hits'),
                                        'default' => 0,
                                        'listable' => config('statamic.seo-pro.redirects.track_hits', true),
                                        'read_only' => true,
                                        'visibility' => 'hidden',
                                    ],
                                ],
                                [
                                    'handle' => 'last_hit_at',
                                    'field' => [
                                        'type' => 'date',
                                        'display' => __('seo-pro::messages.last_hit_at'),
                                        'time_enabled' => true,
                                        'listable' => config('statamic.seo-pro.redirects.track_hits', true),
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
                                        'display' => __('seo-pro::messages.enabled'),
                                        'default' => true,
                                        'listable' => false,
                                        'visibility' => 'hidden',
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
