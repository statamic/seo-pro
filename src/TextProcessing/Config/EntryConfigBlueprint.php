<?php

namespace Statamic\SeoPro\TextProcessing\Config;

use Statamic\Facades\Blueprint;

class EntryConfigBlueprint
{
    public static function blueprint()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'settings' => [
                    'fields' => [
                        [
                            'handle' => 'can_be_suggested',
                            'field' => [
                                'type' => 'toggle',
                                'default' => true,
                            ],
                        ],
                        [
                            'handle' => 'include_in_reporting',
                            'field' => [
                                'type' => 'toggle',
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
