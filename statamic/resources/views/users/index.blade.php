@extends('layout')

@section('content')

    <user-listing inline-template v-cloak>
        <div class="listing user-listing">
            <div id="publish-controls" class="head sticky">
                <h1 id="publish-title">{{ translate('cp.nav_users') }}</h1>
                <div class="controls">
                    @can('users:create')
                        <div class="btn-group">
                            <a href="{{ route('user.create') }}" class="btn btn-primary">{{ translate('cp.create_user_button') }}</a>
                        </div>
                    @endcan
                </div>
            </div>
            <div class="card flush">
                <div class="loading" v-if="loading">
                    <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
                </div>

                <dossier-table v-if="hasItems" :items="items" :keyword.sync="keyword" :options="tableOptions"></dossier-table>
            </div>
        </div>
    </user-listing>

@endsection
