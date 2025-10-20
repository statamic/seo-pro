<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Statamic\CP\Column;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Http\Resources\Reporting\Report as ReportResource;
use Statamic\SeoPro\Reporting\Report;

class ReportController extends CpController
{
    public function index(Request $request)
    {
        $this->authorize('view seo reports');

        $reports = Report::all();

        if ($reports->isEmpty()) {
            return Inertia::render('seo-pro::Reports/Empty', [
                'createUrl' => cp_route('seo-pro.reports.create'),
            ]);
        }

        $columns = [
            Column::make('site_score')->sortable(false),
            Column::make('generated')->sortable(false),
            Column::make('actionable_pages')->sortable(false),
            Column::make('total_pages_crawled')->sortable(false),
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

            return ReportResource::collection($paginated)->additional([
                'meta' => ['columns' => $columns],
            ]);
        }

        return Inertia::render('seo-pro::Reports/Index', [
            'columns' => $columns,
            'listingUrl' => cp_route('seo-pro.reports.index'),
            'createUrl' => cp_route('seo-pro.reports.create'),
            'canDelete' => User::current()->can('delete seo reports'),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('view seo reports');

        $report = Report::create()->save();

        return redirect()->cpRoute('seo-pro.reports.show', $report->id());
    }

    public function show(Request $request, $id)
    {
        $this->authorize('view seo reports');

        throw_unless($report = Report::find($id), NotFoundHttpException::class);

        $report->generateIfNecessary();

        if ($request->wantsJson()) {
            return $report->data();
        }

        return Inertia::render('seo-pro::Reports/Show', [
            'report' => $report,
            'createReportUrl' => cp_route('seo-pro.reports.create'),
            'pagesUrl' => cp_route('seo-pro.reports.pages.index', $report->id()),
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('delete seo reports');

        return Report::find($id)->delete();
    }
}
