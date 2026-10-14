@extends('admin.layouts.master')

@section('title', 'Examination Drive Configuration')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Tab pills */
.fem-tabs { display:flex; gap:8px; flex-wrap:wrap; }
.fem-tab {
    padding:6px 18px; border-radius:20px; font-size:13px; font-weight:600;
    border:none; cursor:pointer; background:#e9ecef; color:#495057;
}
.fem-tab.is-active.tab-pending   { background:#fff3cd; color:#856404; }
.fem-tab.is-active.tab-completed { background:#d1e7dd; color:#0f5132; }
.fem-tab.is-active.tab-recheck   { background:#004384; color:#fff; }

/* Booklet code input */
.fem-booklet { width:130px; font-size:13px; }
/* Marks spinner */
.fem-marks { width:80px; text-align:right; font-size:13px; }
/* Revised marks — red when changed */
.fem-revised.changed { color:#dc3545; font-weight:600; }
/* Attendance select */
.fem-att { width:120px; font-size:13px; }
/* Optional notes */
.fem-notes { width:140px; font-size:12px; color:#6c757d; border:none; background:transparent; }
.fem-notes:focus { border:1px solid #ced4da; background:#fff; border-radius:4px; }
/* Status badge */
.fem-badge-completed { background:#d1e7dd; color:#0f5132; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; }
/* Prev marks in recheck */
.fem-prev-marks { color:#dc3545; font-weight:600; }
</style>
@endpush

@section('setup_content')
@php
    $isPending   = $tab === 'pending';
    $isCompleted = $tab === 'completed';
    $isRecheck   = $tab === 'recheck';
@endphp

<div class="container-fluid coe-page">
    <x-breadcrum title="Examination Drive Configuration" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Evaluation & Marks (Faculty)']">
        <x-slot name="actions">
            @if ($isPending)
            <button type="button" id="femDraftBtn" class="btn btn-outline-secondary rounded-3 px-3">
                <i class="bi bi-floppy" aria-hidden="true"></i> Save as Draft
            </button>
            <button type="button" id="femSendBtn" class="btn btn-primary rounded-3 px-3"
                    style="background:#004384;border-color:#004384;">
                <i class="bi bi-send" aria-hidden="true"></i> Send For Verification
            </button>
            @else
            <button type="button" id="femDownloadBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="femPrintBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
            @endif
        </x-slot>
    </x-breadcrum>

    <x-session_message />

    {{-- Tabs + right tools --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="fem-tabs" aria-label="Status tabs">
            <a href="{{ route('admin.coe.faculty-evaluation-marks.index') }}"
               class="fem-tab tab-pending {{ $isPending ? 'is-active' : '' }}"
               @if($isPending) aria-current="page" @endif>
                Pending: {{ $pending->count() }}
            </a>
            <a href="{{ route('admin.coe.faculty-evaluation-marks.index', ['tab' => 'completed']) }}"
               class="fem-tab tab-completed {{ $isCompleted ? 'is-active' : '' }}"
               @if($isCompleted) aria-current="page" @endif>
                Completed: {{ $completed->count() }}
            </a>
            <a href="{{ route('admin.coe.faculty-evaluation-marks.index', ['tab' => 'recheck']) }}"
               class="fem-tab tab-recheck {{ $isRecheck ? 'is-active' : '' }}"
               @if($isRecheck) aria-current="page" @endif>
                Return for Recheck: {{ $recheck->count() }}
            </a>
        </nav>
        <div class="d-flex gap-2">
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
    </div>

    {{-- Search slot --}}
    <div id="femDtSearch" class="programme-dt-search d-none mb-2" data-dt-search-for="femTable"></div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="programme-dt-panel">
                <div class="table-responsive">

                    {{-- ========================= PENDING TAB ========================= --}}
                    @if ($isPending)
                    <table id="femTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>OT UNIQUE CODE</th>
                                <th>COURSE</th>
                                <th>SUBJECT / COMPONENT</th>
                                <th>BOOKLET CODE</th>
                                <th>ATTENDANCE</th>
                                <th>MARKS / 50</th>
                                <th>OPTIONAL NOTES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pending as $row)
                            <tr data-id="{{ $row['id'] }}"
                                data-booklet="{{ $row['booklet_code'] }}"
                                data-attendance="{{ $row['attendance'] }}"
                                data-marks="{{ $row['exam_marks'] }}">
                                <td>{{ $row['ot_code'] }}</td>
                                <td>{{ $row['course'] }}</td>
                                <td>{{ $row['subject_comp'] }}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm fem-booklet"
                                           value="{{ $row['booklet_code'] }}"
                                           placeholder="Booklet Code"
                                           aria-label="Booklet Code for {{ $row['ot_code'] }}">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm fem-att"
                                            aria-label="Attendance for {{ $row['ot_code'] }}">
                                        <option {{ $row['attendance'] === 'Present' ? 'selected' : '' }}>Present</option>
                                        <option {{ $row['attendance'] === 'Absent'  ? 'selected' : '' }}>Absent</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm fem-marks"
                                           min="0" max="50" step="0.5"
                                           value="{{ is_numeric($row['exam_marks']) ? $row['exam_marks'] : '' }}"
                                           placeholder="—"
                                           aria-label="Marks for {{ $row['ot_code'] }}">
                                </td>
                                <td>
                                    <input type="text" class="fem-notes"
                                           placeholder="Optional Notes"
                                           aria-label="Notes for {{ $row['ot_code'] }}">
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
                                <th>OT UNIQUE CODE</th>
                                <th>COURSE</th>
                                <th>SUBJECT / COMPONENT</th>
                                <th>BOOKLET CODE</th>
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
                                <td>{{ $row['booklet_code'] ?: '—' }}</td>
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
                                <th>OT UNIQUE CODE</th>
                                <th>BOOKLET CODE</th>
                                <th>COURSE</th>
                                <th>SUBJECT / COMPONENT</th>
                                <th>ATTENDANCE</th>
                                <th>PREV. MARKS / 50</th>
                                <th>REVISED MARKS / 50</th>
                                <th>FACULTY REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recheck as $row)
                            <tr data-prev="{{ $row['exam_marks'] }}">
                                <td>{{ $row['ot_code'] }}</td>
                                <td>{{ $row['booklet_code'] ?: '—' }}</td>
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
                                    <input type="text" class="form-control form-control-sm"
                                           style="min-width:140px;font-size:13px;"
                                           placeholder="Optional Note"
                                           value="{{ $row['faculty_remarks'] }}"
                                           aria-label="Faculty remarks for {{ $row['ot_code'] }}">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Resubmit button --}}
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" id="femResubmitBtn"
                                class="btn rounded-3 px-4 fw-semibold text-white"
                                style="background:#004384;border-color:#004384;">
                            Resubmit for Review
                        </button>
                    </div>
                    @endif

                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="femTable"></div>
            </div>
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

    var noSortCols  = isPending ? [3,4,5,6] : (isCompleted ? [7] : [6,7]);
    var excludeCols = isPending ? [3,4,5,6] : (isCompleted ? [7] : [6,7]);

    CoeGrid.init({
        table: '#femTable',
        title: 'Evaluation & Marks',
        snoCol: false,
        order: [[0, 'asc']],
        noSort: noSortCols,
        exclude: excludeCols,
        searchToggle: '#femSearchToggle',
        searchSlot: '#femDtSearch',
        colvisGrid: '#femColumnToggleGrid',
        colvisKey: 'coeFacultyEvalMarks:hiddenColumns:v1:' + (isPending ? 'p' : isCompleted ? 'c' : 'r'),
        empty: { title: 'No Records', text: 'No records found for this tab.' }
    });

    /* ---- Pending: mark changed inputs ---- */
    if (isPending) {
        $('#femSendBtn').on('click', function () {
            Swal.fire({
                icon: 'success',
                title: 'Sent for Verification',
                text: 'Your evaluation has been submitted for COE verification.',
                timer: 2200, showConfirmButton: false
            });
        });
        $('#femDraftBtn').on('click', function () {
            Swal.fire({
                icon: 'success',
                title: 'Draft Saved',
                text: 'Your progress has been saved as a draft.',
                timer: 2000, showConfirmButton: false
            });
        });
    }

    /* ---- Recheck: highlight revised marks that differ from previous ---- */
    if (isRecheck) {
        $('#femTable').on('input', '.fem-revised', function () {
            var prev = parseFloat($(this).closest('tr').data('prev'));
            var cur  = parseFloat($(this).val());
            $(this).toggleClass('changed', !isNaN(prev) && !isNaN(cur) && cur !== prev);
        });

        $('#femResubmitBtn').on('click', function () {
            Swal.fire({
                icon: 'success',
                title: 'Resubmitted for Review',
                text: 'Revised marks have been submitted to the COE for review.',
                timer: 2200, showConfirmButton: false
            });
        });
    }
});
</script>
@endpush
