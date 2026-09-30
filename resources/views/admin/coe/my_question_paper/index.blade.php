@extends('admin.layouts.master')

@section('title', 'My Question Paper')

@push('styles')
{{-- COE question-paper layer (coe-*). Chrome is the shared programme-dt-* set;
     see docs/new-design-index-page.md and docs/design.md. --}}
{{-- Select2 JS is global; its CSS is per page (the filter selects are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $QP = \App\Http\Controllers\Admin\COE\MyQuestionPaperController::class;
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="My Question Paper" :showBack="false"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Question Paper']" />

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions">
        <button type="button" id="qpDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="qpPrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: Filter label + selects + Remove Filter · Columns + search (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="qpFilterDrive" class="form-select coe-filter"
                                data-placeholder="Drive Name" aria-label="Drive Name">
                            <option value="">Drive Name</option>
                            @foreach ($drives as $drive)
                                <option value="{{ $drive }}">{{ $drive }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpFilterBatch" class="form-select coe-filter"
                                data-placeholder="Batch" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpFilterStatus" class="form-select coe-filter"
                                data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            @foreach ($statusLabels as $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="qpResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#qpColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here;
                         the icon button reveals it. --}}
                    <div id="qpDtSearch" class="programme-dt-search d-none" data-dt-search-for="qpTable"></div>
                    <button type="button" id="qpSearchToggle" class="coe-search-toggle"
                            aria-controls="qpDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="qpTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Question paper Code</th>
                                <th scope="col">Question Paper Name</th>
                                <th scope="col">Drive</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Faculty</th>
                                <th scope="col">Original Exam Paper</th>
                                <th scope="col">Translated Exam Paper</th>
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state. --}}
                        <tbody>
                            @foreach ($papers as $paper)
                                @php
                                    $status = $paper['status'];
                                    $isOverdue = $status === $QP::STATUS_PENDING && $paper['deadline']->isPast();
                                @endphp
                                <tr data-coe-id="{{ $paper['id'] }}"
                                    data-coe-code="{{ $paper['code'] }}"
                                    data-coe-name="{{ $paper['name'] }}">
                                    <td></td>
                                    <td>{{ $paper['code'] }}</td>
                                    <td>{{ $paper['name'] }}</td>
                                    <td class="coe-col-wrap">{{ $paper['drive'] }}</td>
                                    <td>{{ $paper['batch'] }}</td>
                                    <td data-order="{{ $paper['deadline']->timestamp }}">
                                        <span class="coe-deadline {{ $isOverdue ? 'is-overdue' : '' }}"
                                              @if ($isOverdue) title="Deadline has passed" @endif>
                                            {{ $paper['deadline']->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td>{{ $paper['faculty'] }}</td>
                                    <td>
                                        <a href="#" class="coe-attach" title="{{ $paper['original_file'] }}">See Attachment</a>
                                    </td>
                                    <td class="coe-cell-translated">
                                        @if ($paper['translated_file'])
                                            <a href="#" class="coe-attach" title="{{ $paper['translated_file'] }}">See Attachment</a>
                                        @else
                                            <span class="coe-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="coe-col-center coe-cell-status">
                                        <span class="coe-state coe-state--{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                                    </td>
                                    <td class="coe-cell-actions">
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <a href="#" class="coe-act coe-act--download" title="{{ $paper['original_file'] }}">
                                                <span class="coe-act__icon"><i class="bi bi-download" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Download Original Paper</span>
                                            </a>

                                            @if ($status === $QP::STATUS_PENDING)
                                                <button type="button" class="coe-act coe-act--upload">
                                                    <span class="coe-act__icon"><i class="bi bi-upload" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Upload Translated Paper</span>
                                                </button>
                                            @elseif ($status === $QP::STATUS_TRANSLATED)
                                                <button type="button" class="coe-act coe-act--del">
                                                    <span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Delete Translated Paper</span>
                                                </button>
                                                <button type="button" class="coe-act coe-act--freeze">
                                                    <span class="coe-act__icon"><i class="bi bi-snow" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Freeze</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills in the pager and
                     "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="qpTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Upload Translated Question Paper (shared) --}}
@include('admin.coe.partials.upload_modal', ['title' => 'Upload Translated Question Paper', 'field' => 'translated_paper'])

{{-- Confirm Delete (shared) --}}
@include('admin.coe.partials.confirm_delete_modal')

{{-- Confirm Freeze (OTP, shared) --}}
@include('admin.coe.partials.freeze_modal', ['phoneMasked' => $otpPhoneMasked])

{{-- Column Visibility --}}
<div class="modal fade" id="qpColumnVisibilityModal" tabindex="-1" aria-labelledby="qpColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="qpColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="qpColumnToggleGrid"></div>
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
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { sno: 0, drive: 3, batch: 4, status: 9, action: 10 };
    var STATUS = @json($statusLabels);

    var $table = $('#qpTable');

    /* DataTable, S. No., exports, filters, search toggle and column picker —
       shared with the other COE grids (public/js/coe-grid.js). */
    var dt = CoeGrid.init({
        table: '#qpTable',
        title: 'My Question Paper',
        order: [[5, 'asc']],                                // nearest deadline first
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: { '#qpFilterDrive': COL.drive, '#qpFilterBatch': COL.batch, '#qpFilterStatus': COL.status },
        reset: '#qpResetFilters',
        download: '#qpDownloadBtn',
        print: '#qpPrintBtn',
        searchToggle: '#qpSearchToggle',
        searchSlot: '#qpDtSearch',
        colvisGrid: '#qpColumnToggleGrid',
        colvisKey: 'coeMyQuestionPaper:hiddenColumns:v1',
        empty: { title: 'No Question Papers', zeroTitle: 'No Question Papers Found', text: 'No question paper has been assigned to you yet.' }
    });

    /* ── Row state ───────────────────────────────────────────────────────────
       DESIGN PREVIEW: no backend yet, so upload / delete / freeze repaint the
       row in the browser only. When the endpoints exist, POST first and repaint
       from the response (or reload) — never from the client alone. */
    function rowActions(state) {
        var html = '<div class="coe-act-group" role="group" aria-label="Row actions">' +
            '<a href="#" class="coe-act coe-act--download">' +
            '<span class="coe-act__icon"><i class="bi bi-download" aria-hidden="true"></i></span>' +
            '<span class="coe-act__label">Download Original Paper</span></a>';

        if (state === 'pending') {
            html += '<button type="button" class="coe-act coe-act--upload">' +
                '<span class="coe-act__icon"><i class="bi bi-upload" aria-hidden="true"></i></span>' +
                '<span class="coe-act__label">Upload Translated Paper</span></button>';
        } else if (state === 'translated') {
            html += '<button type="button" class="coe-act coe-act--del">' +
                '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>' +
                '<span class="coe-act__label">Delete Translated Paper</span></button>' +
                '<button type="button" class="coe-act coe-act--freeze">' +
                '<span class="coe-act__icon"><i class="bi bi-snow" aria-hidden="true"></i></span>' +
                '<span class="coe-act__label">Freeze</span></button>';
        }
        return html + '</div>';
    }

    function setRowState($row, state, fileName) {
        var $translated = $row.find('.coe-cell-translated').empty();
        if (fileName) {
            $('<a href="#" class="coe-attach">See Attachment</a>').attr('title', fileName).appendTo($translated);
        } else {
            $('<span class="coe-muted">-</span>').appendTo($translated);
        }

        $row.find('.coe-cell-status').html(
            $('<span class="coe-state"></span>').addClass('coe-state--' + state).text(STATUS[state])
        );
        $row.find('.coe-cell-actions').html(rowActions(state));

        // Let the status filter / search see the new label.
        dt.row($row).invalidate('dom').draw(false);
    }

    function rowLabel($row) {
        return $row.data('coe-code') + ' — ' + $row.data('coe-name');
    }

    $table.on('click', 'a.coe-act--download, a.coe-attach', function (e) {
        e.preventDefault();   // no file store yet
    });

    /* ── Upload / Delete / Freeze — shared modal behaviour (coe-grid.js) ── */
    CoeGrid.upload({
        table: '#qpTable',
        trigger: '.coe-act--upload',
        meta: rowLabel,
        onSubmit: function ($row, file) {
            setRowState($row, 'translated', file.name);
            Swal.fire({ icon: 'success', title: 'Success', text: 'Translated question paper uploaded.' });
        }
    });

    CoeGrid.confirmDelete({
        table: '#qpTable',
        onConfirm: function ($row) {
            setRowState($row, 'pending', null);
            Swal.fire({ icon: 'success', title: 'Success', text: 'Translated question paper deleted.' });
        }
    });

    CoeGrid.freeze({
        table: '#qpTable',
        trigger: '.coe-act--freeze',
        onConfirm: function ($row) {
            var file = $row.find('.coe-cell-translated .coe-attach').attr('title') || null;
            setRowState($row, 'frozen', file);
            Swal.fire({ icon: 'success', title: 'Success', text: 'Question paper frozen.' });
        }
    });
});
</script>
@endpush
