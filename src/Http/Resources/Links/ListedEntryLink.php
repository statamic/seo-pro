<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Fields\Blueprint;

class ListedEntryLink extends JsonResource
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

    public function toArray($request)
    {
        $link = $this->resource;

        $values = $this->columns->mapWithKeys(function ($column) {
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

        return [
            'id' => $link->id,
            'entry_id' => $link->entry_id,
            $this->merge($values),
        ];
    }
}
