<?php

namespace Statamic\SeoPro\Http\Resources\Redirects;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\User;

class ListedRedirect extends JsonResource
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
        $redirect = $this->resource;

        return [
            'id' => $redirect->id(),
            'status' => $redirect->status(),

            $this->merge($this->values([
                'source' => $redirect->source(),
                'destination' => $redirect->destination(),
                'response_code' => $redirect->responseCode(),
                'hits' => $redirect->hits(),
                'last_hit_at' => $redirect->lastHitAt(),
            ])),

            'edit_url' => $redirect->editUrl(),
            'delete_url' => $redirect->deleteUrl(),
            'editable' => User::current()->can('edit', $redirect),
            'deletable' => User::current()->can('delete', $redirect),
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
