<?php

namespace Statamic\SeoPro\Http\Resources\Reporting;

use Illuminate\Http\Resources\Json\JsonResource;

class Report extends JsonResource
{
    public function toArray($request)
    {
        return [
            ...$this->resource->toArray(),
            'date' => $this->resource->date()->toIso8601ZuluString('millisecond'),
            'url' => cp_route('seo-pro.reports.show', $this->resource->id()),
            'delete_url' => cp_route('seo-pro.reports.destroy', $this->resource->id()),
        ];
    }
}