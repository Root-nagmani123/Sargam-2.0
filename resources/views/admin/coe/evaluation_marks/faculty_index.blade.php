@extends('admin.layouts.master')

@section('title', 'Evaluation & Marks Entry')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Editable cells (Figma 1655:22243): Attendance 121px, Marks 120px, Remarks 188px. */
.fem-att     { width:121px; font-size:12px; }
.fem-marks   { width:120px; font-size:12px; }
.fem-notes   { width:188px; font-size:12px; }
.fem-att, .fem-marks, .fem-notes { color:#000; }
.fem-notes::placeholder, .fem-marks::placeholder { color:#a6a6a6; }
.fem-marks:disabled::placeholder { color:#000; }
/* Absent: yellow attendance, marks locked as "Not applicable". */
.fem-att.is-absent { background-color:#fff6d7; }
.fem-marks:disabled { background:#e8e8e8; border-color:#e8e8e8; }
/* Revised marks — red when changed */
.fem-revised.changed { color:#dc3545; font-weight:600; }
.fem-prev-marks { color:#dc3545; font-weight:600; }
.fem-badge-completed { background:#d1e7dd; color:#0f5132; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; }
/* coe-admin.css centres the last column; Remarks is a left-aligned field. */
.coe-page #femTable th:last-child,
.coe-page #femTable td:last-child { text-align:left; }
.coe-page .coe-btn-header.fem-btn-outline { background:#fff; color:var(--coe-brand); }
.coe-page .coe-btn-header.fem-btn-outline:hover { background:#e7eefb; color:var(--coe-brand); }
.fem-reset {color:var(--coe-brand); font-weight:500; text-decoration:underline; padding-left:0; }
</style>
@endpush

@section('setup_content')
@php
    $isPending   = $tab === 'pending';
    $isCompleted = $tab === 'completed';
    $isRecheck   = $tab === 'recheck';
@endphp

<div class="container-fluid coe-page">
    <x-breadcrum title="Evaluation & Marks Entry" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Evaluation & Marks Entry']">
        @if ($isPending)
        <div class="d-flex flex-wrap gap-3">
            <button type="button" id="femDraftBtn" class="btn coe-btn-header fem-btn-outline">
                <i class="bi bi-folder-check" aria-hidden="true"></i><span>Save as Draft</span>
            </button>
            <button type="button" id="femImportBtn" class="btn coe-btn-header">
                <i class="bi bi-upload" aria-hidden="true"></i><span>Import</span>
            </button>
        </div>
        @endif
    </x-breadcrum>

    <x-session_message />

    {{-- Status tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Status">
            <a href="{{ route('admin.coe.faculty-evaluation-marks.index') }}"
               class="coe-tab {{ $isPending ? 'is-active' : '' }}"
               @if($isPending) aria-current="page" @endif>Pending: {{ $pending->count() }}</a>
            <a href="{{ route('admin.coe.faculty-evaluation-marks.index', ['tab' => 'completed']) }}"
               class="coe-tab {{ $isCompleted ? 'is-active' : '' }}"
               @if($isCompleted) aria-current="page" @endif>Completed: {{ $completed->count() }}</a>
        </nav>
        <div class="d-flex gap-2 coe-secondary-actions">
            <button type="button" id="femDownloadBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="femPrintBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Columns + Search --}}
            <div class="d-flex justify-content-end gap-2 mb-3">
                <button type="button" class="btn programme-dt-btn-columns"
                        data-bs-toggle="modal" data-bs-target="#femColModal">
                    <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <button type="button" id="femSearchToggle" class="coe-search-toggle" title="Search"
                        aria-controls="femDtSearch" aria-expanded="false">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span class="visually-hidden">Search</span>
                </button>
            </div>
            <div id="femDtSearch" class="programme-dt-search d-none mb-2" data-dt-search-for="femTable"></div>

            <div class="programme-dt-panel">
                <div class="table-responsive">

                    {{-- ========================= PENDING TAB ========================= --}}
                    @if ($isPending)
                    <table id="femTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>OT Unique Code</th>
                                <th>Course</th>
                                <th>Subject / Component</th>
                                <th>ATTENDANCE</th>
                                <th>MARKS / 50</th>
                                <th>REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pending as $row)
                            @php $absent = $row['attendance'] === 'Absent'; @endphp
                            <tr data-id="{{ $row['id'] }}">
                                <td>{{ $row['ot_code'] }}</td>
                                <td>{{ $row['course'] }}</td>
                                <td>{{ $row['subject_comp'] }}</td>
                                <td>
                                    <select class="form-select form-select-sm fem-att {{ $absent ? 'is-absent' : '' }}"
                                            aria-label="Attendance for {{ $row['ot_code'] }}">
                                        <option @selected(! $absent)>Present</option>
                                        <option @selected($absent)>Absent</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm fem-marks"
                                           min="0" max="50" step="0.5"
                                           value="{{ ! $absent && is_numeric($row['exam_marks']) ? number_format($row['exam_marks'], 2, '.', '') : '' }}"
                                           placeholder="{{ $absent ? 'Not applicable' : '' }}"
                                           @disabled($absent)
                                           aria-label="Marks for {{ $row['ot_code'] }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm fem-notes"
                                           placeholder="Optional Notes"
                                           value="{{ $row['faculty_remarks'] }}"
                                           aria-label="Remarks for {{ $row['ot_code'] }}">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- ========================= COMPLETED TAB ========================= --}}
                    @elseif ($isCompleted)
                    <table id="femTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>OT Unique Code</th>
                                <th>Course</th>
                                <th>Subject / Component</th>
                                <th>ATTENDANCE</th>
                                <th>MARKS / 50</th>
                                <th>REMARKS</th>
                                <th class="coe-col-center">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($completed as $row)
                            <tr>
                                <td>{{ $row['ot_code'] }}</td>
                                <td>{{ $row['course'] }}</td>
                                <td>{{ $row['subject_comp'] }}</td>
                                <td>{{ $row['attendance'] }}</td>
                                <td>{{ is_numeric($row['exam_marks']) ? number_format($row['exam_marks'], 2) : '—' }}</td>
                                <td>{{ $row['faculty_remarks'] ?: '—' }}</td>
                                <td class="coe-col-center">
                                    <span class="fem-badge-completed">Completed</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- ========================= RETURN FOR RECHECK TAB ========================= --}}
                    @else
                    <table id="femTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>OT Unique Code</th>
                                <th>Course</th>
                                <th>Subject / Component</th>
                                <th>ATTENDANCE</th>
                                <th>PREV. MARKS / 50</th>
                                <th>REVISED MARKS / 50</th>
                                <th>REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recheck as $row)
                            <tr data-prev="{{ $row['exam_marks'] }}">
                                <td>{{ $row['ot_code'] }}</td>
                                <td>{{ $row['course'] }}</td>
                                <td>{{ $row['subject_comp'] }}</td>
                                <td>{{ $row['attendance'] }}</td>
                                <td class="fem-prev-marks">{{ is_numeric($row['exam_marks']) ? number_format($row['exam_marks'], 2) : '—' }}</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm fem-marks fem-revised"
                                           min="0" max="50" step="0.5"
                                           value="{{ is_numeric($row['exam_marks']) ? $row['exam_marks'] : '' }}"
                                           aria-label="Revised marks for {{ $row['ot_code'] }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm fem-notes"
                                           placeholder="Optional Notes"
                                           value="{{ $row['faculty_remarks'] }}"
                                           aria-label="Remarks for {{ $row['ot_code'] }}">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif

                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="femTable"></div>
            </div>

            @if ($isPending)
            <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
                <button type="button" id="femResetBtn" class="btn btn-link fem-reset">Reset the data</button>
                <button type="button" id="femApproveBtn" class="btn btn-primary rounded-3 px-4">Approved</button>
            </div>
            @elseif ($isRecheck)
            <div class="d-flex justify-content-end mt-3">
                <button type="button" id="femResubmitBtn" class="btn btn-primary rounded-3 px-4">Resubmit for Review</button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="femColModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="femColumnToggleGrid"></div>
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
    var isPending   = {{ $isPending   ? 'true' : 'false' }};
    var isCompleted = {{ $isCompleted ? 'true' : 'false' }};
    var isRecheck   = {{ $isRecheck   ? 'true' : 'false' }};

    var dt = CoeGrid.init({
        table: '#femTable',
        title: 'Evaluation & Marks',
        snoCol: false,
        order: [[0, 'asc']],
        noSort: isPending ? [3, 4, 5] : (isCompleted ? [6] : [5, 6]),
        exclude: isCompleted ? [6] : [],
        download: '#femDownloadBtn',
        print: '#femPrintBtn',
        searchToggle: '#femSearchToggle',
        searchSlot: '#femDtSearch',
        colvisGrid: '#femColumnToggleGrid',
        colvisKey: 'coeFacultyEvalMarks:hiddenColumns:v2:' + (isPending ? 'p' : isCompleted ? 'c' : 'r'),
        empty: { title: 'No Records', text: 'No records found for this tab.' }
    });

    if (isPending) {
        // Absent: marks are not applicable; Present restores what was typed.
        function syncAttendance($sel) {
            var absent = $sel.val() === 'Absent';
            var $marks = $sel.closest('tr').find('.fem-marks');
            $sel.toggleClass('is-absent', absent);
            if (absent) { $marks.data('kept', $marks.val()).val(''); }
            else if ($marks.prop('disabled')) { $marks.val($marks.data('kept') || ''); }
            $marks.prop('disabled', absent).attr('placeholder', absent ? 'Not applicable' : '');
        }
        $('#femTable').on('change', '.fem-att', function () { syncAttendance($(this)); });

        // Snapshot the loaded values so "Reset the data" can restore them —
        // across every page, not only the rows DataTables has in the DOM.
        var $fields = function () { return dt.rows().nodes().to$().find('select, input'); };
        $fields().each(function () { $(this).data('initial', $(this).val()); });
        $('#femResetBtn').on('click', function () {
            $fields().each(function () { $(this).val($(this).data('initial')); });
            dt.rows().nodes().to$().find('.fem-att').each(function () { syncAttendance($(this)); });
        });

        // shortcut: design-first screen, nothing is persisted yet; wire to a save route when the backend lands.
        $('#femDraftBtn').on('click', function () {
            Swal.fire({ icon: 'success', title: 'Draft Saved', text: 'Your progress has been saved as a draft.', timer: 2000, showConfirmButton: false });
        });
        $('#femApproveBtn').on('click', function () {
            Swal.fire({ icon: 'success', title: 'Marks Approved', text: 'Your evaluation has been submitted for COE verification.', timer: 2200, showConfirmButton: false });
        });
        $('#femImportBtn').on('click', function () {
            Swal.fire({ icon: 'info', title: 'Import', text: 'Importing marks from a file is not available yet.' });
        });
    }

    if (isRecheck) {
        $('#femTable').on('input', '.fem-revised', function () {
            var prev = parseFloat($(this).closest('tr').data('prev'));
            var cur  = parseFloat($(this).val());
            $(this).toggleClass('changed', !isNaN(prev) && !isNaN(cur) && cur !== prev);
        });
        $('#femResubmitBtn').on('click', function () {
            Swal.fire({ icon: 'success', title: 'Resubmitted for Review', text: 'Revised marks have been submitted to the COE for review.', timer: 2200, showConfirmButton: false });
        });
    }
});
</script>
@endpush
