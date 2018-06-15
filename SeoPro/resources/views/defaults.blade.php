@extends('layout')

@section('content')

    <script>
        Statamic.Publish = {
            contentData: {!! json_encode($data) !!},
            fieldset: {!! json_encode($fieldset) !!}
        };
    </script>

    <publish
        title="{{ $title }}"
        submit-url="{{ route('seopro.defaults.update') }}"
        :update-title-on-save="false"
        content-type="addon"
    ></publish>

@stop
