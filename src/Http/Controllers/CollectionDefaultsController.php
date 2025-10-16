<?php

namespace Statamic\SeoPro\Http\Controllers;

use Statamic\Facades\Collection;

class CollectionDefaultsController extends BaseSectionDefaultsController
{
    protected static $sectionType = 'collections';

    protected function getSectionItem($handle)
    {
        return Collection::find($handle);
    }
}
