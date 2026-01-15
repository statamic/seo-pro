@extends('statamic::layout')
@section('title', __('seo-pro::messages.seo_reports'))

@section('content')

    <div class="flex items-center mb-6">
        <h1 class="flex-1">{{ __('seo-pro::messages.reports') }}</h1>
        <a href="{{ cp_route('seo-pro.reports.create') }}" class="btn-primary">{{ __('seo-pro::messages.generate_report') }}</a>
    </div>

    <div class="card p-0 overflow-hidden">
        <table class="data-table">
            <thead>
                <th>{{ __('seo-pro::messages.site_score') }}</th>
                <th>{{ __('seo-pro::messages.generated') }}</th>
                <th class="text-right">{{ __('seo-pro::messages.actionable_pages') }}</th>
                <th class="text-right">{{ __('seo-pro::messages.total_pages_crawled') }}</th>
                <th></th>
            </thead>
            <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td class="text-xs whitespace-no-wrap">
                            <div class="flex items-center">
                                <seo-pro-index-score
                                    id="{{ $report->id() }}"
                                    initial-status="{{ $report->status() }}"
                                    initial-score="{{ $report->score() }}"
                                ></seo-pro-index-score>
                            </div>
                        </td>
                        <td>
                            <a href="{{ cp_route('seo-pro.reports.show', $report->id()) }}">{{ $report->date()->diffForHumans() }}</a>
                        </td>
                        <td class="text-right">
                            <a href="{{ cp_route('seo-pro.reports.show', $report->id()) }}">{{ $report->pagesActionable() ?? 'N/A' }}</a>
                        </td>
                        <td class="text-right w-8">
                            <a href="{{ cp_route('seo-pro.reports.show', $report->id()) }}">{{ $report->pagesCrawled() }}</a>
                        </td>
                        @can('delete seo reports')
                            <td class="w-8">
                                <dropdown-list>
                                    <dropdown-item :text="__('seo-pro::messages.delete_report')" class="warning" @click="$refs.deleter_{{ $report->id() }}.confirm()">
                                        <resource-deleter
                                            ref="deleter_{{ $report->id() }}"
                                            :resource-title="__('seo-pro::messages.report')"
                                            route="{{ cp_route('seo-pro.reports.destroy', $report->id()) }}"
                                            redirect="{{ cp_route('seo-pro.reports.index') }}"
                                        ></resource-deleter>
                                    </dropdown-item>
                                </dropdown-list>
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('statamic::partials.docs-callout', [
        'topic' => 'SEO Pro',
        'url' => 'https://statamic.com/addons/statamic/seo-pro'
    ])

@stop
