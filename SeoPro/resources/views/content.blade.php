@extends('layout')

@section('content')

    <div class="flex items-center mb-3">
        <h1 class="flex-1">Content Defaults</h1>
    </div>

    <div class="flex items-center mb-1">
        <h2 class="flex-1">Collections</h2>
    </div>
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

    <div class="flex items-center mb-1">
        <h2 class="flex-1">Taxonomies</h2>
    </div>
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

@stop
