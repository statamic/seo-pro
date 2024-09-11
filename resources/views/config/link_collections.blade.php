@extends('statamic::layout')
@section('title', 'A better title will surely appear')
@section('wrapper_class', 'max-w-full')

@section('content')
<seo-pro-collection-behavior-listing
	:blueprint='@json($blueprint)'
	:fields='@json($fields)'
	:meta='@json($meta)'
	:values='@json($values)'
></seo-pro-collection-behavior-listing>
@stop