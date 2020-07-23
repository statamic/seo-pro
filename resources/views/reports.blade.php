@extends('statamic::layout')
@section('title', __('seo-pro::messages.seo_reports'))

@section('content')
    <seo-reports
        listing-route="{{ cp_route('seo-pro.reports.index') }}"
        generate-route="{{ cp_route('seo-pro.reports.store') }}"
    ></seo-reports>
@stop
