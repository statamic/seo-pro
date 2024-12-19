@extends('statamic::layout')
@section('title', __('seo-pro::messages.collection_linking_behavior'))
@section('wrapper_class', 'max-w-full')

@section('content')
<seo-pro-collection-behavior-listing
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:can-edit-link-collections="@json($can_edit_link_collections)"
	:can-edit-link-sites="@json($can_edit_link_sites)"
	:can-edit-global-links="@json($can_edit_global_links)"
></seo-pro-collection-behavior-listing>
@stop