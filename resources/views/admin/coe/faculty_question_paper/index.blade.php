@extends('admin.layouts.master')

@section('title', 'My Question Paper')

@push('styles')
{{-- Select2 JS is global; its CSS is per page (the filter selects are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
{{-- COE question-paper layer (coe-*), shared with the other COE grids. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="My Question Paper" :showBack="false"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Question Paper']" />

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions">
        <button type="button" id="qpfDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="qpfPrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="qpfFilterDrive" class="form-select" data-placeholder="Drive Name" aria-label="Drive Name">
                            <option value="">Drive Name</option>
                            @foreach ($drives as $drive)
                                <option value="{{ $drive }}">{{ $drive }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpfFilterBatch" class="form-select" data-placeholder="Batch" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpfFilterStatus" class="form-select" data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            @foreach ($statusLabels as $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="qpfResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#qpfColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="qpfDtSearch" class="programme-dt-search d-none" data-dt-search-for="qpfTable"></div>
                    <button type="button" id="qpfSearchToggle" class="coe-search-toggle"
                            aria-controls="qpfDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="qpfTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Question paper Code</th>
                                <th scope="col">Question Paper Name</th>
                                <th scope="col">Drive</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Original Exam Paper</th>
                                <th scope="col">Translated Exam Paper</th>
                                <th scope="col">Translated By (Rajbhasha)</th>
                                <th scope="col">Comments</th>
                                <th scope="col">Version</th>
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state.
                             The Action cell is painted by the script from data-coe-state, so
                             the buttons for each state are defined in exactly one place. --}}
                        <tbody>
                            @foreach ($papers as $paper)
                                @php
                                    $isOverdue = in_array($paper['status'], ['pending-faculty', 'draft'], true)
                                        && $paper['deadline']->isPast();
                                @endphp
                                <tr data-coe-id="{{ $paper['id'] }}"
                                    data-coe-code="{{ $paper['code'] }}"
                                    data-coe-name="{{ $paper['name'] }}"
                                    data-coe-state="{{ $paper['status'] }}"
                                    data-coe-version="{{ $paper['version'] }}"
                                    data-coe-translated="{{ $paper['translated_file'] ? '1' : '0' }}">
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
                                    <td class="coe-cell-original">
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
                                    <td class="coe-col-comment">{!! $paper['comments'] ? e($paper['comments']) : '<span class="coe-muted">-</span>' !!}</td>
                                    <td class="coe-cell-version">V{{ $paper['version'] }}</td>
                                    <td class="coe-col-center coe-cell-status">
                                        <span class="coe-state coe-state--{{ $paper['status'] }}">{{ $statusLabels[$paper['status']] ?? ucfirst($paper['status']) }}</span>
                                    </td>
                                    <td class="coe-cell-actions"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills it (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="qpfTable"></div>
            </div>

        </div>
    </div>
</div>

@include('admin.coe.partials.upload_modal', ['title' => 'Upload Question Paper', 'field' => 'question_paper'])
@include('admin.coe.partials.confirm_delete_modal')
@include('admin.coe.partials.freeze_modal', ['phoneMasked' => $otpPhoneMasked])

{{-- Column Visibility --}}
<div class="modal fade" id="qpfColumnVisibilityModal" tabindex="-1" aria-labelledby="qpfColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="qpfColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="qpfColumnToggleGrid"></div>
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
    var COL = { sno: 0, drive: 3, batch: 4, deadline: 5, status: 11, action: 12 };
    var STATUS = @json($statusLabels);
    var $table = $('#qpfTable');

    /* ── Row actions per state — the ONE place they are defined ─────────── */
    function act(cls, icon, label) {
        var tag = cls.indexOf('coe-act--download') !== -1 ? 'a href="#"' : 'button type="button"';
        return '<' + tag + ' class="coe-act ' + cls + '">' +
            '<span class="coe-act__icon"><i class="bi ' + icon + '" aria-hidden="true"></i></span>' +
            '<span class="coe-act__label">' + label + '</span></' + tag.split(' ')[0] + '>';
    }

    var ACTIONS = {
        'pending-faculty': [
            ['coe-act--upload coe-act--wide', 'bi-upload', 'Upload or Create Original Paper']
        ],
        'draft': [
            ['coe-act--download', 'bi-download', 'Download Original Paper'],
            ['coe-act--upload', 'bi-arrow-repeat', 'Re-upload Paper'],
            ['coe-act--del', 'bi-trash3', 'Delete'],
            ['coe-act--freeze', 'bi-snow', 'Freeze']
        ],
        'pending-rajbhasha': [
            ['coe-act--download', 'bi-download', 'Download Original Paper']
        ],
        'published': [
            ['coe-act--download', 'bi-download', 'Download Original Paper'],
            ['coe-act--download', 'bi-translate', 'Download Translated Paper']
        ]
    };

    function paintActions($row) {
        var list = ACTIONS[$row.attr('data-coe-state')] || [];
        $row.find('.coe-cell-actions').html(
            '<div class="coe-act-group" role="group" aria-label="Row actions">' +
            list.map(function (a) { return act(a[0], a[1], a[2]); }).join('') + '</div>'
        );
    }

    // Paint before DataTables reads the DOM.
    $table.find('tbody tr').each(function () { paintActions($(this)); });

    var dt = CoeGrid.init({
        table: '#qpfTable',
        title: 'My Question Paper',
        order: [[COL.deadline, 'asc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: { '#qpfFilterDrive': COL.drive, '#qpfFilterBatch': COL.batch, '#qpfFilterStatus': COL.status },
        reset: '#qpfResetFilters',
        download: '#qpfDownloadBtn',
        print: '#qpfPrintBtn',
        searchToggle: '#qpfSearchToggle',
        searchSlot: '#qpfDtSearch',
        colvisGrid: '#qpfColumnToggleGrid',
        colvisKey: 'coeFacultyQuestionPaper:hiddenColumns:v1',
        empty: { title: 'No Question Papers', zeroTitle: 'No Question Papers Found', text: 'No question paper has been assigned to you yet.' }
    });

    /* ── Row state ───────────────────────────────────────────────────────────
       DESIGN PREVIEW: no backend yet, so upload / delete / freeze repaint the
       row in the browser only. When the endpoints exist, POST first and repaint
       from the response (or reload) — never from the client alone. */
    function setRow($row, state, originalFile, version) {
        $row.attr('data-coe-state', state).attr('data-coe-version', version);

        var $orig = $row.find('.coe-cell-original').empty();
        if (originalFile) {
            $('<a href="#" class="coe-attach">See Attachment</a>').attr('title', originalFile).appendTo($orig);
        } else {
            $('<span class="coe-muted">-</span>').appendTo($orig);
        }

        $row.find('.coe-cell-version').text('V' + version);
        $row.find('.coe-cell-status').html(
            $('<span class="coe-state"></span>').addClass('coe-state--' + state).text(STATUS[state])
        );
        paintActions($row);

        // Let the Status filter / search see the new label.
        dt.row($row).invalidate('dom').draw(false);
    }

    function version($row) { return parseInt($row.attr('data-coe-version'), 10) || 1; }

    $table.on('click', 'a.coe-act--download, a.coe-attach', function (e) { e.preventDefault(); });

    CoeGrid.upload({
        table: '#qpfTable',
        trigger: '.coe-act--upload',
        meta: function ($row) { return $row.data('coe-code') + ' — ' + $row.data('coe-name'); },
        onSubmit: function ($row, file) {
            // A re-upload of a draft is a new version; the first upload is V1.
            var isRevision = $row.attr('data-coe-state') === 'draft';
            setRow($row, 'draft', file.name, isRevision ? version($row) + 1 : version($row));
            Swal.fire({ icon: 'success', title: 'Success',
                text: isRevision ? 'New version uploaded.' : 'Question paper uploaded.' });
        }
    });

    CoeGrid.confirmDelete({
        table: '#qpfTable',
        onConfirm: function ($row) {
            setRow($row, 'pending-faculty', null, version($row));
            Swal.fire({ icon: 'success', title: 'Success', text: 'Question paper deleted.' });
        }
    });

    CoeGrid.freeze({
        table: '#qpfTable',
        trigger: '.coe-act--freeze',
        onConfirm: function ($row) {
            var file = $row.find('.coe-cell-original .coe-attach').attr('title') || null;
            setRow($row, 'pending-rajbhasha', file, version($row));
            Swal.fire({ icon: 'success', title: 'Success', text: 'Question paper frozen and sent for translation.' });
        }
    });
});
</script>
@endpush
