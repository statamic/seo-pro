@extends('statamic::layout')
@section('title', __('seo-pro::messages.site_linking_behavior'))
@section('wrapper_class', 'max-w-full')

@section('content')
<seo-pro-site-config-listing
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:can-edit-link-collections="@json($can_edit_link_collections)"
	:can-edit-link-sites="@json($can_edit_link_sites)"
></seo-pro-site-config-listing>
@stop