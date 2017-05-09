@extends('layout')

@section('content')

    <script>
        Statamic.Publish = {
            contentData: {!! json_encode($content_data) !!}
        };
    </script>

    <publish title="{{ $title }}"
             extra="{{ json_encode($extra) }}"
             :is-new="false"
             slug="{{ $slug }}"
             content-type="{{ $content_type }}"
             fieldset-name="{{ $fieldset }}"
    ></publish>

@endsection
