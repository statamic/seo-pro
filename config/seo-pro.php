<?php

return [

    'site_defaults' => [
        'path' => base_path('content/seo.yaml'),
    ],

    'assets' => [
        'container' => null,
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
        'queue_chunk_size' => 1000,
    ],

];
