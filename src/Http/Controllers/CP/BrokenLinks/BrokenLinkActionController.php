<?php

namespace Statamic\SeoPro\Http\Controllers\CP\BrokenLinks;

use Illuminate\Support\Collection;
use Statamic\Http\Controllers\CP\ActionController;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\Facades;

class BrokenLinkActionController extends ActionController
{
    protected function getSelectedItems($items, $context): Collection
    {
        return $items->map(fn (int|string $id): ?ExternalLink => Facades\ExternalLink::find($id));
    }
}
