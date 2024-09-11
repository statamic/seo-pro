<?php

namespace Statamic\SeoPro\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filters\Fields as CoreFields;
use Statamic\SeoPro\TextProcessing\Links\LinkBlueprint;

class Fields extends CoreFields
{
    public function visibleTo($key)
    {
        return $key === 'seo_pro.links';
    }

    protected function getBlueprints()
    {
        return collect([
            LinkBlueprint::make(),
        ]);
    }
}