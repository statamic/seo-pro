@extends('statamic::layout')
@section('title', 'SEO Pro')

@section('content')

    <header class="mb-6">
        <h1>{{ 'SEO Pro' }}</h1>
    </header>

    <div class="card p-4 content">
        <div class="flex flex-wrap">
            @can('view seo reports')
                <a href="{{ cp_route('seo-pro.reports.index') }}" class="w-full lg:w-1/2 p-4 md:flex items-start hover:bg-gray-200 rounded-md group">
                    <div class="h-8 w-8 mr-4 text-gray-800">
                        @cp_svg('icons/light/charts')
                    </div>
                    <div class="text-blue flex-1 mb-4 md:mb-0 md:mr-6">
                        <h3>{{ __('seo-pro::messages.reports') }}</h3>
                        <p class="text-xs">{{ __('seo-pro::messages.seo_reports_description') }}</p>
                    </div>
                </a>
            @endcan
            @can('edit seo site defaults')
                <a href="{{ cp_route('seo-pro.site-defaults.edit') }}" class="w-full lg:w-1/2 p-4 md:flex items-start hover:bg-gray-200 rounded-md group">
                    <div class="h-8 w-8 mr-4 text-gray-800">
                        @cp_svg('icons/light/hammer-wrench')
                    </div>
                    <div class="text-blue flex-1 mb-4 md:mb-0 md:mr-6">
                        <h3>{{ __('seo-pro::messages.site_defaults') }}</h3>
                        <p class="text-xs">{{ __('seo-pro::messages.site_defaults_description') }}</p>
                    </div>
                </a>
            @endcan
            @can('edit seo section defaults')
                <a href="{{ cp_route('seo-pro.section-defaults.index') }}" class="w-full lg:w-1/2 p-4 md:flex items-start hover:bg-gray-200 rounded-md group">
                    <div class="h-8 w-8 mr-4 text-gray-800">
                        @cp_svg('icons/light/hammer-wrench')
                    </div>
                    <div class="text-blue flex-1 mb-4 md:mb-0 md:mr-6">
                        <h3>{{ __('seo-pro::messages.section_defaults') }}</h3>
                        <p class="text-xs">{{ __('seo-pro::messages.section_defaults_description') }}</p>
                    </div>
                </a>
            @endcan
            @if (config('statamic.seo-pro.text_analysis.enabled', false))
                @can('view seo links')
                    <a href="{{ cp_route('seo-pro.internal-links.index') }}" class="w-full lg:w-1/2 p-4 md:flex items-start hover:bg-gray-200 rounded-md group">
                        <div class="h-8 w-8 mr-4 text-gray-800">
                            @cp_svg('icons/light/link')
                        </div>
                        <div class="text-blue flex-1 mb-4 md:mb-0 md:mr-6">
                            <h3>{{ __('seo-pro::messages.link_manager') }}</h3>
                            <p class="text-xs">{{ __('seo-pro::messages.links_description') }}</p>
                        </div>
                    </a>
                @endcan
            @endif
        </div>
    </div>

    @include('statamic::partials.docs-callout', [
        'topic' => 'SEO Pro',
        'url' => 'https://statamic.com/addons/statamic/seo-pro'
    ])

@endsection
