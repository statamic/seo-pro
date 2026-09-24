<?php

namespace Statamic\SeoPro\Http\Controllers\CP\DeadLinks;

use Illuminate\Support\Collection;
use Statamic\Http\Controllers\CP\ActionController;
use Statamic\SeoPro\Facades;

class DeadLinkActionController extends ActionController
{
    protected function getSelectedItems($items, $context): Collection
    {
        return $items->map(fn ($id) => Facades\DeadLink::find($id));
    }
}
