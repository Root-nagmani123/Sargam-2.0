@extends('admin.layouts.master')

@section('title', 'Unique Participant Code')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.coe-btn-danger { background:#dc3545;color:#fff;border:none; }
.coe-btn-danger:hover { background:#b02a37;color:#fff; }
.coe-btn-freeze { background:#198754;color:#fff;border:none; }
.coe-btn-freeze:hover { background:#146c43;color:#fff; }
.coe-state--pending { background:#fff3cd;color:#856404; }
.upc-ot-who { font-weight: 600; }
.upc-ot-who span { color: var(--ds-ink-muted, #6c757d); font-weight: 400; }
</style>
@endpush

@section('setup_content')
@php
    $isArchived = $scope === 'archived';
    $menu = request('menu');
    $student = (int) request()->route('student');
    $here = fn (?string $s) => route('admin.coe.unique-participant-code.ot', array_filter([
        'student' => $student, 'course' => request('course'), 'scope' => $s, 'menu' => $menu,
    ]));
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Unique Participant Code" :showBack="true"
                 :items="['Setup', 'COE', 'Master Data', 'Unique Participant Code']">
        <button type="button" class="btn coe-btn-header" id="upcGenerateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Generate Unique Code</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs · bulk buttons · Download / Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ $here(null) }}" class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ $here('archived') }}" class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 align-items-center coe-secondary-actions">
            <button type="button" id="upcDeleteAllBtn" class="btn coe-btn coe-btn-danger">Delete Selected</button>
            <button type="button" id="upcFreezeAllBtn" class="btn coe-btn coe-btn-freeze">Freeze Selected</button>
            @include('admin.coe.partials.print_brand')
            <button type="button" id="upcDownloadBtn" class="btn programme-dt-btn-columns" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="upcPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <p class="upc-ot-who mb-3">{{ $otName }} <span>({{ $userName }})</span></p>

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="upcFilterCourse" class="form-select" data-placeholder="Course Name" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course }}">{{ $course }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select coe-filter-narrow">
                        <select id="upcFilterBatch" class="form-select" data-placeholder="Batch" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select coe-filter-narrow">
                        <select id="upcFilterStatus" class="form-select" data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Frozen">Frozen</option>
                        </select>
                    </div>

                    <button type="button" id="upcResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#upcColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="upcDtSearch" class="programme-dt-search d-none" data-dt-search-for="upcCodesTable"></div>
                    <button type="button" id="upcSearchToggle" class="coe-search-toggle"
                            aria-controls="upcDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="upcCodesTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-check">
                                    <input type="checkbox" class="form-check-input m-0" id="upcCheckAll"
                                           aria-label="Select all rows">
                                </th>
                                <th scope="col">S. No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col" class="coe-col-center">Batch</th>
                                <th scope="col">Subject Name</th>
                                <th scope="col">Unique Code</th>
                                <th scope="col" class="coe-col-center">STATUS</th>
                                <th scope="col">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['id'] }}">
                                    <td class="coe-col-check">
                                        <input type="checkbox" class="form-check-input m-0 upc-row-check"
                                               aria-label="Select {{ $row['subject_name'] }}">
                                    </td>
                                    <td></td>
                                    <td>{{ $row['course_name'] }}</td>
                                    <td class="coe-col-center">{{ $row['batch'] }}</td>
                                    <td>{{ $row['subject_name'] }}</td>
                                    <td class="coe-strong">{{ $row['unique_code'] }}</td>
                                    <td class="coe-col-center upc-cell-status"
                                        data-order="{{ $row['status'] === 'Frozen' ? 1 : 0 }}"
                                        data-search="{{ $row['status'] }}">
                                        <span class="coe-state coe-state--{{ $row['status'] === 'Frozen' ? 'active' : 'pending' }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="upc-cell-actions">
                                        {{-- A frozen code cannot be deleted (the server refuses it).
                                             No Edit: a hand-typed code broke the subject's sequence. --}}
                                        @if ($row['status'] === 'Frozen')
                                            <span class="text-body-secondary">—</span>
                                        @else
                                            <div class="coe-act-group" role="group" aria-label="Row actions">
                                                <button type="button" class="coe-act coe-act--freeze">
                                                    <span class="coe-act__icon"><i class="bi bi-snow2" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Freeze</span>
                                                </button>
                                                <button type="button" class="coe-act coe-act--del">
                                                    <span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Delete</span>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="upcCodesTable"></div>
            </div>

        </div>
    </div>
</div>

@include('admin.coe.unique_participant_code._generate')

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'upcDeleteModal', 'confirmId' => 'upcDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="upcColumnVisibilityModal" tabindex="-1" aria-labelledby="upcColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="upcColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="upcColumnToggleGrid"></div>
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

    var ROUTES = {
        freeze: @json(route('admin.coe.unique-participant-code.freeze')),
        destroy: @json(route('admin.coe.unique-participant-code.bulk-destroy'))
    };

    /* Column indexes — keep in step with the <thead>. */
    var COL = { check: 0, sno: 1, course: 2, batch: 3, subject: 4, code: 5, status: 6, action: 7 };
    var $table = $('#upcCodesTable');

    var dt = CoeGrid.init({
        table: '#upcCodesTable',
        title: @json('Unique Participant Code - ' . $otName),
        snoCol: COL.sno,
        order: [[COL.sno, 'asc']],
        noSort: [COL.check, COL.sno, COL.action],
        exclude: [COL.check, COL.action],
        rowCheck: '.upc-row-check',
        filters: { '#upcFilterCourse': COL.course, '#upcFilterBatch': COL.batch, '#upcFilterStatus': COL.status },
        reset: '#upcResetFilters',
        download: '#upcDownloadBtn',
        print: '#upcPrintBtn',
        searchToggle: '#upcSearchToggle',
        searchSlot: '#upcDtSearch',
        colvisGrid: '#upcColumnToggleGrid',
        colvisKey: 'coeUniqueParticipantCode:codeColumns:v2',
        empty: { title: 'No Participant Codes', zeroTitle: 'No Records Found',
                 text: 'This OT has no unique codes in this tab.' }
    });

    /* Opened from a course count: start on that course. */
    var preset = @json($presetCourse);
    if (preset) { $('#upcFilterCourse').val(preset).trigger('change'); }

    function send(url, data) {
        return $.ajax({
            url: url, type: 'POST', data: data, dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', 'Accept': 'application/json' }
        });
    }

    function showError(xhr) {
        var j = xhr.responseJSON || {};
        var text = xhr.status === 419 ? 'Your session has expired. Reload the page and try again.'
            : (j.message || 'The request failed. Please try again.');
        Swal.fire({ icon: 'error', title: 'Could not complete', text: text });
    }

    function idOf($row) { return parseInt($row.attr('data-coe-id'), 10); }

    /* ── Row selection ──────────────────────────────────────────────── */
    function selectedRows() {
        return dt.rows({ search: 'applied' }).nodes().to$().filter(function () {
            return $(this).find('.upc-row-check').prop('checked');
        });
    }

    function syncCheckAll() {
        var n = selectedRows().length;
        var all = dt.rows({ search: 'applied' }).nodes().to$().length;
        $('#upcCheckAll').prop('checked', n > 0 && n === all).prop('indeterminate', n > 0 && n < all);
    }

    function clearSelection() {
        $table.find('.upc-row-check').prop('checked', false);
        syncCheckAll();
    }

    $('#upcCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.upc-row-check').prop('checked', this.checked);
        syncCheckAll();
    });
    $table.on('change', '.upc-row-check', syncCheckAll);
    dt.on('search.dt', function () { setTimeout(syncCheckAll, 0); });

    /* ── Freeze ──────────────────────────────────────────────────────── */
    function paintFrozen($row) {
        $row.find('.upc-cell-status').attr('data-order', 1).attr('data-search', 'Frozen')
            .html('<span class="coe-state coe-state--active">Frozen</span>');
        $row.find('.upc-cell-actions').html('<span class="text-body-secondary">—</span>');
        dt.row($row).invalidate('dom');
    }

    function freeze($rows) {
        var ids = $rows.map(function () { return idOf($(this)); }).get();
        return send(ROUTES.freeze, { ids: ids }).done(function (res) {
            $rows.filter(function () { return res.frozen_ids.indexOf(idOf($(this))) !== -1; })
                 .each(function () { paintFrozen($(this)); });
            dt.draw(false);
            clearSelection();
            Swal.fire({ icon: 'success', title: 'Frozen', text: res.message });
        }).fail(showError);
    }

    $table.on('click', '.coe-act--freeze', function () { freeze($(this).closest('tr')); });

    // The bulk buttons are always shown; with nothing ticked they say so instead of acting.
    // Bound before CoeGrid.confirmDelete so this runs first and can stop its handler.
    function requireSelection(e) {
        if (selectedRows().length) { return true; }
        e.stopImmediatePropagation();
        Swal.fire({ icon: 'info', title: 'Nothing selected', text: 'Select at least one code first.' });
        return false;
    }
    $('#upcDeleteAllBtn').on('click', requireSelection);
    $('#upcFreezeAllBtn').on('click', function (e) { if (requireSelection(e)) { freeze(selectedRows()); } });

    /* ── Delete ──────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#upcCodesTable',
        modal: '#upcDeleteModal',
        confirm: '#upcDeleteConfirm',
        bulkTrigger: '#upcDeleteAllBtn',
        onConfirm: function ($row) {
            var $targets = $row ? $row : selectedRows();
            var ids = $targets.map(function () { return idOf($(this)); }).get();

            return send(ROUTES.destroy, { ids: ids }).done(function (res) {
                dt.rows($targets.filter(function () {
                    return res.deleted_ids.indexOf(idOf($(this))) !== -1;
                })).remove().draw(false);
                clearSelection();
                Swal.fire({ icon: 'success', title: 'Deleted', text: res.message });
            }).fail(showError);
        }
    });
});
</script>
@endpush
