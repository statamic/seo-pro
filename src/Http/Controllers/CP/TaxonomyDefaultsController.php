<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Statamic\Facades\Taxonomy;

class TaxonomyDefaultsController extends BaseSectionDefaultsController
{
    protected static $sectionType = 'taxonomies';

    protected function getSectionItem($handle)
    {
        return Taxonomy::find($handle);
    }
}
