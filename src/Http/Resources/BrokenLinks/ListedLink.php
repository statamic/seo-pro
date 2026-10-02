<?php

namespace Statamic\SeoPro\Http\Resources\BrokenLinks;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\SeoPro\BrokenLinks\EditUrls;
use Statamic\SeoPro\BrokenLinks\ExternalLink;

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
                ->unique(fn ($reference) => ExternalLink::subjectKey($reference['subject_type'], $reference['subject_id'], $reference['site']))
                ->map(fn ($reference) => [...$reference, 'edit_url' => EditUrls::for($reference)])
                ->filter(fn ($reference) => $reference['edit_url'])
                ->values()
                ->all(),
        ];
    }
}
