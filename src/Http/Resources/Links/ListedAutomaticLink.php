<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResource;

class ListedAutomaticLink extends ListedResource
{
    public function values($request): array
    {
        return [
            'id' => $this->resource->id,
            'site' => $this->resource->site,
            'is_active' => $this->resource->is_active,
            'entry_id' => $this->resource->entry_id,
        ];
    }
}
