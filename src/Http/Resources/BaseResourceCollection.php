<?php

namespace Statamic\SeoPro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\CP\Column;

abstract class BaseResourceCollection extends ResourceCollection
{
    protected $columnPreferenceKey;
    protected $columns;

    public function columnPreferenceKey($key)
    {
        $this->columnPreferenceKey = $key;

        return $this;
    }

    protected function makeColumn(string $field, string $label, bool $visible = true): Column
    {
        return Column::make($field)
            ->listable(true)
            ->label($label)
            ->visible($visible)
            ->defaultVisibility(true)
            ->defaultOrder($this->columns->count() + 1)
            ->sortable(true);
    }

    protected function addColumn(string $field, string $label, bool $visible = true): static
    {
        $this->columns->put($field, $this->makeColumn($field, $label, $visible));

        return $this;
    }

    abstract protected function setColumns(): void;

    public function toArray($request)
    {
        $this->setColumns();

        return $this->collection;
    }

    public function with(Request $request)
    {
        return [
            'meta' => [
                'columns' => $this->visibleColumns(),
            ],
        ];
    }
}
