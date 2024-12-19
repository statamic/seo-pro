<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResource;

class ListedCollectionConfig extends ListedResource
{
    public function values($request): array
    {
        return [
            'handle' => $this->resource->handle,
            'title' => $this->resource->title,
        ];
    }
}
