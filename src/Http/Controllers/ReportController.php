<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Reporting\Report;

class ReportController extends CpController
{
    public function index(Request $request)
    {
        abort_unless(User::current()->can('view seo reports'), 403);

        $reports = Report::all();

        return $reports->isNotEmpty()
            ? view('seo-pro::reports.index', ['reports' => $reports])
            : view('seo-pro::reports.create');
    }

    public function create(Request $request)
    {
        abort_unless(User::current()->can('view seo reports'), 403);

        $report = Report::create()->save();

        return redirect()->cpRoute('seo-pro.reports.show', $report->id());
    }

    public function show(Request $request, $id)
    {
        abort_unless(User::current()->can('view seo reports'), 403);

        $report = Report::find($id)->withPages();

        return view('seo-pro::reports.show', ['report' => $report]);
    }

    public function destroy($id)
    {
        abort_unless(User::current()->can('delete seo reports'), 403);

        return Report::find($id)->delete();
    }
}
