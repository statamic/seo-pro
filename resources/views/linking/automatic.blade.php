@extends('statamic::layout')
@section('title', __('seo-pro::messages.global_automatic_links'))
@section('wrapper_class', 'max-w-full')

@section('content')
<seo-pro-automatic-links-listing
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:initial-values='@json($values)'
	:can-edit-link-collections="@json($can_edit_link_collections)"
	:can-edit-link-sites="@json($can_edit_link_sites)"
	:can-edit-global-links="@json($can_edit_global_links)"
></seo-pro-automatic-links-listing>
@stop