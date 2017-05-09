@extends('layout')

@section('content')

    <div class="tabs">
        @foreach ($settings as $setting)
            <a href="{{ route('settings.edit', $setting) }}" class="{{ $setting !== $slug ?: 'active' }}">
                {{ translate('cp.settings_'.$setting) }}
            </a>
        @endforeach
    </div>

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
