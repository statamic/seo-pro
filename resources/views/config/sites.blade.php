@extends('statamic::layout')
@section('title', 'Site Linking Configuration')

@section('content')
<seo-pro-site-config-listing
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:values='@json($values)'
></seo-pro-site-config-listing>
@stop