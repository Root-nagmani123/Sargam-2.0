@extends('admin.layouts.master')

@section('title', 'Evaluation & Marks')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Status counter pills */
.em-pill           { display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;
                     font-size:13px;font-weight:600;cursor:pointer;border:2px solid transparent; }
.em-pill--pending  { background:#fff3cd;color:#856404;border-color:#ffc107; }
.em-pill--approved { background:#d1f0e0;color:#0d6832;border-color:#198754; }
.em-pill--recheck  { background:#f8d7da;color:#842029;border-color:#dc3545; }
/* Approval stage badge */
.em-stage-pending  { background:#fff3cd;color:#856404; }
.em-stage-approved { background:#d1f0e0;color:#0d6832; }
.em-stage-recheck  { background:#f8d7da;color:#842029; }
/* Action icon buttons */
.em-act-approve    { background:none;border:none;padding:0;cursor:pointer;color:#198754;font-size:22px;line-height:1; }
.em-act-recheck    { background:none;border:none;padding:0;cursor:pointer;color:#dc3545;font-size:22px;line-height:1; }
.em-act-approve:hover{ color:#146c43; }
.em-act-recheck:hover{ color:#b02a37; }
.em-act-group      { display:flex;flex-direction:column;align-items:center;gap:6px; }
.em-act-label      { font-size:10px;color:var(--ds-ink-muted);line-height:1;text-align:center; }
/* Admin note inline input */
.em-note-input     { border:none;border-bottom:1px dashed var(--ds-line);background:transparent;
                     font-size:12px;color:var(--ds-ink-muted);width:100%;padding:2px 0;min-width:100px; }
.em-note-input:focus{ outline:none;border-bottom-color:var(--ds-brand);color:var(--ds-ink); }
/* Bulk action buttons */
.em-bulk-recheck   { background:#dc3545;color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer; }
.em-bulk-recheck:hover{ background:#b02a37; }
.em-bulk-approve   { background:#198754;color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer; }
.em-bulk-approve:hover{ background:#146c43; }
/* Attendance badges */
.em-att-present    { color:#0d6832;font-weight:600; }
.em-att-absent     { color:#842029;font-weight:600; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Evaluation &amp; Marks" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Evaluation & Marks']">
        {{-- Bulk actions – visible when rows selected --}}
        <div id="emBulkActions" class="d-none d-flex gap-2">
            <button type="button" class="em-bulk-recheck" id="emReturnAllBtn">Returned All for Recheck</button>
            <button type="button" class="em-bulk-approve" id="emApproveAllBtn">Approve All</button>
        </div>
    </x-breadcrum>

    <x-session_message />

    {{-- Status counter pills + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2" id="emPills">
            <span class="em-pill em-pill--pending">
                Pending: <strong id="emCntPending">{{ $pendingCount }}</strong>
            </span>
            <span class="em-pill em-pill--approved">
                Approved: <strong id="emCntApproved">{{ $approvedCount }}</strong>
            </span>
            @if ($recheckCount > 0)
            <span class="em-pill em-pill--recheck" id="emPillRecheck">
                Returned for Recheck: <strong id="emCntRecheck">{{ $recheckCount }}</strong>
            </span>
            @endif
        </div>
        <div class="d-flex gap-2 coe-secondary-actions">
            <button type="button" id="emDownloadBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="emPrintBtn" class="btn programme-dt-btn-columns">
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
                        <select id="emFilterCourse" class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="emFilterExam" class="form-select" aria-label="Examination">
                            <option value="">Examination</option>
                            @foreach ($examinations as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="emFilterStatus" class="form-select" aria-label="Approval Status">
                            <option value="">Approval Status</option>
                            @foreach ($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                        </select>
                    </div>
                    <button type="button" id="emResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#emColumnVisibilityModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="emDtSearch" class="programme-dt-search d-none" data-dt-search-for="emTable"></div>
                    <button type="button" id="emSearchToggle" class="coe-search-toggle"
                            aria-controls="emDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="emTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="emCheckAll" class="form-check-input" style="width:16px;height:16px;accent-color:#004384"></th>
                                <th>S.No</th>
                                <th>Course</th>
                                <th>Subject / Component</th>
                                <th>OT Unique Code</th>
                                <th>Marks Source</th>
                                <th>Faculty Name</th>
                                <th>Booklet Code</th>
                                <th>Attendance</th>
                                <th>Assessment Marks / 50</th>
                                <th>Exam Marks / 50</th>
                                <th>Faculty Remarks</th>
                                <th>Admin Review Note</th>
                                <th>Approval Stage</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                            @php
                                $stageClass = match($row['approval_stage']) {
                                    'Approved'              => 'em-stage-approved',
                                    'Returned for Recheck'  => 'em-stage-recheck',
                                    default                 => 'em-stage-pending',
                                };
                                $attClass = $row['attendance'] === 'Absent' ? 'em-att-absent' : 'em-att-present';
                            @endphp
                            <tr data-coe-id="{{ $row['id'] }}"
                                data-em-stage="{{ $row['approval_stage'] }}"
                                data-em-course="{{ $row['course'] }}">
                                <td><input type="checkbox" class="em-row-check form-check-input" style="width:16px;height:16px;accent-color:#004384"></td>
                                <td></td>
                                <td>{{ $row['course'] }}</td>
                                <td>{{ $row['subject_comp'] }}</td>
                                <td>{{ $row['ot_code'] }}</td>
                                <td>{{ $row['marks_source'] ?: '—' }}</td>
                                <td>{{ $row['faculty_name'] }}</td>
                                <td>{{ $row['booklet_code'] }}</td>
                                <td class="{{ $attClass }}">{{ $row['attendance'] }}</td>
                                <td>{{ $row['assess_marks'] !== '-' ? $row['assess_marks'] : '—' }}</td>
                                <td>{{ is_numeric($row['exam_marks']) ? number_format($row['exam_marks'], 2) : ($row['exam_marks'] ?: '—') }}</td>
                                <td style="max-width:140px;font-size:12px;color:#6c757d">{{ $row['faculty_remarks'] ?: '—' }}</td>
                                <td>
                                    <input type="text" class="em-note-input"
                                           placeholder="Optional Notes"
                                           value="{{ $row['admin_note'] }}"
                                           aria-label="Admin review note">
                                </td>
                                <td>
                                    <span class="coe-state {{ $stageClass }}">{{ $row['approval_stage'] }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-3">
                                        <div class="em-act-group">
                                            <button type="button" class="em-act-approve em-act--approve"
                                                    title="Approve" aria-label="Approve">
                                                <i class="bi bi-hand-thumbs-up-fill" aria-hidden="true"></i>
                                            </button>
                                            <span class="em-act-label">Approve</span>
                                        </div>
                                        <div class="em-act-group">
                                            <button type="button" class="em-act-recheck em-act--recheck"
                                                    title="Return for Recheck" aria-label="Return for Recheck">
                                                <i class="bi bi-hand-thumbs-down-fill" aria-hidden="true"></i>
                                            </button>
                                            <span class="em-act-label">Return for<br>Recheck</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="emTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Return for Recheck modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="emRecheckModal" tabindex="-1" aria-labelledby="emRecheckModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header border-0 pb-1">
                <h5 class="modal-title fw-bold" id="emRecheckModalLabel">Return for Recheck</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body pt-2">
                <label class="coe-form-label" for="emRecheckReason">Reason<span class="coe-req">*</span></label>
                <textarea id="emRecheckReason" class="form-control coe-control" rows="3"
                          placeholder="eg. Marks should be under 50" maxlength="500"></textarea>
                <div class="coe-field-error d-none" id="emRecheckError">Please enter a reason.</div>
            </div>
            <div class="modal-footer coe-modal-footer border-0 pt-0">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="emRecheckConfirm">Return for Recheck</button>
            </div>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="emColumnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="emColumnToggleGrid"></div>
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

    var COL = { chk:0, sno:1, course:2, subj:3, ot:4, src:5, fac:6, book:7, att:8, assess:9, exam:10, facRem:11, note:12, stage:13, action:14 };

    var $table = $('#emTable');

    /* Counts */
    var counts = {
        pending:  parseInt($('#emCntPending').text(), 10)  || 0,
        approved: parseInt($('#emCntApproved').text(), 10) || 0,
        recheck:  parseInt($('#emCntRecheck').text(), 10)  || 0,
    };

    function updatePills() {
        $('#emCntPending').text(counts.pending);
        $('#emCntApproved').text(counts.approved);
        $('#emCntRecheck').text(counts.recheck);
        var $pr = $('#emPillRecheck');
        if (counts.recheck > 0) {
            if (!$pr.length) {
                $('#emPills').append('<span class="em-pill em-pill--recheck" id="emPillRecheck">Returned for Recheck: <strong id="emCntRecheck">' + counts.recheck + '</strong></span>');
            } else { $pr.show(); }
        } else { $pr.hide(); }
    }

    /* ── DataTable ────────────────────────────────────────────────────── */
    var dt = CoeGrid.init({
        table: '#emTable',
        title: 'Evaluation & Marks',
        snoCol: COL.sno,
        order: [[COL.course, 'asc'], [COL.ot, 'asc']],
        noSort: [COL.chk, COL.sno, COL.note, COL.action],
        exclude: [COL.chk, COL.note, COL.action],
        filters: { '#emFilterCourse': COL.course, '#emFilterStatus': COL.stage },
        reset: '#emResetFilters',
        download: '#emDownloadBtn',
        print: '#emPrintBtn',
        searchToggle: '#emSearchToggle',
        searchSlot: '#emDtSearch',
        colvisGrid: '#emColumnToggleGrid',
        colvisKey: 'coeEvaluationMarks:hiddenColumns:v1',
        pageLength: 10,
        empty: { title: 'No Records', text: 'No evaluation records found.' }
    });

    /* ── Checkbox / bulk ──────────────────────────────────────────────── */
    var $checkAll = $('#emCheckAll');

    function syncBulk() {
        var checked = $table.find('.em-row-check:checked').length;
        $('#emBulkActions').toggleClass('d-none', checked === 0).toggleClass('d-flex', checked > 0);
        $checkAll.prop('indeterminate', checked > 0 && checked < $table.find('.em-row-check').length);
        $checkAll.prop('checked', checked > 0 && checked === $table.find('.em-row-check').length);
    }

    $checkAll.on('change', function () {
        $table.find('.em-row-check').prop('checked', this.checked);
        syncBulk();
    });
    $table.on('change', '.em-row-check', syncBulk);

    /* ── Helper: update row stage ─────────────────────────────────────── */
    function setRowStage($row, stage) {
        var oldStage = $row.attr('data-em-stage');
        if (oldStage === stage) { return; }
        // Adjust counts
        if (oldStage === 'Pending Review')        { counts.pending  = Math.max(0, counts.pending  - 1); }
        else if (oldStage === 'Approved')          { counts.approved = Math.max(0, counts.approved - 1); }
        else if (oldStage === 'Returned for Recheck'){ counts.recheck = Math.max(0, counts.recheck - 1); }

        if (stage === 'Pending Review')            { counts.pending++; }
        else if (stage === 'Approved')             { counts.approved++; }
        else if (stage === 'Returned for Recheck') { counts.recheck++; }

        var cls = { 'Pending Review':'em-stage-pending', 'Approved':'em-stage-approved', 'Returned for Recheck':'em-stage-recheck' }[stage] || 'em-stage-pending';
        $row.attr('data-em-stage', stage);
        $row.find('.coe-state').attr('class', 'coe-state ' + cls).text(stage);
        dt.row($row).invalidate('dom');
        updatePills();
    }

    /* ── Row-level Approve ────────────────────────────────────────────── */
    $table.on('click', '.em-act--approve', function () {
        var $row = $(this).closest('tr');
        setRowStage($row, 'Approved');
        dt.draw(false);
        Swal.fire({ icon: 'success', title: 'Approved', text: 'Marks entry approved.', timer: 1500, showConfirmButton: false });
    });

    /* ── Row-level Return for Recheck ─────────────────────────────────── */
    var $recheckTargetRow = null;
    var $recheckModal = $('#emRecheckModal');

    $table.on('click', '.em-act--recheck', function () {
        $recheckTargetRow = $(this).closest('tr');
        $('#emRecheckReason').val('');
        $('#emRecheckError').addClass('d-none');
        bootstrap.Modal.getOrCreateInstance($recheckModal[0]).show();
    });

    $('#emRecheckConfirm').on('click', function () {
        var reason = $.trim($('#emRecheckReason').val());
        if (!reason) {
            $('#emRecheckError').removeClass('d-none');
            return;
        }
        bootstrap.Modal.getOrCreateInstance($recheckModal[0]).hide();
        if ($recheckTargetRow) {
            setRowStage($recheckTargetRow, 'Returned for Recheck');
            dt.draw(false);
        }
        $recheckTargetRow = null;
        Swal.fire({ icon: 'info', title: 'Returned for Recheck', text: 'Entry returned for recheck.', timer: 1800, showConfirmButton: false });
    });

    $recheckModal.on('hidden.bs.modal', function () { $('#emRecheckError').addClass('d-none'); });

    /* ── Bulk Approve All ─────────────────────────────────────────────── */
    $('#emApproveAllBtn').on('click', function () {
        $table.find('.em-row-check:checked').each(function () {
            setRowStage($(this).closest('tr'), 'Approved');
        });
        dt.draw(false);
        syncBulk();
        Swal.fire({ icon: 'success', title: 'Approved', text: 'Selected entries approved.', timer: 1500, showConfirmButton: false });
    });

    /* ── Bulk Return All for Recheck ──────────────────────────────────── */
    $('#emReturnAllBtn').on('click', function () {
        $recheckTargetRow = null; // bulk mode
        $('#emRecheckReason').val('');
        $('#emRecheckError').addClass('d-none');
        bootstrap.Modal.getOrCreateInstance($recheckModal[0]).show();
    });

    // When confirm fires with no specific row → bulk
    $('#emRecheckConfirm').off('click').on('click', function () {
        var reason = $.trim($('#emRecheckReason').val());
        if (!reason) { $('#emRecheckError').removeClass('d-none'); return; }
        bootstrap.Modal.getOrCreateInstance($recheckModal[0]).hide();
        if ($recheckTargetRow) {
            setRowStage($recheckTargetRow, 'Returned for Recheck');
            dt.draw(false);
        } else {
            // Bulk
            $table.find('.em-row-check:checked').each(function () {
                setRowStage($(this).closest('tr'), 'Returned for Recheck');
            });
            dt.draw(false);
            syncBulk();
        }
        $recheckTargetRow = null;
        Swal.fire({ icon: 'info', title: 'Returned for Recheck', timer: 1500, showConfirmButton: false });
    });
});
</script>
@endpush
