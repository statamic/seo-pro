<?php

namespace Statamic\SeoPro\TextProcessing\Links;

use Statamic\Facades\Blueprint;

class GlobalAutomaticLinksBlueprint
{
    public static function blueprint()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'link_text',
                            'field' => [
                                'display' => 'Link Text',
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'link_target',
                            'field' => [
                                'display' => 'Link Target',
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'is_active',
                            'field' => [
                                'display' => 'Active Link',
                                'type' => 'toggle',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
