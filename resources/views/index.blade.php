@extends('statamic::layout')
@section('title', 'SEO Pro')

@section('content')

    <header class="mb-6">
        <h1>{{ 'SEO Pro' }}</h1>
    </header>

    <div class="card p-4 content">
        <div class="flex flex-wrap">
            @can('view seo reports')
                <a href="{{ cp_route('seo-pro.reports.index') }}" class="w-full lg:w-1/2 p-4 flex items-start hover:bg-gray-200 dark:hover:bg-dark-550 rounded-md group">
                    <div class="h-8 w-8 rtl:ml-4 ltr:mr-4 text-gray-800 dark:text-dark-175">
                        @cp_svg('icons/light/charts')
                    </div>
                    <div class="text-blue flex-1 mb-4 md:mb-0 md:mr-6">
                        <h3>{{ __('seo-pro::messages.reports') }}</h3>
                        <p class="text-xs">{{ __('seo-pro::messages.seo_reports_description') }}</p>
                    </div>
                </a>
            @endcan
            @can('edit seo site defaults')
                <a href="{{ cp_route('seo-pro.site-defaults.edit') }}" class="w-full lg:w-1/2 p-4 flex items-start hover:bg-gray-200 dark:hover:bg-dark-550 rounded-md group">
                    <div class="h-8 w-8 rtl:ml-4 ltr:mr-4 text-gray-800 dark:text-dark-175">
                        @cp_svg('icons/light/hammer-wrench')
                    </div>
                    <div class="text-blue flex-1 mb-4 md:mb-0 md:mr-6">
                        <h3>{{ __('seo-pro::messages.site_defaults') }}</h3>
                        <p class="text-xs">{{ __('seo-pro::messages.site_defaults_description') }}</p>
                    </div>
                </a>
            @endcan
            @can('edit seo section defaults')
                <a href="{{ cp_route('seo-pro.section-defaults.index') }}" class="w-full lg:w-1/2 p-4 flex items-start hover:bg-gray-200 dark:hover:bg-dark-550 rounded-md group">
                    <div class="h-8 w-8 rtl:ml-4 ltr:mr-4 text-gray-800 dark:text-dark-175">
                        @cp_svg('icons/light/hammer-wrench')
                    </div>
                    <div class="text-blue flex-1 mb-4 md:mb-0 md:mr-6">
                        <h3>{{ __('seo-pro::messages.section_defaults') }}</h3>
                        <p class="text-xs">{{ __('seo-pro::messages.section_defaults_description') }}</p>
                    </div>
                </a>
            @endcan
        </div>
    </div>

    @include('statamic::partials.docs-callout', [
        'topic' => 'SEO Pro',
        'url' => 'https://statamic.com/addons/statamic/seo-pro'
    ])

@endsection
