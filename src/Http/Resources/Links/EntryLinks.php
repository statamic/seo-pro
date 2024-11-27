<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\CP\Column;
use Statamic\Fields\Blueprint;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;
use Statamic\SeoPro\Http\Resources\BaseResourceCollection;

class EntryLinks extends BaseResourceCollection
{
    use HasRequestedColumns;

    protected $blueprint;
    public $collects = ListedEntryLink::class;

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

        return $this->collection->each(function (ListedEntryLink $link) {
            $link
                ->blueprint($this->blueprint)
                ->columns($this->requestedColumns());
        });
    }
}
