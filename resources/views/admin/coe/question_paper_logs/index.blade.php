@extends('admin.layouts.master')

@section('title', 'Question Paper Management Logs')

@push('styles')
{{-- Select2 JS is global; its CSS is per page (the filter selects are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
{{-- COE question-paper layer (coe-*), shared with the Question Paper screens. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $isTranslatedScope = $scope === 'translated';
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Question Paper Management Logs" :showBack="false"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Question Paper Management Logs']" />

    <x-session_message />

    {{-- Scope tabs (links, one URL each) · Download / Print — above the card, per §1 --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Log scope">
            <a href="{{ route('admin.coe.question-paper-logs.index') }}"
               class="coe-tab {{ $isTranslatedScope ? '' : 'is-active' }}"
               @unless ($isTranslatedScope) aria-current="page" @endunless>All</a>
            <a href="{{ route('admin.coe.question-paper-logs.index', ['scope' => 'translated']) }}"
               class="coe-tab {{ $isTranslatedScope ? 'is-active' : '' }}"
               @if ($isTranslatedScope) aria-current="page" @endif>Translated</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="qplgDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="qplgPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar coe-toolbar--compact">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="programme-dt-filters-label">Filter</span>

                    @foreach ([
                        ['qplgFilterDrive', 'Drive Name', $drives],
                        ['qplgFilterBatch', 'Batch', $batches],
                        ['qplgFilterFaculty', 'Faculty', $faculties],
                        ['qplgFilterStatus', 'Status', array_keys($actions)],
                    ] as [$id, $label, $values])
                        <div class="programme-dt-filter-select">
                            <select id="{{ $id }}" class="form-select" data-placeholder="{{ $label }}" aria-label="{{ $label }}">
                                <option value="">{{ $label }}</option>
                                @foreach ($values as $value)
                                    <option value="{{ $value }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach

                    <button type="button" id="qplgResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#qplgColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="qplgDtSearch" class="programme-dt-search d-none" data-dt-search-for="qplgTable"></div>
                    <button type="button" id="qplgSearchToggle" class="coe-search-toggle"
                            aria-controls="qplgDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="qplgTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Date &amp; Time</th>
                                <th scope="col">User Name</th>
                                <th scope="col">Role</th>
                                <th scope="col">Module</th>
                                <th scope="col">Question Paper Code</th>
                                <th scope="col">Action</th>
                                <th scope="col">IP Address</th>
                                <th scope="col">Location</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state.
                             Drive / Batch / Faculty filter on the row's data-*: not columns. --}}
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr data-drive="{{ $entry['drive'] }}"
                                    data-batch="{{ $entry['batch'] }}"
                                    data-faculty="{{ $entry['faculty'] }}">
                                    <td></td>
                                    <td data-order="{{ $entry['at']->timestamp }}" class="coe-col-wrap">
                                        {{ $entry['at']->format('d/m/Y, h:i A') }}
                                    </td>
                                    <td>{{ $entry['user_name'] }}</td>
                                    <td>
                                        <span class="coe-log-badge coe-log-badge--{{ $roles[$entry['role']] ?? 'grey' }}">{{ $entry['role'] }}</span>
                                    </td>
                                    <td>{{ $entry['module'] }}</td>
                                    <td title="{{ $entry['paper_name'] }}">
                                        {{ $entry['paper_code'] }}
                                        @if ($entry['detail'])
                                            <span class="coe-muted">({{ $entry['detail'] }})</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="coe-log-badge coe-log-badge--{{ $actions[$entry['action']] ?? 'grey' }}">{{ $entry['action'] }}</span>
                                    </td>
                                    <td>{{ $entry['ip_address'] }}</td>
                                    <td>{{ $entry['location'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills it (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="qplgTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="qplgColumnVisibilityModal" tabindex="-1" aria-labelledby="qplgColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="qplgColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="qplgColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
{{-- Built first: the json directive splits its argument on commas. --}}
@php
    $gridTitle = $isTranslatedScope ? 'Question Paper Management Logs - Translated' : 'Question Paper Management Logs';
    $emptyState = ['title' => 'No Log Entries', 'zeroTitle' => 'No Log Entries Found', 'text' => 'Nothing has been done to a question paper yet.'];
@endphp
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { sno: 0, at: 1, action: 6 };

    CoeGrid.init({
        table: '#qplgTable',
        title: @json($gridTitle),
        order: [[COL.at, 'desc']],                          // newest first
        noSort: [COL.sno],
        filters: { '#qplgFilterStatus': COL.action },
        rowFilters: { '#qplgFilterDrive': 'drive', '#qplgFilterBatch': 'batch', '#qplgFilterFaculty': 'faculty' },
        reset: '#qplgResetFilters',
        download: '#qplgDownloadBtn',
        print: '#qplgPrintBtn',
        searchToggle: '#qplgSearchToggle',
        searchSlot: '#qplgDtSearch',
        colvisGrid: '#qplgColumnToggleGrid',
        colvisKey: 'coeQuestionPaperLogs:hiddenColumns:v1',
        empty: @json($emptyState)
    });
});
</script>
@endpush
