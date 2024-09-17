<?php

namespace Statamic\SeoPro\TextProcessing\Config;

use Statamic\Facades\Blueprint;

class SiteConfigBlueprint
{
    public static function blueprint()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'thresholds' => [
                    'fields' => [
                        [
                            'handle' => 'keyword_threshold',
                            'field' => [
                                'display' => 'Keyword Threshold',
                                'type' => 'range',
                                'default' => config('statamic.seo-pro.text_analysis.keyword_threshold', 65),
                            ],
                        ],
                    ],
                ],
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'min_internal_links',
                            'field' => [
                                'display' => 'Min. Internal Links',
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.text_analysis.internal_links.min_desired', 3),
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'max_internal_links',
                            'field' => [
                                'display' => 'Max. Internal Links',
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.text_analysis.internal_links.max_desired', 6),
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'min_external_links',
                            'field' => [
                                'display' => 'Min. External Links',
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.text_analysis.external_links.min_desired', 0),
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'max_external_links',
                            'field' => [
                                'display' => 'Max. External Links',
                                'type' => 'integer',
                                'default' => config('statamic.seo-pro.text_analysis.external_links.max_desired', 0),
                                'width' => 50,
                            ],
                        ],
                    ],
                ],
                'phrases' => [
                    'fields' => [
                        [
                            'handle' => 'ignored_phrases',
                            'field' => [
                                'display' => 'Ignored Phrases',
                                'type' => 'list',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
