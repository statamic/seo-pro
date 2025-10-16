<?php

namespace Statamic\SeoPro\Widgets;

use Illuminate\Support\Facades\File;
use Statamic\SeoPro\Reporting\Report;
use Statamic\Widgets\Widget;

class SeoProWidget extends Widget
{
    public function html()
    {
        return view('seo-pro::widget', [
            'icon' => File::get(__DIR__.'/../../resources/svg/nav-icon.svg'),
            'reportsUrl' => cp_route('seo-pro.reports.index'),
            'createReportUrl' => cp_route('seo-pro.reports.create'),
            'report' => Report::latestGenerated(),
        ]);
    }
}
