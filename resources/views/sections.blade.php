@extends('layout')

@section('content')

    <div class="flex items-center mb-3">
        <h1 class="flex-1">{{ $title }}</h1>
    </div>

    <div class="flex items-center mb-1">
        <h2 class="flex-1">{{ trans_choice('cp.pages', 2) }}</h2>
    </div>

    <div class="card flush dossier">
        <div class="dossier-table-wrapper">
            <table class="dossier">
                <tbody>
                    <tr>
                        <td class="cell-title first-cell">
                            <a class="" href="{{ route('seopro.pages.edit') }}">{{ trans_choice('cp.pages', 2) }}</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center mb-1">
        <h2 class="flex-1">{{ trans_choice('cp.collections', 2) }}</h2>
    </div>

    @if ($collections->isEmpty())
        <p class="card text-xs text-grey">{{ translate('addons.SeoPro::messages.no_collections') }}</p>
    @else
        <div class="card flush dossier">
            <div class="dossier-table-wrapper">
                <table class="dossier">
                    <tbody>
                        @foreach ($collections as $collection)
                        <tr>
                            <td class="cell-title first-cell">
                                <span class="column-label">Title</span>
                                <a class="" href="{{ route('seopro.collections.edit', ['collection' => $collection->path()]) }}">
                                    {{ $collection->title() }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="flex items-center mb-1">
        <h2 class="flex-1">{{ trans_choice('cp.taxonomies', 2) }}</h2>
    </div>

    @if ($taxonomies->isEmpty())
        <p class="card text-xs text-grey">{{ translate('addons.SeoPro::messages.no_taxonomies') }}</p>
    @else
        <div class="card flush dossier">
            <div class="dossier-table-wrapper">
                <table class="dossier">
                    <tbody>
                        @foreach ($taxonomies as $taxonomy)
                        <tr>
                            <td class="cell-title first-cell">
                                <span class="column-label">Taxonomies</span>
                                <a class="" href="{{ route('seopro.taxonomies.edit', ['taxonomy' => $taxonomy->path()]) }}">
                                    {{ $taxonomy->title() }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@stop
