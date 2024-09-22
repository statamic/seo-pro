@extends('statamic::layout')
@section('title', $title)
@section('wrapper_class', 'max-w-full')

@section('content')
<seo-pro-link-dashboard
	:initial-report="{{ $report->toJson() }}"
	initial-tab="{{ $tab }}"
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:values='@json($values)'
></seo-pro-link-dashboard>
@stop