<?php

namespace Statamic\SeoPro\Http\Controllers;

use Statamic\Facades\Taxonomy;

class TaxonomyDefaultsController extends SectionDefaultsController
{
    protected static $sectionType = 'taxonomies';

    protected function getSectionItem($handle)
    {
        return Taxonomy::find($handle);
    }
}
