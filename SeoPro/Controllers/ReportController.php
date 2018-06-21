<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\Addons\SeoPro\Reporting\Report;

class ReportController extends Controller
{
    public function index()
    {
        return $this->view('report', [
            'title' => 'SEO Report'
        ]);
    }

    public function summary()
    {
        return Report::create()->withPages()->generate();
    }
}
