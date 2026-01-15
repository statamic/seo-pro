<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\CP\Column;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Reporting\Report;

class ReportPagesController extends CpController
{
    public function index(Request $request, $id)
    {
        abort_unless(User::current()->can('view seo reports'), 403);

        $data = Report::find($id)->data();

        // If report is still generating,
        // we'll get a report instance with a status for the front end to poll against.
        // When it's finished generating, `data()` will return a collection.
        if ($data instanceof Report) {
            return $data;
        }

        $data['columns'] = [
            Column::make('status')->label(__('Status')),
            Column::make('url')->label(__('URL')),
            Column::make('actionable')->label(__('Actionable'))->sortable(false),
        ];

        $data['sortColumn'] = $request->input('sortColumn', 'status');
        $data['sortDirection'] = $request->input('sortDirection', 'asc');

        $pages = $data['pages']
            ->sortBy(
                callback: fn ($value) => $this->sortablePageValue($value, $data['sortColumn']),
                descending: $data['sortDirection'] === 'desc',
            )
            ->values();

        $currentPage = $request->input('page', 1);
        $perPage = $request->input('perPage', config('statamic.cp.pagination_size'));

        $data['pages'] = new LengthAwarePaginator(
            $pages->forPage($currentPage, $perPage)->values(),
            $pages->count(),
            $perPage,
            $currentPage,
        );

        return $data;
    }

    private function sortablePageValue($value, $column)
    {
        $value = $value[$column];

        if ($column !== 'status') {
            return $value;
        }

        if ($value === 'fail') {
            return '1fail';
        } elseif ($value === 'warning') {
            return '2warning';
        } elseif ($value === 'pass') {
            return '3pass';
        }

        return $value;
    }
}
