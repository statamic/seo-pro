<?php

namespace Statamic\SeoPro\GraphQL;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Fields\DateField;
use Statamic\GraphQL\Types\SiteType;
use Statamic\SeoPro\RendersMetaHtml;

class SeoProType extends Type
{
    use RendersMetaHtml;

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
                'type' => GraphQL::float(),
                'resolve' => function ($meta) {
                    return (float) $meta['priority']->value();
                },
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
                'type' => GraphQL::listOf(GraphQL::type(AlternateLocaleType::NAME)),
            ],
            'last_modified' => (new DateField)->setValueResolver(function ($meta) {
                return $meta['last_modified'];
            }),
            'twitter_card' => [
                'type' => GraphQL::string(),
            ],
            'twitter_description' => [
                'type' => GraphQL::string(),
            ],
            'twitter_handle' => [
                'type' => GraphQL::string(),
            ],
            'twitter_title' => [
                'type' => GraphQL::string(),
            ],
            'image' => [
                'type' => GraphQL::type('AssetInterface'),
                'resolve' => function ($meta) {
                    return optional($meta['image'] ?? null)->value();
                },
            ],
            'html' => [
                'type' => GraphQL::string(),
                'resolve' => function ($meta) {
                    return $this->renderMetaHtml($meta);
                },
            ],
        ];
    }
}
