<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Inertia\Inertia;
use Statamic\Facades\File;

class IndexController
{
    public function __invoke()
    {
        return Inertia::render('seo-pro::Index', [
            'icon' => File::get(__DIR__.'/../../../resources/svg/nav-icon.svg'),
            'canViewReports' => auth()->user()->can('view seo reports'),
            'canEditSiteDefaults' => auth()->user()->can('edit seo site defaults'),
            'canEditSectionDefaults' => auth()->user()->can('edit seo section defaults'),
        ]);
    }
}
