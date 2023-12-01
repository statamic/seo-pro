@extends('statamic::layout')
@section('title', __('seo-pro::messages.seo_reports'))

@section('content')

    <div class="flex items-center mb-6">
        <h1 class="flex-1">{{ __('seo-pro::messages.reports') }}</h1>
        <a href="{{ cp_route('seo-pro.reports.create') }}" class="btn-primary">{{ __('seo-pro::messages.generate_report') }}</a>
    </div>

    <div class="card p-0 overflow-hidden">
        <table class="data-table">
            <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td class="text-xs whitespace-no-wrap">
                            <div class="flex items-center">
                                <seo-status-icon status="{{ $report->status() }}" class="mr-3"></seo-status-icon>
                                {{ $report->score() }}%
                            </div>
                        </td>
                        <td>
                            <a href="{{ cp_route('seo-pro.reports.show', $report->id()) }}">{{ $report->date()->diffForHumans() }}</a>
                        </td>
                        @can('delete seo reports')
                            <td class="float-right">
                                <dropdown-list>
                                    <dropdown-item :text="__('seo-pro::messages.delete_report')" class="warning" @click="$refs.deleter.confirm()">
                                        <resource-deleter
                                            ref="deleter"
                                            resource-title="Report"
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
