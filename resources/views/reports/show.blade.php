@extends('statamic::layout')
@section('title', __('seo-pro::messages.seo_reports'))

@section('content')

    @include('statamic::partials.breadcrumb', [
        'url' => cp_route('seo-pro.reports.index'),
        'title' => __('seo-pro::messages.reports'),
    ])

    <seo-pro-report id="{{ $report->id() }}"></seo-pro-report>

    @include('statamic::partials.docs-callout', [
        'topic' => 'SEO Pro',
        'url' => 'https://statamic.com/addons/statamic/seo-pro'
    ])

@stop
