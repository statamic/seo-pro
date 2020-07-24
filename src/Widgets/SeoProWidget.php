<?php

namespace Statamic\SeoPro\Widgets;

use Statamic\Widgets\Widget;
use Statamic\SeoPro\Reporting\Report;

class SeoProWidget extends Widget
{
    public function html()
    {
        return view('seo-pro::widget', [
            'report' => Report::latest()
        ]);
    }
}
