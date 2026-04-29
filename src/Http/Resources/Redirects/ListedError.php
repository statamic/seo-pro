<?php

namespace Statamic\SeoPro\Http\Resources\Redirects;

use Illuminate\Http\Resources\Json\JsonResource;

class ListedError extends JsonResource
{
    protected $blueprint;

    protected $columns;

    public function blueprint($blueprint)
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    public function columns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function toArray($request)
    {
        $error = $this->resource;

        return [
            'id' => $error->id(),

            $this->merge($this->values([
                'url' => $error->url(),
                'hits' => $error->hits(),
                'last_hit_at' => $error->lastHitAt(),
            ])),

            'create_redirect_url' => cp_route('seo-pro.redirects.create', [
                'source' => $error->url(),
            ]),
        ];
    }

    protected function values($extra = [])
    {
        return $this->columns->mapWithKeys(function ($column) use ($extra) {
            $key = $column->field;
            $field = $this->blueprint->field($key);

            $value = $extra[$key] ?? $this->resource->get($key) ?? $field?->defaultValue();

            if (! $field) {
                return [$key => $value];
            }

            $value = $field->setValue($value)
                ->setParent($this->resource)
                ->preProcessIndex()
                ->value();

            return [$key => $value];
        });
    }
}
