@extends('statamic::layout')

@section('content')

    @if (isset($breadcrumbTitle) && isset($breadcrumbUrl))
        <header>
            @include('statamic::partials.breadcrumb', [
                'url' => $breadcrumbUrl,
                'title' => $breadcrumbTitle
            ])
            <h1>@yield('title')</h1>
        </header>
    @endif

    <publish-form
        title="{{ $title }}"
        action="{{ $action }}"
        :blueprint='@json($blueprint)'
        :meta='@json($meta)'
        :values='@json($values)'
    ></publish-form>

{{--    @include('statamic::partials.docs-callout', [--}}
{{--        'topic' => 'SEO Pro',--}}
{{--        'url' => 'https://statamic.com/addons/statamic/seo-pro'--}}
{{--    ])--}}
@stop
