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

        if (! $request->ajax()) {
            return view('seo-pro::reports');
        }

        return Report::all();
    }

    public function store()
    {
        abort_unless(User::current()->can('view seo reports'), 403);

        return Report::queue();
    }

    public function show(Request $request, $id)
    {
        abort_unless(User::current()->can('view seo reports'), 403);

        return Report::find($id)->withPages();
    }

    public function destroy($id)
    {
        abort_unless(User::current()->can('delete seo reports'), 403);

        return Report::find($id)->delete();
    }
}
