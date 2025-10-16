<?php

namespace Statamic\SeoPro\Http\Controllers;

use Inertia\Inertia;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\User;

class SectionDefaultsController
{
    public function index()
    {
        abort_unless(User::current()->can('edit seo section defaults'), 403);

        return Inertia::render('seo-pro::SectionDefaults/Index', [
            'collections' => Collection::all(),
            'taxonomies' => Taxonomy::all(),
        ]);
    }
}