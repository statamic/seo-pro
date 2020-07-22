<?php

namespace Statamic\SeoPro\Http\Controllers;

use Statamic\Facades\Collection;

class CollectionDefaultsController extends SectionDefaultsController
{
    protected static $sectionType = 'collections';

    protected function getSectionItem($handle)
    {
        return Collection::find($handle);
    }
}
