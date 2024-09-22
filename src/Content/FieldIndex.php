<?php

namespace Statamic\SeoPro\Content;

use Illuminate\Database\Eloquent\Builder;
use Statamic\Facades\Entry;
use Statamic\SeoPro\TextProcessing\Models\EntryLink;

class FieldIndex
{
    protected Builder $query;

    public function __construct()
    {
        $this->query = EntryLink::query();
    }

    public function query()
    {
        $this->query = EntryLink::query();

        return $this;
    }

    public function inCollection(string $collection): static
    {
        $this->query = $this->query->where('collection', $collection);

        return $this;
    }

    public function havingFieldType(string $type): static
    {
        $this->query = $this->query
            ->where('content_mapping', 'LIKE', '%"last_fieldtype":"'.$type.'"%');

        return $this;
    }

    public function havingFieldWithType(string $handle, string $fieldType): static
    {
        $search = $handle.'{type:'.$fieldType.'}';

        $this->query = $this->query
            ->where('content_mapping', 'LIKE', '%"fqn_path":"%'.$search.'%"%');

        return $this;
    }

    public function whereValue(string $value): static
    {
        $this->query = $this->query
            ->where('content_mapping', 'LIKE', '%"value":"'.$value.'"%');

        return $this;
    }

    public function entries()
    {
        $ids = $this->query->select('entry_id')->distinct()->get()->pluck('entry_id')->all();

        return Entry::query()->whereIn('id', $ids)->get();
    }
}
