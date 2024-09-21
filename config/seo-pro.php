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
        'queue_chunk_size' => 1000,
    ],

    'jobs' => [
        'connection' => env('SEO_PRO_JOB_CONNECTION'),
        'queue' => env('SEO_PRO_JOB_QUEUE'),
    ],

    'text_analysis' => [

        'enabled' => false,

        'openai' => [
            'api_key' => env('SEO_PRO_OPENAI_API_KEY'),
            'model' => 'text-embedding-3-small',
            'token_limit' => 8000,
        ],

        'keyword_threshold' => 65,

        'prevent_circular_links' => false,

        'internal_links' => [
            'min_desired' => 3,
            'max_desired' => 6,
        ],

        'external_links' => [
            'min_desired' => 0,
            'max_desired' => 3,
        ],

        'rake' => [
            'phrase_min_length' => 0,
            'filter_numerics' => true,
        ],

        'drivers' => [
            'embeddings' => \Statamic\SeoPro\TextProcessing\Embeddings\OpenAiEmbeddings::class,
            'keywords' => \Statamic\SeoPro\TextProcessing\Keywords\Rake::class,
            'tokenizer' => \Statamic\SeoPro\TextProcessing\Content\Tokenizer::class,
            'content' => \Statamic\SeoPro\TextProcessing\Content\ContentRetriever::class,
            'link_scanner' => \Statamic\SeoPro\TextProcessing\Links\LinkCrawler::class,
        ],

        'disabled_collections' => [
        ],

    ],
];
