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
	:can-edit-link-collections="@json($can_edit_link_collections)"
	:can-edit-link-sites="@json($can_edit_link_sites)"
></seo-pro-link-listing>
@stop