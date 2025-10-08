<?php

namespace Statamic\SeoPro\Http\Controllers;

use Inertia\Inertia;

class IndexController
{
    public function __invoke()
    {
        return Inertia::render('SEOPro/Index', [
            'canViewReports' => auth()->user()->can('view seo reports'),
            'canEditSiteDefaults' => auth()->user()->can('edit seo site defaults'),
            'canEditSectionDefaults' => auth()->user()->can('edit seo section defaults'),
        ]);
    }
}