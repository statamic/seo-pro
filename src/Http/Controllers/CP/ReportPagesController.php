<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Statamic\CP\Column;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Http\Resources\Reporting\Page as PageResource;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\Support\Arr;

class ReportPagesController extends CpController
{
    public function index(Request $request, $id)
    {
        $this->authorize('view seo reports');

        throw_unless($report = Report::find($id), NotFoundHttpException::class);

        $sortField = $request->input('sort', 'status');
        $sortDirection = $request->input('order', 'asc');

        $pages = collect($report->pages())
            ->sortBy(
                callback: fn ($page) => $this->sortablePageValue($page, $sortField),
                descending: $sortDirection === 'desc',
            )
            ->values();

        $currentPage = $request->input('page', 1);
        $perPage = $request->input('perPage', config('statamic.cp.pagination_size'));

        $paginator = new LengthAwarePaginator(
            $pages->forPage($currentPage, $perPage)->values(),
            $pages->count(),
            $perPage,
            $currentPage,
        );

        return PageResource::collection($paginator)->additional([
            'meta' => [
                'columns' => [
                    Column::make('status')->label(__('Status')),
                    Column::make('url')->label(__('URL')),
                    Column::make('actionable')->label(__('Actionable'))->sortable(false),
                ],
            ],
        ]);
    }

    private function sortablePageValue(Page $page, string $column): ?string
    {
        if ($column === 'status') {
            return match ($page->status()) {
                'fail' => '1fail',
                'warning' => '2warning',
                'pass' => '3pass',
            };
        }

        return Arr::get($page->toArray(), $column);
    }
}
