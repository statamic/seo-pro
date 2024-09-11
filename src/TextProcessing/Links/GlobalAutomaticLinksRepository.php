<?php

namespace Statamic\SeoPro\TextProcessing\Links;

use Statamic\SeoPro\Contracts\TextProcessing\Links\GlobalAutomaticLinksRepository as GlobalAutomaticLinksContract;
use Statamic\SeoPro\TextProcessing\Models\AutomaticLink;

class GlobalAutomaticLinksRepository implements GlobalAutomaticLinksContract
{
    public function deleteAutomaticLinksForSite(string $handle): void
    {
        AutomaticLink::where('site', $handle)->delete();
    }
}