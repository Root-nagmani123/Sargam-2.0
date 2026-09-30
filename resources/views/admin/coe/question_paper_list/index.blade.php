@extends('admin.layouts.master')

@section('title', 'Question Paper')

@push('styles')
{{-- Select2 JS is global; its CSS is per page (the filter selects are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
{{-- COE question-paper layer (coe-*), shared with My Question Paper. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $isTranslatedScope = $scope === 'translated';
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Question Paper" :showBack="false"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Question Paper']" />

    <x-session_message />

    {{-- Scope tabs (links, one URL each) · Download / Print — above the card, per §1 --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Question paper scope">
            <a href="{{ route('admin.coe.question-paper.index') }}"
               class="coe-tab {{ $isTranslatedScope ? '' : 'is-active' }}"
               @unless ($isTranslatedScope) aria-current="page" @endunless>All</a>
            <a href="{{ route('admin.coe.question-paper.index', ['scope' => 'translated']) }}"
               class="coe-tab {{ $isTranslatedScope ? 'is-active' : '' }}"
               @if ($isTranslatedScope) aria-current="page" @endif>Translated</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="qplDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="qplPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar (§2). Translated scope drops Faculty + Status: every row there
                 is Published, and Faculty is not a filter in that design. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar {{ $isTranslatedScope ? '' : 'coe-toolbar--compact' }}">
                <div class="d-flex flex-wrap align-items-center {{ $isTranslatedScope ? 'gap-3' : 'gap-2' }}">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="qplFilterDrive" class="form-select" data-placeholder="Drive Name" aria-label="Drive Name">
                            <option value="">Drive Name</option>
                            @foreach ($drives as $drive)
                                <option value="{{ $drive }}">{{ $drive }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qplFilterBatch" class="form-select" data-placeholder="Batch" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    @unless ($isTranslatedScope)
                        <div class="programme-dt-filter-select">
                            <select id="qplFilterFaculty" class="form-select" data-placeholder="Faculty" aria-label="Faculty">
                                <option value="">Faculty</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty }}">{{ $faculty }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <select id="qplFilterStatus" class="form-select" data-placeholder="Status" aria-label="Status">
                                <option value="">Status</option>
                                @foreach ($statusLabels as $label)
                                    <option value="{{ $label }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless

                    <button type="button" id="qplResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#qplColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="qplDtSearch" class="programme-dt-search d-none" data-dt-search-for="qplTable"></div>
                    <button type="button" id="qplSearchToggle" class="coe-search-toggle"
                            aria-controls="qplDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="qplTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Question paper Code</th>
                                <th scope="col">Question Paper Name</th>
                                <th scope="col">Drive</th>
                                <th scope="col">Faculty</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Original Exam Paper</th>
                                <th scope="col">Translated Exam Paper</th>
                                <th scope="col">Translated By (Rajbhasha)</th>
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state. --}}
                        <tbody>
                            @foreach ($papers as $paper)
                                <tr data-coe-id="{{ $paper['id'] }}">
                                    <td></td>
                                    <td>{{ $paper['code'] }}</td>
                                    <td>{{ $paper['name'] }}</td>
                                    <td class="coe-col-wrap">{{ $paper['drive'] }}</td>
                                    <td class="coe-col-wrap">{{ $paper['faculty'] }}</td>
                                    <td>{{ $paper['batch'] }}</td>
                                    <td data-order="{{ $paper['deadline']->timestamp }}">
                                        <span class="coe-deadline">{{ $paper['deadline']->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        @if ($paper['original_file'])
                                            <a href="#" class="coe-attach" title="{{ $paper['original_file'] }}">See Attachment</a>
                                        @else
                                            <span class="coe-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($paper['translated_file'])
                                            <a href="#" class="coe-attach" title="{{ $paper['translated_file'] }}">See Attachment</a>
                                        @else
                                            <span class="coe-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{!! $paper['translated_by'] ? e($paper['translated_by']) : '<span class="coe-muted">-</span>' !!}</td>
                                    <td class="coe-col-center">
                                        <span class="coe-state coe-state--{{ $paper['status'] }}">{{ $statusLabels[$paper['status']] ?? ucfirst($paper['status']) }}</span>
                                    </td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="coe-act coe-act--del">
                                                <span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Delete</span>
                                            </button>

                                            {{-- Greyed, not hidden, when there is no file yet — keeps
                                                 the action column the same shape on every row. --}}
                                            @foreach ([
                                                [$paper['original_file'], 'bi-download', 'Download Original Paper', 'Faculty has not uploaded the paper yet'],
                                                [$paper['translated_file'], 'bi-translate', 'Download Translated Paper', 'No translation uploaded yet'],
                                            ] as [$file, $icon, $label, $why])
                                                @if ($file)
                                                    <a href="#" class="coe-act coe-act--download" title="{{ $file }}">
                                                        <span class="coe-act__icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                                                        <span class="coe-act__label">{{ $label }}</span>
                                                    </a>
                                                @else
                                                    <span class="coe-act is-disabled" aria-disabled="true" title="{{ $why }}">
                                                        <span class="coe-act__icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                                                        <span class="coe-act__label">{{ $label }}</span>
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills it (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="qplTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Confirm Delete (shared) --}}
@include('admin.coe.partials.confirm_delete_modal', ['id' => 'qplDeleteModal', 'confirmId' => 'qplDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="qplColumnVisibilityModal" tabindex="-1" aria-labelledby="qplColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="qplColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="qplColumnToggleGrid"></div>
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
    $emptyState = $isTranslatedScope
        ? ['title' => 'No Translated Papers', 'zeroTitle' => 'No Translated Papers Found', 'text' => 'No question paper has been translated yet.']
        : ['title' => 'No Question Papers', 'zeroTitle' => 'No Question Papers Found', 'text' => 'No question paper has been created yet.'];
@endphp
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { sno: 0, drive: 3, faculty: 4, batch: 5, deadline: 6, status: 10, action: 11 };

    var dt = CoeGrid.init({
        table: '#qplTable',
        title: @json($isTranslatedScope ? 'Question Paper - Translated' : 'Question Paper'),
        order: [[COL.deadline, 'asc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: {
            '#qplFilterDrive': COL.drive,
            '#qplFilterBatch': COL.batch,
            '#qplFilterFaculty': COL.faculty,
            '#qplFilterStatus': COL.status
        },
        reset: '#qplResetFilters',
        download: '#qplDownloadBtn',
        print: '#qplPrintBtn',
        searchToggle: '#qplSearchToggle',
        searchSlot: '#qplDtSearch',
        colvisGrid: '#qplColumnToggleGrid',
        colvisKey: 'coeQuestionPaperList:hiddenColumns:v1',
        empty: @json($emptyState)
    });

    var $table = $('#qplTable');

    // No file store yet — keep the links from jumping to "#".
    $table.on('click', 'a.coe-act--download, a.coe-attach', function (e) { e.preventDefault(); });

    /* ── Delete ──────────────────────────────────────────────────────────────
       DESIGN PREVIEW: no backend yet, so the row is removed in the browser
       only. When the endpoint exists, DELETE first and redraw from the
       response — never from the client alone. */
    CoeGrid.confirmDelete({
        table: '#qplTable',
        modal: '#qplDeleteModal',
        confirm: '#qplDeleteConfirm',
        onConfirm: function ($row) {
            dt.row($row).remove().draw(false);
            Swal.fire({ icon: 'success', title: 'Success', text: 'Question paper deleted.' });
        }
    });
});
</script>
@endpush
