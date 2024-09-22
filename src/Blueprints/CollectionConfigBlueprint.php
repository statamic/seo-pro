<?php

namespace Statamic\SeoPro\Blueprints;

use Statamic\Facades\Blueprint;

class CollectionConfigBlueprint
{
    public static function blueprint()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'linking_enabled',
                            'field' => [
                                'display' => 'Linking Enabled',
                                'type' => 'toggle',
                            ],
                        ],
                        [
                            'handle' => 'allow_cross_site_linking',
                            'field' => [
                                'display' => 'Allow Cross-Site Suggestions',
                                'type' => 'toggle',
                                'default' => false,
                            ],
                        ],
                        [
                            'handle' => 'allow_cross_collection_suggestions',
                            'field' => [
                                'display' => 'Allow Suggestions from all Collections',
                                'type' => 'toggle',
                            ],
                        ],
                        [
                            'handle' => 'allowed_collections',
                            'field' => [
                                'display' => 'Receive Suggestions From',
                                'type' => 'collections',
                                'mode' => 'select',
                                'if' => [
                                    'allow_cross_collection_suggestions' => 'equals false',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
