<?php

namespace Statamic\SeoPro\TextProcessing\Links;

use Illuminate\Support\Collection;
use Statamic\SeoPro\Contracts\TextProcessing\Links\GlobalAutomaticLinksRepository as GlobalAutomaticLinksContract;
use Statamic\SeoPro\TextProcessing\Models\AutomaticLink;

class GlobalAutomaticLinksRepository implements GlobalAutomaticLinksContract
{
    public function deleteAutomaticLinksForSite(string $handle): void
    {
        AutomaticLink::query()
            ->where('site', $handle)
            ->delete();
    }

    public function getLinksForSite(string $handle): Collection
    {
        return AutomaticLink::query()
            ->where('is_active', true)
            ->where('site', $handle)
            ->get();
    }
}