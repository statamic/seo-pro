<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Illuminate\Http\Resources\Json\JsonResource;

class ListedEntryLink extends JsonResource
{
    protected $columns;

    public function columns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function toArray($request)
    {
        $link = $this->resource;

        return [
            'id' => $link->id,
            'entry_id' => $link->entry_id,
            'title' => $link->cached_title,
            'uri' => $link->cached_uri,
            'site' => $link->site,
            'collection' => $link->collection,
            'internal_link_count' => $link->internal_link_count,
            'external_link_count' => $link->external_link_count,
            'inbound_internal_link_count' => $link->inbound_internal_link_count,
        ];
    }
}