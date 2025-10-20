<?php

namespace Statamic\SeoPro\Http\Resources\Reporting;

use Illuminate\Http\Resources\Json\JsonResource;

class Page extends JsonResource
{
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}