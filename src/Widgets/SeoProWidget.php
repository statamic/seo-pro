<?php

namespace Statamic\SeoPro\Widgets;

use Statamic\SeoPro\Reporting\Report;
use Statamic\Widgets\Widget;

class SeoProWidget extends Widget
{
    public function html()
    {
        return view('seo-pro::widget', [
            'report' => Report::latest(),
        ]);
    }
}
