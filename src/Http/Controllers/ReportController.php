<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Illuminate\Http\Request;
use Statamic\Addons\SeoPro\Reporting\Report;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->ajax()) {
            return $this->view('reports', [
                'title' => 'SEO Reports',
            ]);
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
}
