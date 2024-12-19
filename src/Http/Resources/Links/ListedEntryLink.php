<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\SeoPro\Http\Resources\ListedResource;

class ListedEntryLink extends ListedResource
{
    public function values($request): array
    {
        return [
            'id' => $this->resource->id,
            'entry_id' => $this->resource->entry_id,
        ];
    }
}
