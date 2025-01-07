<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResource;

class ListedSiteConfig extends ListedResource
{
    public function values($request): array
    {
        return [
            'handle' => $this->resource->handle,
            'name' => $this->resource->name,
        ];
    }
}
