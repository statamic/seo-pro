<?php

namespace Statamic\SeoPro\GraphQL;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Types\SiteType;

class SeoProType extends Type
{
    const NAME = 'SeoPro';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'site_name' => [
                'type' => GraphQL::string(),
            ],
            'site_name_position' => [
                'type' => GraphQL::string(),
            ],
            'site_name_separator' => [
                'type' => GraphQL::string(),
            ],
            'title' => [
                'type' => GraphQL::string(),
            ],
            'description' => [
                'type' => GraphQL::string(),
            ],
            'priority' => [
                'type' => GraphQL::string(),
            ],
            'change_frequency' => [
                'type' => GraphQL::string(),
            ],
            'compiled_title' => [
                'type' => GraphQL::string(),
            ],
            'og_title' => [
                'type' => GraphQL::string(),
            ],
            'canonical_url' => [
                'type' => GraphQL::string(),
            ],
            'prev_url' => [
                'type' => GraphQL::string(),
            ],
            'next_url' => [
                'type' => GraphQL::string(),
            ],
            'home_url' => [
                'type' => GraphQL::string(),
            ],
            'humans_txt' => [
                'type' => GraphQL::string(),
            ],
            'site' => [
                'type' => GraphQL::type(SiteType::NAME),
            ],
            'alternate_locales' => [
                'type' => GraphQL::type('Array'),
            ],
            'last_modified' => [
                'type' => GraphQL::string(),
            ],
            'twitter_card' => [
                'type' => GraphQL::string(),
            ],
            'twitter_handle' => [
                'type' => GraphQL::string(),
            ],
            'image' => [
                'type' => GraphQL::type('AssetInterface'),
                'resolve' => function ($value) {
                    return $value['image']->value();
                },
            ],
            'html' => [
                'type' => GraphQL::string(),
                'resolve' => function ($value) {
                    return view('seo-pro::meta', $value)->render();
                },
            ],
        ];
    }
}
