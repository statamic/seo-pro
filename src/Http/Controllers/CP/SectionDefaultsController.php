<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Inertia\Inertia;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\Http\Controllers\CP\CpController;

class SectionDefaultsController extends CpController
{
    public function index()
    {
        $this->authorize('edit seo section defaults');

        return Inertia::render('seo-pro::SectionDefaults/Index', [
            'collections' => Collection::all()->sortBy('title')->values(),
            'taxonomies' => Taxonomy::all()->sortBy('title')->values(),
        ]);
    }
}
