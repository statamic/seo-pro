<?php

namespace Statamic\SeoPro\Http\Controllers\CP\Redirects;

use Illuminate\Support\Collection;
use Statamic\Http\Controllers\CP\ActionController;
use Statamic\SeoPro\Facades;

class RedirectActionController extends ActionController
{
    protected function getSelectedItems($items, $context): Collection
    {
        return $items->map(fn ($id) => Facades\Redirect::find($id));
    }
}
