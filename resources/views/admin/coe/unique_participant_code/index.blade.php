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
</style>
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Unique Participant Code" :showBack="false"
                 :items="['Setup', 'COE', 'Master Data', 'Unique Participant Code']">
        <button type="button" class="btn coe-btn-header" id="upcGenerateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Generate Unique Code</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs · bulk buttons · Download / Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.unique-participant-code.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.unique-participant-code.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 align-items-center coe-secondary-actions">
            <button type="button" id="upcDeleteAllBtn" class="btn coe-btn coe-btn-danger d-none">Delete All</button>
            <button type="button" id="upcFreezeAllBtn" class="btn coe-btn coe-btn-freeze d-none">Freeze All</button>
            <button type="button" id="upcDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="upcPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar --}}
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

                    <div id="upcDtSearch" class="programme-dt-search d-none" data-dt-search-for="upcTable"></div>
                    <button type="button" id="upcSearchToggle" class="coe-search-toggle"
                            aria-controls="upcDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="upcTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-check">
                                    <input type="checkbox" class="form-check-input m-0" id="upcCheckAll"
                                           aria-label="Select all rows">
                                </th>
                                <th scope="col">S. No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">OT Name</th>
                                <th scope="col">DT Name</th>
                                <th scope="col">Unique Code</th>
                                <th scope="col" class="coe-col-center">STATUS</th>
                                <th scope="col">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['id'] }}"
                                    data-upc-course="{{ $row['course_name'] }}"
                                    data-upc-ot="{{ $row['ot_name'] }}"
                                    data-upc-dt="{{ $row['dt_name'] }}"
                                    data-upc-code="{{ $row['unique_code'] }}"
                                    data-upc-status="{{ $row['status'] }}">
                                    <td class="coe-col-check">
                                        <input type="checkbox" class="form-check-input m-0 upc-row-check"
                                               aria-label="Select {{ $row['ot_name'] }}">
                                    </td>
                                    <td></td>
                                    <td class="coe-strong upc-cell-course">{{ $row['course_name'] }}</td>
                                    <td class="upc-cell-ot">{{ $row['ot_name'] }}</td>
                                    <td class="upc-cell-dt">{{ $row['dt_name'] }}</td>
                                    <td class="upc-cell-code">{{ $row['unique_code'] }}</td>
                                    <td class="coe-col-center upc-cell-status"
                                        data-order="{{ $row['status'] === 'Frozen' ? 1 : 0 }}"
                                        data-search="{{ $row['status'] }}">
                                        <span class="coe-state coe-state--{{ $row['status'] === 'Frozen' ? 'active' : 'pending' }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="upc-cell-actions">
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Edit</span>
                                            </button>
                                            <button type="button" class="coe-act coe-act--freeze">
                                                <span class="coe-act__icon"><i class="bi bi-lock" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Freeze</span>
                                            </button>
                                            <button type="button" class="coe-act coe-act--del">
                                                <span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="upcTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- ── Generate Unique Code modal (Create) ────────────────────────────────── --}}
<div class="modal fade" id="upcGenerateModal" tabindex="-1" aria-labelledby="upcGenerateModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="upcGenerateForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="upcGenerateModalLabel">Generate Unique Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="upcGenCourse">Course<span class="coe-req">*</span></label>
                            <select id="upcGenCourse" name="course" class="form-select coe-control" required>
                                <option value="">Select Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course }}">{{ $course }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcGenOtCount">Number of OTs</label>
                            <input type="text" id="upcGenOtCount" class="form-control coe-control" value="234" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="upcGenPrefix">Unique Code Prefix<span class="coe-req">*</span></label>
                            <input type="text" id="upcGenPrefix" name="prefix" class="form-control coe-control"
                                   placeholder="eg. UC-01" maxlength="20" required autocomplete="off">
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="upcGenStart">Count Start from<span class="coe-req">*</span></label>
                            <input type="number" id="upcGenStart" name="count_start" class="form-control coe-control"
                                   placeholder="eg. 324" min="1" step="1" inputmode="numeric" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Edit Unique Code modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="upcEditModal" tabindex="-1" aria-labelledby="upcEditModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="upcEditForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="upcEditModalLabel">Edit Unique Participant Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditCourse">Course Name<span class="coe-req">*</span></label>
                            <select id="upcEditCourse" name="course_name" class="form-select coe-control" required>
                                <option value="">Select Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course }}">{{ $course }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditOt">OT Name<span class="coe-req">*</span></label>
                            <input type="text" id="upcEditOt" name="ot_name" class="form-control coe-control"
                                   placeholder="eg. Dana Arnold" maxlength="100" required autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditDt">DT Name<span class="coe-req">*</span></label>
                            <input type="text" id="upcEditDt" name="dt_name" class="form-control coe-control"
                                   placeholder="eg. danaarnold" maxlength="100" required autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditCode">Unique Code<span class="coe-req">*</span></label>
                            <input type="text" id="upcEditCode" name="unique_code" class="form-control coe-control"
                                   placeholder="eg. UC-234" maxlength="50" required autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditStatus">Status<span class="coe-req">*</span></label>
                            <select id="upcEditStatus" name="status" class="form-select coe-control" required>
                                <option value="Pending">Pending</option>
                                <option value="Frozen">Frozen</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    /* Column indexes — keep in step with the <thead>. */
    var COL = { check: 0, sno: 1, course: 2, ot: 3, dt: 4, code: 5, status: 6, action: 7 };

    var $table = $('#upcTable');

    var ACTION_HTML =
        '<div class="coe-act-group" role="group" aria-label="Row actions">' +
        '<button type="button" class="coe-act coe-act--edit">' +
        '<span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>' +
        '<span class="coe-act__label">Edit</span></button>' +
        '<button type="button" class="coe-act coe-act--freeze">' +
        '<span class="coe-act__icon"><i class="bi bi-lock" aria-hidden="true"></i></span>' +
        '<span class="coe-act__label">Freeze</span></button>' +
        '<button type="button" class="coe-act coe-act--del">' +
        '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>' +
        '<span class="coe-act__label">Delete</span></button></div>';

    var dt = CoeGrid.init({
        table: '#upcTable',
        title: @json($isArchived ? 'Unique Participant Code - Archived' : 'Unique Participant Code'),
        snoCol: COL.sno,
        order: [[COL.sno, 'asc']],
        noSort: [COL.check, COL.sno, COL.action],
        exclude: [COL.check, COL.action],
        filters: { '#upcFilterCourse': COL.course, '#upcFilterStatus': COL.status },
        reset: '#upcResetFilters',
        download: '#upcDownloadBtn',
        print: '#upcPrintBtn',
        searchToggle: '#upcSearchToggle',
        searchSlot: '#upcDtSearch',
        colvisGrid: '#upcColumnToggleGrid',
        colvisKey: 'coeUniqueParticipantCode:hiddenColumns:v1',
        empty: { title: 'No Participant Codes', zeroTitle: 'No Records Found',
                 text: 'Generate unique codes to get started.' }
    });

    /* ── Row selection ──────────────────────────────────────────────────── */
    function selectedRows() {
        return dt.rows({ search: 'applied' }).nodes().to$().filter(function () {
            return $(this).find('.upc-row-check').prop('checked');
        });
    }

    function syncBulkButtons() {
        var n = selectedRows().length;
        var all = dt.rows({ search: 'applied' }).nodes().to$().length;
        $('#upcDeleteAllBtn, #upcFreezeAllBtn').toggleClass('d-none', n === 0);
        $('#upcCheckAll')
            .prop('checked', n > 0 && n === all)
            .prop('indeterminate', n > 0 && n < all);
    }

    $('#upcCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.upc-row-check').prop('checked', this.checked);
        syncBulkButtons();
    });
    $table.on('change', '.upc-row-check', syncBulkButtons);
    dt.on('search.dt', function () { setTimeout(syncBulkButtons, 0); });

    /* ── Status badge helper ───────────────────────────────────────────── */
    function paintStatus($row, status) {
        var isFrozen = status === 'Frozen';
        $row.find('.upc-cell-status')
            .attr('data-order', isFrozen ? 1 : 0)
            .attr('data-search', status)
            .html('<span class="coe-state coe-state--' + (isFrozen ? 'active' : 'pending') + '">' + status + '</span>');
        $row.attr('data-upc-status', status);
        dt.row($row).invalidate('dom').draw(false);
    }

    /* ── Delete ─────────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#upcTable',
        modal: '#upcDeleteModal',
        confirm: '#upcDeleteConfirm',
        bulkTrigger: '#upcDeleteAllBtn',
        onConfirm: function ($row) {
            var $targets = $row ? $row : selectedRows();
            var n = $targets.length;
            dt.rows($targets).remove().draw(false);
            syncBulkButtons();
            Swal.fire({ icon: 'success', title: 'Deleted',
                text: n === 1 ? 'Record deleted.' : n + ' records deleted.' });
        }
    });

    /* ── Freeze single row ────────────────────────────────────────────── */
    $table.on('click', '.coe-act--freeze', function () {
        var $row = $(this).closest('tr');
        paintStatus($row, 'Frozen');
        Swal.fire({ icon: 'success', title: 'Frozen', text: 'Participant code has been frozen.' });
    });

    /* ── Freeze All ────────────────────────────────────────────────────── */
    $('#upcFreezeAllBtn').on('click', function () {
        selectedRows().each(function () { paintStatus($(this), 'Frozen'); });
        $table.find('.upc-row-check').prop('checked', false);
        $('#upcCheckAll').prop('checked', false).prop('indeterminate', false);
        syncBulkButtons();
        Swal.fire({ icon: 'success', title: 'Frozen', text: 'Selected codes have been frozen.' });
    });

    /* ── Edit modal ────────────────────────────────────────────────────── */
    var $editModal   = $('#upcEditModal');
    var $editCourse  = $('#upcEditCourse');
    var $editOt      = $('#upcEditOt');
    var $editDt      = $('#upcEditDt');
    var $editCode    = $('#upcEditCode');
    var $editStatus  = $('#upcEditStatus');
    var $editingRow  = null;

    $editCourse.select2({ width: '100%', dropdownParent: $editModal });
    $editStatus.select2({ width: '100%', dropdownParent: $editModal });

    function editFieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    function clearEditErrors() {
        [$editCourse, $editOt, $editDt, $editCode].forEach(function ($el) {
            editFieldError($el, '');
        });
    }

    $table.on('click', '.coe-act--edit', function () {
        $editingRow = $(this).closest('tr');
        $editCourse.val($editingRow.attr('data-upc-course') || '').trigger('change.select2');
        $editOt.val($editingRow.attr('data-upc-ot') || '');
        $editDt.val($editingRow.attr('data-upc-dt') || '');
        $editCode.val($editingRow.attr('data-upc-code') || '');
        $editStatus.val($editingRow.attr('data-upc-status') || 'Pending').trigger('change.select2');
        clearEditErrors();
        bootstrap.Modal.getOrCreateInstance($editModal[0]).show();
    });

    $editModal.on('hidden.bs.modal', function () {
        $editingRow = null;
        clearEditErrors();
    });

    [$editCourse, $editOt, $editDt, $editCode].forEach(function ($el) {
        $el.on('input change', function () {
            if ($.trim(this.value || '') !== '') { editFieldError($el, ''); }
        });
    });

    $('#upcEditForm').on('submit', function (e) {
        e.preventDefault();
        var ok = true;
        ok = editFieldError($editCourse, $editCourse.val() ? '' : 'Select a course.') && ok;
        ok = editFieldError($editOt, $.trim($editOt.val()) ? '' : 'Enter the OT name.') && ok;
        ok = editFieldError($editDt, $.trim($editDt.val()) ? '' : 'Enter the DT name.') && ok;
        ok = editFieldError($editCode, $.trim($editCode.val()) ? '' : 'Enter the unique code.') && ok;
        if (!ok || !$editingRow) { return; }

        var course = $editCourse.val();
        var ot     = $.trim($editOt.val());
        var dt_    = $.trim($editDt.val());
        var code   = $.trim($editCode.val());
        var status = $editStatus.val();

        $editingRow.attr('data-upc-course', course)
                   .attr('data-upc-ot', ot)
                   .attr('data-upc-dt', dt_)
                   .attr('data-upc-code', code);

        $editingRow.find('.upc-cell-course').text(course);
        $editingRow.find('.upc-cell-ot').text(ot);
        $editingRow.find('.upc-cell-dt').text(dt_);
        $editingRow.find('.upc-cell-code').text(code);
        paintStatus($editingRow, status);

        bootstrap.Modal.getOrCreateInstance($editModal[0]).hide();
        Swal.fire({ icon: 'success', title: 'Updated', text: 'Participant code updated.' });
    });

    /* ── Generate modal (Create) ────────────────────────────────────────── */
    var $genModal   = $('#upcGenerateModal');
    var $genCourse  = $('#upcGenCourse');

    $genCourse.select2({ width: '100%', dropdownParent: $genModal });

    $('#upcGenerateBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($genModal[0]).show();
    });

    $genModal.on('hidden.bs.modal', function () {
        document.getElementById('upcGenerateForm').reset();
        $genCourse.val('').trigger('change.select2');
        $genModal.find('.coe-field-error').remove();
        $genModal.find('.is-invalid').removeClass('is-invalid');
    });

    function genFieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    $genCourse.on('change', function () { if (this.value) { genFieldError($genCourse, ''); } });

    $('#upcGenerateForm').on('submit', function (e) {
        e.preventDefault();
        var ok = true;
        ok = genFieldError($genCourse, $genCourse.val() ? '' : 'Select a course.') && ok;
        ok = genFieldError($('#upcGenPrefix'), $.trim($('#upcGenPrefix').val()) ? '' : 'Enter a prefix.') && ok;
        ok = genFieldError($('#upcGenStart'), $.trim($('#upcGenStart').val()) ? '' : 'Enter a start number.') && ok;
        if (!ok) { return; }

        var course = $genCourse.val();
        var prefix = $.trim($('#upcGenPrefix').val());
        var start  = parseInt($('#upcGenStart').val(), 10);
        var code   = prefix + start;
        var esc    = function (t) { return $('<div>').text(t).html(); };
        var newId  = 1 + Math.max(0, Math.max.apply(null,
            dt.rows().nodes().to$().map(function () {
                return parseInt(this.getAttribute('data-coe-id'), 10) || 0;
            }).get()
        ));

        var $newRow = $(dt.row.add([
            '<input type="checkbox" class="form-check-input m-0 upc-row-check" aria-label="Select">',
            '',
            esc(course),
            'Generated OT',
            'generatedot',
            esc(code),
            '<span class="coe-state coe-state--pending">Pending</span>',
            ACTION_HTML
        ]).draw(false).node());

        $newRow.attr({
            'data-coe-id': newId,
            'data-upc-course': course,
            'data-upc-ot': 'Generated OT',
            'data-upc-dt': 'generatedot',
            'data-upc-code': code,
            'data-upc-status': 'Pending'
        });
        $newRow.children('td').eq(COL.check).addClass('coe-col-check');
        $newRow.children('td').eq(COL.course).addClass('coe-strong upc-cell-course');
        $newRow.children('td').eq(COL.ot).addClass('upc-cell-ot');
        $newRow.children('td').eq(COL.dt).addClass('upc-cell-dt');
        $newRow.children('td').eq(COL.code).addClass('upc-cell-code');
        $newRow.children('td').eq(COL.status).addClass('coe-col-center upc-cell-status')
               .attr({'data-order': 0, 'data-search': 'Pending'});
        $newRow.children('td').eq(COL.action).addClass('upc-cell-actions');

        bootstrap.Modal.getOrCreateInstance($genModal[0]).hide();
        Swal.fire({ icon: 'success', title: 'Generated', text: 'Unique codes generated successfully.' });
    });
});
</script>
@endpush
