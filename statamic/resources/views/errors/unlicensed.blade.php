@extends('outside')

@section('content')
    <form method="POST" action="{{ route('license-key') }}">
        {!! csrf_field() !!}

        <p>
            <b>{{ t('trial_mode') }}</b><br>
            {{ t('trial_mode_explanation') }}
        </p>
        <p><a href="https://docs.statamic.com/knowledge-base/trial-mode">{{ t('learn_more_about_trial_mode') }}</a></p>

        <hr>

        <div class="form-group">
            <label>{{ t('license_key')}}</label>
            <input type="text" class="form-control" name="key" value="{{ \Statamic\API\Config::getLicenseKey() }}" autofocus>
        </div>
        <div>
            <button type="submit" class="btn btn-outside btn-block">{{ trans('cp.submit') }}</button>
        </div>
    </form>
@endsection
