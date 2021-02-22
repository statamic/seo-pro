<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Reporting\Report;

class ReportController extends CpController
{
    public function index(Request $request)
    {
        if (! $request->ajax()) {
            return view('seo-pro::reports');
        }

        return Report::all();
    }

    public function store()
    {
        return Report::queue();
    }

    public function show(Request $request, $id)
    {
        return Report::find($id)->withPages();
    }

    public function destroy($id)
    {
        return Report::find($id)->delete();
    }
}
