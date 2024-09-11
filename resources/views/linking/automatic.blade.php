@extends('statamic::layout')
@section('title', 'Automatic Global Links')
@section('wrapper_class', 'max-w-full')

@section('content')
<seo-pro-automatic-links-listing
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:values='@json($values)'
	site="{{ $site }}"
></seo-pro-automatic-links-listing>
@stop