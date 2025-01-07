<?php

namespace Statamic\SeoPro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\CP\Column;
use Statamic\Fields\Blueprint;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;

abstract class ListedResourceCollection extends ResourceCollection
{
    use HasRequestedColumns;

    protected $blueprint;
    protected $columnPreferenceKey;
    protected $columns;

    public function columnPreferenceKey($key)
    {
        $this->columnPreferenceKey = $key;

        return $this;
    }

    public function blueprint(Blueprint $blueprint): static
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    protected function setColumns(): void
    {
        $this->columns = $this->blueprint->columns()->map(function (Column $column) {
            return $column->listable(true)
                ->visible(true)
                ->defaultVisibility(true)
                ->sortable(true);
        });

        if ($this->columnPreferenceKey) {
            $this->columns->setPreferred($this->columnPreferenceKey);
        }

        $this->columns = $this->columns->rejectUnlisted()->values();
    }

    public function toArray($request)
    {
        $this->setColumns();

        return $this->collection->each(function ($resource) {
            $resource
                ->blueprint($this->blueprint)
                ->columns($this->requestedColumns());
        });
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
