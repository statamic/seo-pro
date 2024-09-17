<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\SeoPro\TextProcessing\Models\AutomaticLink;

class ListedAutomaticLink extends JsonResource
{
    protected $columns;

    public function columns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function toArray($request)
    {
        /** @var AutomaticLink $link */
        $link = $this->resource;

        return [
            'id' => $link->id,
            'site' => $link->site,
            'is_active' => $link->is_active,
            'link_text' => $link->link_text,
            'link_target' => $link->link_target,
            'entry_id' => $link->entry_id,
        ];
    }
}
