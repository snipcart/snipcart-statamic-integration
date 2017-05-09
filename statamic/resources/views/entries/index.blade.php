@extends('layout')
@section('content-class', 'publishing')

@section('content')

    <entry-listing inline-template v-cloak
        get="{{ route('entries.get', $collection->path()) }}"
        delete="{{ route('entries.delete') }}"
        reorder="{{ route('entries.reorder') }}"
        sort="{{ $sort }}"
        sort-order="{{ $sort_order }}"
        :reorderable="{{ $reorderable }}"
        :can-delete="{{ bool_str(\Statamic\API\User::getCurrent()->can('collections:'.$collection->path().':delete')) }}">

        <div class="listing entry-listing">

            <div id="publish-controls" class="head sticky">
                <h1 id="publish-title">{{ $collection->title() }}</h1>
                <div class="controls">
                    @can("collections:{$collection->path()}:create")
                        <template v-if="! reordering">
                            <div class="btn-group">
                                <button type="button" @click="enableReorder" class="btn btn-secondary" v-if="reorderable">
                                    {{ translate('cp.reorder') }}
                                </button>
                            </div>
                            <div class="btn-group">
                                <a href="{{ route('entry.create', $collection->path()) }}" class="btn btn-primary">{{ translate('cp.create_entry_button') }}</a>
                            </div>
                        </template>
                        <template v-else>
                            <div class="btn-group">
                                <button type="button" @click="cancelOrder" class="btn btn-secondary">
                                    {{ translate('cp.cancel') }}
                                </button>
                            </div>
                            <div class="btn-group">
                                <button type="button" @click="saveOrder" class="btn btn-primary">
                                    {{ translate('cp.save_order') }}
                                </button>
                            </div>
                        </template>
                    @endcan
                </div>
            </div>
            <div class="card flush">
                <template v-if="noItems">
                    <div class="info-block">
                        <span class="icon icon-documents"></span>
                        <h2>{{ trans('cp.entries_empty_heading', ['type' => $collection->title()]) }}</h2>
                        <h3>{{ trans('cp.entries_empty') }}</h3>
                        @can("collections:{$collection->path()}:create")
                            <a href="{{ route('entry.create', $collection->path()) }}" class="btn btn-default btn-lg">{{ trans('cp.create_entry_button') }}</a>
                        @endcan
                    </div>
                </template>

                <div class="loading" v-if="loading">
                    <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
                </div>

                <dossier-table v-if="hasItems" :items="items" :keyword.sync="keyword" :options="tableOptions"></dossier-table>
            </div>
        </div>

    </entry-listing>
@endsection
