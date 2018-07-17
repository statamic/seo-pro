<?php

namespace Statamic\Addons\SeoPro\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        return redirect()->route('seopro.reports.index');
    }
}
