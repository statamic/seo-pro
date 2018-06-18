@extends('layout')

@section('content')

    <div class="flex items-center mb-3">
        <h1 class="flex-1">Collections</h1>
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

@stop
