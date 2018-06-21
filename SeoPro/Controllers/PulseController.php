<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\Addons\SeoPro\Reporting\Report;

class PulseController extends Controller
{
    public function index()
    {
        return $this->view('pulse', [
            'title' => 'Pulse'
        ]);
    }

    public function summary()
    {
        return Report::create()->withPages()->generate();
    }
}
