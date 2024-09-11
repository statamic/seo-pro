<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\CP\Columns;
use Statamic\Facades\Site;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;
use Statamic\SeoPro\Http\Resources\BaseResourceCollection;

class EntryLinks extends BaseResourceCollection
{
    use HasRequestedColumns;

    public $collects = ListedEntryLink::class;

    protected function setColumns(): void
    {
        $this->columns = new Columns();

        $this->addColumn('title', 'Title')
            ->addColumn('uri', 'URI')
            ->addColumn('site', 'Site', Site::hasMultiple())
            ->addColumn('collection', 'Collection')
            ->addColumn('internal_link_count', 'Internal Link Count')
            ->addColumn('external_link_count', 'External Link Count')
            ->addColumn('inbound_internal_link_count', 'Inbound Internal Link Count');


        if ($this->columnPreferenceKey) {
            $this->columns->setPreferred($this->columnPreferenceKey);
        }

        $this->columns = $this->columns->rejectUnlisted()->values();
    }
}