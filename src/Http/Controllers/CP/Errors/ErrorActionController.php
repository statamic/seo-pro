<?php

namespace Statamic\SeoPro\Http\Controllers\CP\Errors;

use Illuminate\Support\Collection;
use Statamic\Http\Controllers\CP\ActionController;
use Statamic\SeoPro\Facades;

class ErrorActionController extends ActionController
{
    protected function getSelectedItems($items, $context): Collection
    {
        return $items->map(fn ($id) => Facades\Error::find($id));
    }
}
