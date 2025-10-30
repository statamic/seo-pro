<?php

return [

    'assets' => [
        'container' => null,
        'folder' => null,
        'twitter_preset' => [
            'w' => 1200,
            'h' => 600,
        ],
        'open_graph_preset' => [
            'w' => 1146,
            'h' => 600,
        ],
    ],

    'sitemap' => [
        'enabled' => true,
        'url' => 'sitemap.xml',
        'expire' => 60,
        'pagination' => [
            'enabled' => false,
            'url' => 'sitemap_{page}.xml',
            'limit' => 100,
        ],
    ],

    'humans' => [
        'enabled' => true,
        'url' => 'humans.txt',
    ],

    'pagination' => [
        'enabled_in_canonical_url' => true,
        'enabled_on_first_page' => false,
    ],

    'twitter' => [
        'card' => 'summary_large_image',
    ],

    'alternate_locales' => [
        'enabled' => true,
        'excluded_sites' => [],
    ],

    'reports' => [
        'keep_recent' => 'all',
        'queue_chunk_size' => 1000,
        'title_length' => [
            'warn_min' => 30,
            'pass_max' => 60,
            'warn_max' => 70,
        ],
        'meta_description_length' => [
            'warn_min' => 120,
            'pass_max' => 160,
            'warn_max' => 240,
        ],
    ],

];
