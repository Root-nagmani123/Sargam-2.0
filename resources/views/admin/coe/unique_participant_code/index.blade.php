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
            @include('admin.coe.partials.download_menu', ['prefix' => 'upc', 'csv' => true])
            @include('admin.coe.partials.print_brand')
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
                                    data-upc-course="{{ $row['course_name'] }} — {{ $row['batch'] }}"
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
                                        {{-- A frozen code has nothing left to do: the server refuses edit and delete. --}}
                                        @if ($row['status'] === 'Frozen')
                                            <span class="text-body-secondary">—</span>
                                        @else
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
                                        @endif
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

{{-- ── Generate Unique Code modal ─────────────────────────────────────────── --}}
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
                            <select id="upcGenCourse" name="course_batch" class="form-select coe-control" required>
                                <option value="">Select Course</option>
                                @foreach ($cohorts as $cohort)
                                    <option value="{{ $cohort['key'] }}" data-count="{{ $cohort['count'] }}">{{ $cohort['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcGenOtCount">Number of OTs</label>
                            <input type="text" id="upcGenOtCount" class="form-control coe-control" value="" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="upcGenPrefix">Unique Code Prefix<span class="coe-req">*</span></label>
                            <input type="text" id="upcGenPrefix" name="prefix" class="form-control coe-control"
                                   placeholder="eg. UC-" maxlength="20" required autocomplete="off">
                        </div>
                        <div class="col-sm-6">
                            {{-- Text, not number: leading zeros set the width (0324 → UC-0324). --}}
                            <label class="coe-form-label" for="upcGenStart">Count Start from<span class="coe-req">*</span></label>
                            <input type="text" id="upcGenStart" name="count_start" class="form-control coe-control"
                                   placeholder="eg. 324" maxlength="9" inputmode="numeric" pattern="\d*" required autocomplete="off">
                        </div>
                        <div class="col-12">
                            <div class="form-text" id="upcGenPreview">
                                Codes are given in name order. Generating again replaces the course's pending codes.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="upcGenSubmit">Generate</button>
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
                        {{-- Course, OT and DT name come from the masters; only the code is edited here. --}}
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditCourse">Course Name</label>
                            <input type="text" id="upcEditCourse" class="form-control coe-control" disabled>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditOt">OT Name</label>
                            <input type="text" id="upcEditOt" class="form-control coe-control" disabled>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditDt">DT Name</label>
                            <input type="text" id="upcEditDt" class="form-control coe-control" disabled>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="upcEditCode">Unique Code<span class="coe-req">*</span></label>
                            <input type="text" id="upcEditCode" name="unique_code" class="form-control coe-control"
                                   placeholder="eg. UC-234" maxlength="50" required autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="upcEditSubmit">Update</button>
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

    var ROUTES = {
        generate: @json(route('admin.coe.unique-participant-code.generate')),
        freeze: @json(route('admin.coe.unique-participant-code.freeze')),
        destroy: @json(route('admin.coe.unique-participant-code.bulk-destroy')),
        update: @json(route('admin.coe.unique-participant-code.update', ['code' => '__ID__']))
    };

    /* Column indexes — keep in step with the <thead>. */
    var COL = { check: 0, sno: 1, course: 2, ot: 3, dt: 4, code: 5, status: 6, action: 7 };

    var $table = $('#upcTable');

    var dt = CoeGrid.init({
        table: '#upcTable',
        title: @json($isArchived ? 'Unique Participant Code - Archived' : 'Unique Participant Code'),
        snoCol: COL.sno,
        order: [[COL.sno, 'asc']],
        noSort: [COL.check, COL.sno, COL.action],
        exclude: [COL.check, COL.action],
        filters: { '#upcFilterCourse': COL.course, '#upcFilterStatus': COL.status },
        reset: '#upcResetFilters',
        download: '#upcDownloadExcel', pdf: '#upcDownloadPdf', csv: '#upcDownloadCsv',
        print: '#upcPrintBtn',
        searchToggle: '#upcSearchToggle',
        searchSlot: '#upcDtSearch',
        colvisGrid: '#upcColumnToggleGrid',
        colvisKey: 'coeUniqueParticipantCode:hiddenColumns:v1',
        empty: { title: 'No Participant Codes', zeroTitle: 'No Records Found',
                 text: 'Generate unique codes to get started.' }
    });

    /* ── AJAX helpers ─────────────────────────────────────────────────── */
    function csrf() { return $('meta[name="csrf-token"]').attr('content') || ''; }

    function send(url, data, type) {
        return $.ajax({
            url: url,
            type: type || 'POST',
            data: data,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        });
    }

    function errorText(xhr) {
        if (xhr.status === 419) { return 'Your session has expired. Reload the page and try again.'; }
        var j = xhr.responseJSON || {};
        if (j.errors) {
            var first = Object.keys(j.errors)[0];
            if (first && j.errors[first][0]) { return j.errors[first][0]; }
        }
        return j.message || 'The request failed. Please try again.';
    }

    function showError(xhr) {
        Swal.fire({ icon: 'error', title: 'Could not complete', text: errorText(xhr) });
    }

    function idOf($row) { return parseInt($row.attr('data-coe-id'), 10); }

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

    function clearSelection() {
        $table.find('.upc-row-check').prop('checked', false);
        syncBulkButtons();
    }

    $('#upcCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.upc-row-check').prop('checked', this.checked);
        syncBulkButtons();
    });
    $table.on('change', '.upc-row-check', syncBulkButtons);
    dt.on('search.dt', function () { setTimeout(syncBulkButtons, 0); });

    /* ── Frozen: badge, and the row loses its actions ──────────────────── */
    function paintFrozen($row) {
        $row.find('.upc-cell-status')
            .attr('data-order', 1)
            .attr('data-search', 'Frozen')
            .html('<span class="coe-state coe-state--active">Frozen</span>');
        $row.find('.upc-cell-actions').html('<span class="text-body-secondary">—</span>');
        $row.attr('data-upc-status', 'Frozen');
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
    $('#upcFreezeAllBtn').on('click', function () { freeze(selectedRows()); });

    /* ── Delete ─────────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#upcTable',
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

    /* ── Edit: only the code ───────────────────────────────────────────── */
    var $editModal  = $('#upcEditModal');
    var $editCode   = $('#upcEditCode');
    var $editingRow = null;

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    $table.on('click', '.coe-act--edit', function () {
        $editingRow = $(this).closest('tr');
        $('#upcEditCourse').val($editingRow.attr('data-upc-course') || '');
        $('#upcEditOt').val($editingRow.attr('data-upc-ot') || '');
        $('#upcEditDt').val($editingRow.attr('data-upc-dt') || '');
        $editCode.val($editingRow.attr('data-upc-code') || '');
        fieldError($editCode, '');
        bootstrap.Modal.getOrCreateInstance($editModal[0]).show();
    });

    $editModal.on('hidden.bs.modal', function () {
        $editingRow = null;
        fieldError($editCode, '');
    });

    $editCode.on('input', function () { if ($.trim(this.value) !== '') { fieldError($editCode, ''); } });

    $('#upcEditForm').on('submit', function (e) {
        e.preventDefault();
        var code = $.trim($editCode.val());
        if (!fieldError($editCode, code ? '' : 'Enter the unique code.') || !$editingRow) { return; }

        var $row = $editingRow;
        var $btn = $('#upcEditSubmit').prop('disabled', true);

        send(ROUTES.update.replace('__ID__', idOf($row)), { unique_code: code }, 'PUT')
            .done(function (res) {
                $row.attr('data-upc-code', res.unique_code);
                $row.find('.upc-cell-code').text(res.unique_code);
                dt.row($row).invalidate('dom').draw(false);
                bootstrap.Modal.getOrCreateInstance($editModal[0]).hide();
                Swal.fire({ icon: 'success', title: 'Updated', text: res.message });
            })
            .fail(function (xhr) { fieldError($editCode, errorText(xhr)); })
            .always(function () { $btn.prop('disabled', false); });
    });

    /* ── Generate ───────────────────────────────────────────────────────── */
    var $genModal  = $('#upcGenerateModal');
    var $genCourse = $('#upcGenCourse');
    var $genPrefix = $('#upcGenPrefix');
    var $genStart  = $('#upcGenStart');

    $genCourse.select2({ width: '100%', dropdownParent: $genModal });

    $('#upcGenerateBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($genModal[0]).show();
    });

    $genModal.on('hidden.bs.modal', function () {
        document.getElementById('upcGenerateForm').reset();
        $genCourse.val('').trigger('change.select2');
        $('#upcGenOtCount').val('');
        $genModal.find('.coe-field-error').remove();
        $genModal.find('.is-invalid').removeClass('is-invalid');
    });

    $genCourse.on('change', function () {
        $('#upcGenOtCount').val($genCourse.find(':selected').data('count') || '');
        if (this.value) { fieldError($genCourse, ''); }
    });
    $genPrefix.on('input', function () { if ($.trim(this.value)) { fieldError($genPrefix, ''); } });
    $genStart.on('input', function () { if ($.trim(this.value)) { fieldError($genStart, ''); } });

    $('#upcGenerateForm').on('submit', function (e) {
        e.preventDefault();
        var prefix = $.trim($genPrefix.val());
        var start  = $.trim($genStart.val());
        var ok = true;
        ok = fieldError($genCourse, $genCourse.val() ? '' : 'Select a course.') && ok;
        ok = fieldError($genPrefix, prefix ? '' : 'Enter a prefix.') && ok;
        ok = fieldError($genStart, /^\d{1,9}$/.test(start) ? '' : 'Enter the start number in digits.') && ok;
        if (!ok) { return; }

        var $btn = $('#upcGenSubmit').prop('disabled', true);

        send(ROUTES.generate, { course_batch: $genCourse.val(), prefix: prefix, count_start: start })
            .done(function (res) {
                bootstrap.Modal.getOrCreateInstance($genModal[0]).hide();
                var text = res.message + (res.scope === 'archived' ? ' The course is archived, so the codes are listed under the Archived tab.' : '');
                Swal.fire({ icon: 'success', title: 'Generated', text: text })
                    .then(function () {
                        if (res.redirect) {
                            var url = new URL(res.redirect, window.location.origin);
                            var menu = new URLSearchParams(window.location.search).get('menu');
                            if (menu) { url.searchParams.set('menu', menu); }
                            window.location.assign(url.toString());
                        } else {
                            window.location.reload();
                        }
                    });
            })
            .fail(showError)
            .always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endpush
