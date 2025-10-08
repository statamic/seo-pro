<?php

namespace Statamic\SeoPro\Http\Controllers;

use Inertia\Inertia;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;

class SectionDefaultsController
{
    public function index()
    {
        return Inertia::render('SEOPro/SectionDefaults/Index', [
            'collections' => Collection::all(),
            'taxonomies' => Taxonomy::all(),
        ]);
    }
}