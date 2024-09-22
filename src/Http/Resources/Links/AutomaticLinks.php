<?php

namespace Statamic\SeoPro\Http\Resources\Links;

use Statamic\CP\Columns;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;
use Statamic\SeoPro\Http\Resources\BaseResourceCollection;

class AutomaticLinks extends BaseResourceCollection
{
    use HasRequestedColumns;

    public $collects = ListedAutomaticLink::class;

    protected function setColumns(): void
    {
        $this->columns = new Columns;

        $this->addColumn('link_text', 'Link Text')
            ->addColumn('link_target', 'Link Target')
            ->addColumn('is_active', 'Active')
            ->addColumn('site', 'Site');

        if ($this->columnPreferenceKey) {
            $this->columns->setPreferred($this->columnPreferenceKey);
        }

        $this->columns = $this->columns->rejectUnlisted()->values();
    }
}
