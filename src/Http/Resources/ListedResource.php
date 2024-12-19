<?php

namespace Statamic\SeoPro\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Fields\Blueprint;

abstract class ListedResource extends JsonResource
{
    protected $blueprint;
    protected $columns;

    public function blueprint(Blueprint $blueprint): static
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    public function columns($columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    abstract public function values($request): array;

    public function toArray($request)
    {
        return [
            $this->merge($this->values($request)),
            $this->merge($this->resourceValues()),
        ];
    }

    protected function resourceValues()
    {
        return $this->columns->mapWithKeys(function ($column) {
            $key = $column->field;
            $field = $this->blueprint->field($key);
            $value = $this->resource->{$key};

            if (! $field) {
                return [$key => $value];
            }

            $value = $field
                ->setValue($value)
                ->setParent($this->resource)
                ->preProcessIndex()
                ->value();

            return [$key => $value];
        });
    }
}
