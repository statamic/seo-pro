<?php

namespace Statamic\SeoPro\GraphQL;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Types\SiteType;

class AlternateLocaleType extends Type
{
    const NAME = 'AlternateLocaleType';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'site' => [
                'type' => GraphQL::nonNull(GraphQL::type(SiteType::NAME)),
            ],
            'url' => [
                'type' => GraphQL::nonNull(GraphQL::string()),
            ],
        ];
    }
}
