<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Http\Resources\ListedReport;
use Statamic\SeoPro\Reporting\Report;

class ReportController extends CpController
{
    public function index(Request $request)
    {
        abort_unless(User::current()->can('view seo reports'), 403);

        $reports = Report::all();

        if ($reports->isEmpty()) {
            // todo: empty state
        }

        $columns = [
            Column::make('site_score'),
            Column::make('generated'),
            Column::make('actionable_pages'),
            Column::make('total_pages_crawled'),
        ];

        if ($request->wantsJson()) {
            $perPage = $request->get('perPage');
            $currentPage = $request->get('page', 1);

            $paginated = new LengthAwarePaginator(
                items: $reports->forPage($currentPage, $perPage),
                total: $reports->count(),
                perPage: $perPage,
                currentPage: $currentPage,
            );

            return ListedReport::collection($paginated)->additional([
                'meta' => ['columns' => $columns],
            ]);
        }

        return Inertia::render('seo-pro::Reports/Index', [
            'columns' => $columns,
            'listingUrl' => cp_route('seo-pro.reports.index'),
            'createReportUrl' => cp_route('seo-pro.reports.create'),
            'canDeleteReports' => User::current()->can('delete seo reports'),
        ]);
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

        abort_unless($report = Report::find($id), 404);

        return view('seo-pro::reports.show', ['report' => $report]);
    }

    public function destroy($id)
    {
        abort_unless(User::current()->can('delete seo reports'), 403);

        return Report::find($id)->delete();
    }
}
