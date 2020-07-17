<?php

return [

    'defaults' => [
        'title' => '@seo:title',
        'description' => '@seo:content',
        'site_name' => 'Site Name',
        'site_name_position' => 'after',
        'site_name_separator' => '|',
        'priority' => '0.5',
        'change_frequency' => 'monthly',
        // 'image' => '@seo:hero_image',
        // 'robots' => ['nofollow', 'noindex'],
        // 'twitter_site' => 'twitter_handle',
        // 'bing_verification' => 'verification_code',
        // 'google_verification' => 'verification_code',
    ],

    'assets' => [
        'container' => null,
        'open_graph_preset' => [
            'w' => 1200,
            'h' => 1200,
            'fit' => 'crop',
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

];
