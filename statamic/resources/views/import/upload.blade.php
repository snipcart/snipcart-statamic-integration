@extends('layout')

@section('content')

    <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data">
        {{ csrf_field() }}

        <div class="card flat-bottom">
            <div class="head">
                <h1>{{ t('import') }}</h1>
            </div>
        </div>

        <div class="card flat-top flat-bottom">
            <div class="form-group">
                <label>{{ t('json_file') }}</label>
                <small class="help-block"></small>
                <input type="file" class="form-control" name="file" />
            </div>

            <button type="submit" class="btn btn-primary">{{ t('import') }}</button>
        </div>
    </form>

@stop
