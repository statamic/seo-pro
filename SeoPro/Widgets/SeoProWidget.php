<?php

namespace Statamic\Addons\SeoPro\Widgets;

use Statamic\Extend\Widget;
use Statamic\Addons\SeoPro\Reporting\Report;

class SeoProWidget extends Widget
{
    public function html()
    {
        return $this->view('widget', [
            'report' => Report::latest()
        ]);
    }
}
