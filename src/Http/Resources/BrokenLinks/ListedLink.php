<?php

namespace Statamic\SeoPro\Http\Resources\BrokenLinks;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\SeoPro\BrokenLinks\Reference;

class ListedLink extends JsonResource
{
    public function toArray($request)
    {
        $link = $this->resource;

        return [
            'id' => $link->id(),
            'url' => $link->url(),
            'broken_since' => $link->brokenSince(),
            'response' => $link->response(),
            'checked_at' => $link->checkedAt(),
            'references' => $link->references()
                ->unique(fn (Reference $reference) => $reference->key())
                ->map(fn (Reference $reference) => ['title' => $reference->title, 'edit_url' => $reference->editUrl()])
                ->filter(fn ($reference) => $reference['edit_url'])
                ->values()
                ->all(),
        ];
    }
}
