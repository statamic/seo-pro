<?php

return [

    'assets' => [
        'container' => null,
        'open_graph_preset' => [
            'w' => 1200,
            'h' => 1200,
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

];
