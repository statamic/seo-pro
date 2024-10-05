@extends('statamic::layout')
@section('title', __('seo-pro::messages.section_defaults'))

@section('content')

    <div class="flex items-center mb-6">
        <h1 class="flex-1">{{ __('seo-pro::messages.section_defaults') }}</h1>
    </div>

    @if (Statamic\Facades\Collection::all()->count())
        <h3 class="little-heading pl-0 mb-2">{{ __('Collections') }}</h3>
        <div class="card p-0 mb-4">
            <table class="data-table">
                @foreach (Statamic\Facades\Collection::all() as $collection)
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 mr-4">@cp_svg('icons/light/content-writing')</div>
                                    <a href="{{ cp_route('seo-pro.section-defaults.collections.edit', $collection) }}">{{ $collection->title() }}</a>
                                </div>
                            </td>
                        </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if (Statamic\Facades\Taxonomy::all()->count())
        <h3 class="little-heading pl-0 mb-2">{{ __('Taxonomies') }}</h3>
        <div class="card p-0 mb-4">
            <table class="data-table">
                @foreach (Statamic\Facades\Taxonomy::all() as $taxonomy)
                    <tr>
                        <td>
                            <div class="flex items-center">
                                <div class="w-4 h-4 mr-4">@cp_svg('icons/light/tags')</div>
                                <a href="{{ cp_route('seo-pro.section-defaults.taxonomies.edit', $taxonomy) }}">{{ $taxonomy->title() }}</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @include('statamic::partials.docs-callout', [
        'topic' => 'SEO Pro',
        'url' => 'https://statamic.com/addons/statamic/seo-pro'
    ])

@stop
