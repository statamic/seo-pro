@extends('statamic::layout')
@section('title', __('seo-pro::messages.link_manager'))
@section('wrapper_class', 'max-w-full')

@section('content')
<seo-pro-link-listing
	site="{{ $site }}"
	:filters="{{ $filters->toJson() }}"
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:values='@json($values)'
></seo-pro-link-listing>
@stop