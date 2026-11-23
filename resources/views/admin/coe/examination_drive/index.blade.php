@extends('admin.layouts.master')

@section('title', 'Examination Drive Configuration')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Subject/Faculty mapping table in modal */
.edc-map-table       { width:100%;border-collapse:collapse; }
.edc-map-table th    { padding:8px 10px;background:var(--ds-surface-2);font-size:12px;
                        font-weight:600;color:var(--ds-ink-muted);border-bottom:1px solid var(--ds-line); }
.edc-map-table td    { padding:6px 6px;border-bottom:1px solid var(--ds-line);vertical-align:middle; }
.edc-map-row-del     { background:#dc3545;color:#fff;border:none;border-radius:50%;
                        width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer; }
.edc-map-row-add     { background:#004384;color:#fff;border:none;border-radius:50%;
                        width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer; }
.edc-map-row-del:hover{ background:#b02a37; }
.edc-map-row-add:hover{ background:#003070; }
/* "+" only on the last mapping row; earlier rows keep just "−" (space kept so they line up). */
.edc-map-row:not(:last-child) .edc-map-row-add { visibility:hidden; }
/* Re-exam (ReexamController) */
.coe-page .coe-act--reexam   { color:#dc8a00; }
.edc-reexam-tag              { margin-left:4px;font-size:11px;white-space:nowrap; }
/* The modal sits outside .coe-page, so the shared .coe-tabs / .coe-state rules don't reach it:
   its own styles below (Figma "Create Re-exam"). */
#edcReexamModal .modal-title { font-size:1.375rem;font-weight:500;color:var(--ds-ink); }
.edc-rx-intro                { color:#475467;font-size:15px;line-height:1.6;margin:0 0 16px;padding-bottom:16px;border-bottom:1px solid var(--ds-line); }
.edc-rx-bar                  { display:flex;flex-wrap:wrap;align-items:center;gap:16px;margin-bottom:14px; }
.edc-rx-tabs                 { display:inline-flex;gap:8px;padding:6px;border-radius:var(--ds-radius-2);background:#e4e7ec; }
.edc-rx-tab                  { min-width:96px;padding:.55rem 1.25rem;border:0;border-radius:var(--ds-radius-2);background:#fff;
                               color:var(--ds-ink);font-size:.9375rem;font-weight:500;white-space:nowrap;cursor:pointer; }
.edc-rx-tab:hover            { color:var(--coe-brand); }
.edc-rx-tab.is-active        { background:var(--coe-brand);color:#fff;font-weight:600; }
.edc-rx-tab:focus-visible    { outline:2px solid var(--coe-brand);outline-offset:2px; }
.edc-rx-note                 { color:var(--ds-ink-muted);font-size:13px; }
.edc-rx-wrap                 { max-height:340px;overflow:auto;border:1px solid var(--ds-line);border-radius:var(--ds-radius-2); }
.edc-rx-table                { width:100%;border-collapse:collapse;font-size:15px;color:var(--ds-ink); }
.edc-rx-table th             { position:sticky;top:0;z-index:1;padding:12px 14px;background:#eef0f3;font-size:13px;
                               font-weight:500;color:#344054;text-transform:uppercase;letter-spacing:.02em;
                               text-align:left;border-bottom:1px solid var(--ds-line); }
.edc-rx-table td             { padding:10px 14px;border-bottom:1px solid var(--ds-line);vertical-align:middle; }
.edc-rx-table tbody tr:last-child td { border-bottom:0; }
.edc-rx-table td small       { display:block;margin-top:2px;color:var(--ds-ink-muted);font-size:13px; }
.edc-rx-table .edc-rx-check  { width:48px;text-align:center; }
.edc-rx-reason               { display:inline-block;padding:3px 10px;border-radius:var(--ds-radius-1);font-size:13px;font-weight:500;line-height:1.4; }
.edc-rx-reason--failed       { background:#fde2e4;color:#d92d20; }
.edc-rx-reason--absent       { background:#fef0c7;color:#b54708; }
.edc-rx-empty                { padding:24px;text-align:center;color:var(--ds-ink-muted); }
/* Re-exam Result (ReexamController::result) */
.coe-page .coe-act--rxresult { color:#067647; }
#edcRxResultModal .modal-title { font-size:1.375rem;font-weight:500;color:var(--ds-ink); }
.edc-rx-result               { display:inline-block;padding:3px 10px;border-radius:var(--ds-radius-1);font-size:13px;font-weight:500;line-height:1.4; }
.edc-rx-result--pass         { background:#dcfae6;color:#067647; }
.edc-rx-result--fail         { background:#fde2e4;color:#d92d20; }
.edc-rx-result--pending      { background:#eef0f3;color:#475467; }
</style>
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Examination Drive Configuration" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Roll Numbers']">
        <button type="button" class="btn coe-btn-header" id="edcCreateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Create Examination Drive</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Scope">
            <a href="{{ route('admin.coe.examination-drive.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.examination-drive.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        {{-- Sargam's master export (docs/new-design-index-page.md §1): CSV / Excel / PDF and a
             server-rendered branded Print (landscape), from ExaminationDriveController::export.
             edcSyncExportLinks() stamps the tab, filters, search and visible columns on. --}}
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <div class="dropdown">
                <button type="button" id="edcDownloadToggle"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="edcDownloadToggle">
                    <li><a class="dropdown-item edc-export-link" href="{{ route('admin.coe.examination-drive.export', ['format' => 'csv']) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                    <li><a class="dropdown-item edc-export-link" href="{{ route('admin.coe.examination-drive.export', ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item edc-export-link" href="{{ route('admin.coe.examination-drive.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
                </ul>
            </div>
            <a href="{{ route('admin.coe.examination-drive.export', ['format' => 'print']) }}" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary edc-export-link" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterCourse" class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterTerm" class="form-select" aria-label="Term">
                            <option value="">Term</option>
                            @foreach ($terms as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterPhase" class="form-select" aria-label="Phase">
                            <option value="">Phase</option>
                            @foreach ($phases as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterType" class="form-select" aria-label="Examination Type">
                            <option value="">Examination Type</option>
                            @foreach ($examTypes as $et)<option value="{{ $et }}">{{ $et }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterStatus" class="form-select" aria-label="Status">
                            <option value="">Status</option>
                            @foreach (\App\DataTables\ExaminationDriveDataTable::STATUS_FILTERS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="edcResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#edcColumnVisibilityModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="edcDtSearch" class="programme-dt-search d-none" data-dt-search-for="edcTable"></div>
                    <button type="button" id="edcSearchToggle" class="coe-search-toggle"
                            aria-controls="edcDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="edcTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S. NO.</th>
                                <th>DRIVE NAME</th>
                                <th>EXAMINATION TYPE</th>
                                <th>TERM</th>
                                <th>COURSE</th>
                                <th>SERVICE</th>
                                <th>ACADEMIC SESSION</th>
                                <th>FACULTY</th>
                                <th class="coe-col-center">SUB-COORDINATES</th>
                                <th class="coe-col-center">COMPONENTS</th>
                                <th>START DATE</th>
                                <th>END DATE</th>
                                <th class="coe-col-center">STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Rows loaded via Yajra server-side AJAX --}}
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="edcTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Create / Edit Drive modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="edcFormModal" tabindex="-1" aria-labelledby="edcFormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="edcForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="edcFormModalLabel">Create Examination Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    {{-- Drive Name (create and edit) --}}
                    <div id="edcDriveNameRow" class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="edcDriveName">Drive Name<span class="coe-req">*</span></label>
                            <input type="text" id="edcDriveName" name="drive_name" class="form-control coe-control"
                                   placeholder="eg. Foundation Exam Drive" maxlength="150" autocomplete="off">
                        </div>
                    </div>
                    {{-- Course. No Service: a drive is for the whole course, and each OT's
                         passing marks follow their own service (ResultMarksService::passingPercent). --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="edcCourse">Course<span class="coe-req">*</span></label>
                            <select id="edcCourse" name="course" class="form-select coe-control">
                                <option value="">Select Course</option>
                                @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Examination Type (optional) + Term --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcExamType">Examination Type</label>
                            <select id="edcExamType" name="exam_type" class="form-select coe-control">
                                <option value="">Select Examination Type</option>
                                @foreach ($activeExamTypes as $et)<option value="{{ $et }}">{{ $et }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcTerm">Term<span class="coe-req">*</span></label>
                            <select id="edcTerm" name="term" class="form-select coe-control">
                                <option value="">Select Term</option>
                                @foreach ($activeTerms as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Academic Session + Status --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcSession">Academic Session<span class="coe-req">*</span></label>
                            <input type="text" id="edcSession" name="academic_session" class="form-control coe-control"
                                   placeholder="eg. 2026" maxlength="10" autocomplete="off">
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcStatus">Status<span class="coe-req">*</span></label>
                            <select id="edcStatus" name="status" class="form-select coe-control">
                                @foreach ($statuses as $s)<option value="{{ $s }}" @if($s==='Draft') selected @endif>{{ $s }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Start Date + End Date --}}
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcStartDate">Start Date<span class="coe-req">*</span></label>
                            <input type="date" id="edcStartDate" name="start_date" class="form-control coe-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcEndDate">End Date<span class="coe-req">*</span></label>
                            <input type="date" id="edcEndDate" name="end_date" class="form-control coe-control">
                        </div>
                        {{-- Filled by edcApplyCourseRange(): the drive must sit inside the course dates. --}}
                        <div class="col-12 mt-1"><small id="edcCourseRange" class="text-muted"></small></div>
                    </div>

                    {{-- Subject / Faculty mapping --}}
                    <div class="mb-2 fw-semibold" style="font-size:14px">Subject and Faculty Mapping</div>
                    <table class="edc-map-table" id="edcMapTable">
                        <thead>
                            <tr>
                                <th style="width:38%">Subject</th>
                                <th style="width:42%">Faculty</th>
                                <th style="width:20%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="edcMapBody">
                            {{-- Rows added by JS --}}
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="edcFormSubmit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Create Re-exam modal (ReexamController) ─────────────────────────────── --}}
<div class="modal fade" id="edcReexamModal" tabindex="-1" aria-labelledby="edcReexamTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="edcReexamForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="edcReexamTitle">Create Re-exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <p class="edc-rx-intro" id="edcReexamIntro"></p>
                    <div class="edc-rx-bar">
                        <nav class="edc-rx-tabs" aria-label="Reason">
                            <button type="button" class="edc-rx-tab is-active" data-rx-filter="all">All (<span data-rx-count="all">0</span>)</button>
                            <button type="button" class="edc-rx-tab" data-rx-filter="1">Failed · <span data-rx-ots="1">0</span> OTs (<span data-rx-count="1">0</span>)</button>
                            <button type="button" class="edc-rx-tab" data-rx-filter="2">Absent · <span data-rx-ots="2">0</span> OTs (<span data-rx-count="2">0</span>)</button>
                        </nav>
                        <span class="edc-rx-note">Counts are OT-subjects; <span id="edcReexamTicked">0</span> ticked in total.</span>
                    </div>
                    <div class="edc-rx-wrap">
                        <table class="edc-rx-table">
                            <thead>
                                <tr>
                                    <th class="edc-rx-check"><input type="checkbox" class="form-check-input" id="edcReexamAll" aria-label="Tick all shown"></th>
                                    <th>Officer Trainee</th>
                                    <th>Roll No.</th>
                                    <th>Subject</th>
                                    <th>Reason</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody id="edcReexamBody"></tbody>
                        </table>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="coe-form-label" for="edcReexamStart">Re-exam start<span class="coe-req">*</span></label>
                            <input type="date" id="edcReexamStart" class="form-control coe-control">
                        </div>
                        <div class="col-md-4">
                            <label class="coe-form-label" for="edcReexamEnd">Re-exam end<span class="coe-req">*</span></label>
                            <input type="date" id="edcReexamEnd" class="form-control coe-control">
                        </div>
                        <div class="col-md-4">
                            <label class="coe-form-label" for="edcReexamName">Name</label>
                            <input type="text" id="edcReexamName" class="form-control coe-control" maxlength="150" autocomplete="off">
                            <small class="text-muted">Optional</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary" id="edcReexamSubmit">Create Re-exam (0)</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Re-exam Result modal (ReexamController::result) ─────────────────────── --}}
<div class="modal fade" id="edcRxResultModal" tabindex="-1" aria-labelledby="edcRxResultTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header coe-modal-header--rule">
                <h5 class="modal-title" id="edcRxResultTitle">Re-exam Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body">
                <p class="edc-rx-intro" id="edcRxResultIntro"></p>
                <div class="edc-rx-bar">
                    <nav class="edc-rx-tabs" aria-label="Result">
                        <button type="button" class="edc-rx-tab is-active" data-rxr-filter="all">All (<span data-rxr-count="all">0</span>)</button>
                        <button type="button" class="edc-rx-tab" data-rxr-filter="Pass">Pass (<span data-rxr-count="Pass">0</span>)</button>
                        <button type="button" class="edc-rx-tab" data-rxr-filter="Fail">Fail (<span data-rxr-count="Fail">0</span>)</button>
                        <button type="button" class="edc-rx-tab" data-rxr-filter="Pending">Pending (<span data-rxr-count="Pending">0</span>)</button>
                    </nav>
                    <span class="edc-rx-note">Counts are OTs. An OT passes when every re-exam subject is passed.</span>
                </div>
                <div class="edc-rx-wrap">
                    <table class="edc-rx-table">
                        <thead>
                            <tr>
                                <th>Officer Trainee</th>
                                <th>Roll No.</th>
                                <th>Subject</th>
                                <th>Before</th>
                                <th>Re-exam Score</th>
                                <th>Subject Result</th>
                                <th>OT Result</th>
                            </tr>
                        </thead>
                        <tbody id="edcRxResultBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer coe-modal-footer">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'edcDeleteModal', 'confirmId' => 'edcDeleteConfirm'])
@include('admin.coe.partials.freeze_modal', ['phoneMasked' => '+91 ******3274'])

{{-- Column Visibility --}}
<div class="modal fade" id="edcColumnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="edcColumnToggleGrid"></div>
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

    var SUBJECTS = @json($subjects);
    var FACULTY  = @json($faculty);
    var STATUSES = @json($statuses);
    // Course name => [start, end] (Y-m-d): the drive's Start / End Date must sit inside it.
    var COURSE_DATES = @json($courseDates);

    /* COL indexes — keep in step with <thead> AND with the DataTable class's getColumns() */
    var COL = { sno:0, name:1, type:2, term:3, course:4, service:5, session:6, faculty:7, sub:8, comp:9, start:10, end:11, status:12, action:13 };

    var $table = $('#edcTable');

    /* ── DataTable (server-side; rows/status/actions are rendered by ExaminationDriveDataTable) ── */
    var dt = CoeGrid.init({
        table: '#edcTable',
        title: @json($isArchived ? 'Examination Drive Configuration – Archived' : 'Examination Drive Configuration'),
        snoCol: COL.sno,
        order: [[COL.name, 'asc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        serverSide: true,
        processing: true,
        // The Status filter goes to the server (status_filter), like the scope tab.
        ajax: { url: @json(route('admin.coe.examination-drive.index')),
                data: function (d) { d.scope = @json($scope); d.status_filter = $('#edcFilterStatus').val() || ''; } },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex' },
            { data: 'name', name: 'name' },
            { data: 'exam_type', name: 'exam_type' },
            { data: 'term', name: 'term' },
            { data: 'course', name: 'course' },
            { data: 'service', name: 'service' },
            { data: 'academic_session', name: 'academic_session' },
            { data: 'faculty', name: 'faculty' },
            { data: 'sub_coords', name: 'sub_coords' },
            { data: 'components', name: 'components' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'status', name: 'status' },
            { data: 'actions', name: 'actions' }
        ],
        filters: { '#edcFilterCourse': COL.course, '#edcFilterTerm': COL.term, '#edcFilterType': COL.type },
        reset: '#edcResetFilters',
        searchToggle: '#edcSearchToggle',
        searchSlot: '#edcDtSearch',
        colvisGrid: '#edcColumnToggleGrid',
        colvisKey: 'coeExaminationDrive:hiddenColumns:v2',
        empty: { title: 'No Examination Drives', zeroTitle: 'No Records Found', text: 'Create the first examination drive to get started.' }
    });

    /* ── Export links follow the grid: tab, Course / Term / Type filters, search, visible columns ── */
    var EXPORT_KEYS = ['sno', 'name', 'exam_type', 'term', 'course', 'service', 'session', 'faculty', 'sub_coords', 'components', 'start', 'end', 'status', ''];
    function edcSyncExportLinks() {
        var keys = [], all = EXPORT_KEYS.filter(Boolean).length;
        dt.columns().every(function () { var k = EXPORT_KEYS[this.index()]; if (k && this.visible()) { keys.push(k); } });
        var set = { scope: @json($scope), course_filter: $('#edcFilterCourse').val(), term_filter: $('#edcFilterTerm').val(),
                    type_filter: $('#edcFilterType').val(), status_filter: $('#edcFilterStatus').val(), q: dt.search() };
        $('.edc-export-link').each(function () {
            var parts = this.href.split('?'), params = new URLSearchParams();
            Object.keys(set).forEach(function (k) { if (set[k]) { params.set(k, set[k]); } });
            if (keys.length && keys.length !== all) { params.set('cols', keys.join(',')); }
            this.href = parts[0] + '?' + params.toString();
        });
    }
    dt.on('draw column-visibility.dt', edcSyncExportLinks);
    $('#edcFilterStatus').on('change', function () { dt.ajax.reload(); });
    $('#edcResetFilters').on('click', function () { $('#edcFilterStatus').val(''); dt.ajax.reload(); });
    edcSyncExportLinks();

    /* ── Delete ───────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#edcTable',
        modal: '#edcDeleteModal',
        confirm: '#edcDeleteConfirm',
        onConfirm: function ($row) {
            var id = $row.attr('data-coe-id');
            $.ajax({
                url: '{{ url("admin/coe/examination-drive") }}/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).done(function () {
                dt.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Deleted', text: 'Drive deleted.' });
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete the drive.';
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            });
        }
    });

    /* ── Freeze (status change is not yet persisted — see above). ── */
    CoeGrid.freeze({
        table: '#edcTable',
        trigger: '.coe-act--freeze',
        onConfirm: function () {
            dt.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Frozen', text: 'Drive sent to COE for approval.' });
        }
    });

    /* ── Subject/Faculty mapping rows ────────────────────────────────── */
    function subjectOptions(selected) {
        return SUBJECTS.map(function (s) {
            return '<option value="' + s + '"' + (s === selected ? ' selected' : '') + '>' + s + '</option>';
        }).join('');
    }
    function facultyOptions(selected) {
        // FACULTY = Internal faculty only; a drive mapped before that rule keeps its
        // current faculty as an option so an edit does not drop it.
        var list = selected && FACULTY.indexOf(selected) === -1 ? [selected].concat(FACULTY) : FACULTY;
        return list.map(function (f) {
            return '<option value="' + f + '"' + (f === selected ? ' selected' : '') + '>' + f + '</option>';
        }).join('');
    }

    function buildMapRow(subject, faculty) {
        return '<tr class="edc-map-row">' +
            '<td><select class="form-select form-select-sm edc-map-subject"><option value="">Subject</option>' + subjectOptions(subject || '') + '</select></td>' +
            '<td><select class="form-select form-select-sm edc-map-faculty"><option value="">Faculty</option>' + facultyOptions(faculty || '') + '</select></td>' +
            '<td>' +
            '<button type="button" class="edc-map-row-del me-1" aria-label="Remove row"><i class="bi bi-dash" aria-hidden="true"></i></button>' +
            '<button type="button" class="edc-map-row-add" aria-label="Add row"><i class="bi bi-plus" aria-hidden="true"></i></button>' +
            '</td></tr>';
    }

    var $mapBody = $('#edcMapBody');

    function initMapRowSelect2($row) {
        $row.find('.edc-map-subject, .edc-map-faculty').each(function () {
            $(this).select2({
                width: '100%',
                dropdownParent: $modal,
                placeholder: $(this).hasClass('edc-map-subject') ? 'Subject' : 'Faculty'
            });
        });
    }

    function appendMapRow(subject, faculty) {
        var $row = $(buildMapRow(subject, faculty));
        $mapBody.append($row);
        initMapRowSelect2($row);
        return $row;
    }

    function ensureOneRow() {
        if ($mapBody.find('.edc-map-row').length === 0) {
            appendMapRow('', '');
        }
    }

    $mapBody.on('click', '.edc-map-row-del', function () {
        var $row = $(this).closest('.edc-map-row');
        $row.find('.edc-map-subject, .edc-map-faculty').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) { $(this).select2('destroy'); }
        });
        $row.remove();
        ensureOneRow();
    });
    $mapBody.on('click', '.edc-map-row-add', function () {
        var $row = appendMapRow('', '');
        $(this).closest('.edc-map-row').after($row);
    });

    /* ── Form modal (Create / Edit) ──────────────────────────────────── */
    /* Dropdowns attach to the modal, not the scrollable .modal-body: inside the
       body, a select near the bottom (the mapping rows) opened its list past the
       scroll edge and got clipped. Still inside the modal, so focus trap holds. */
    var $modal      = $('#edcFormModal');
    var $editingRow = null;

    var $course  = $('#edcCourse');
    var $type    = $('#edcExamType');
    var $term    = $('#edcTerm');
    var $status  = $('#edcStatus');
    [$course, $type, $term, $status].forEach(function ($s) {
        $s.select2({ width: '100%', dropdownParent: $modal });
    });

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    function clearErrors() {
        $modal.find('.coe-field-error').remove();
        $modal.find('.is-invalid').removeClass('is-invalid');
    }

    function openForm($row) {
        $editingRow = $row || null;
        var isEdit  = !!$row;
        $('#edcFormModalLabel').text(isEdit ? 'Edit Examination Drive' : 'Create Examination Drive');
        $('#edcFormSubmit').text('Save');
        $('#edcDriveNameRow').removeClass('d-none');
        // Options added for a previous Edit are not offered on the next Create / Edit.
        $modal.find('option.edc-kept').remove();
        clearErrors();
        $mapBody.empty();

        // A drive keeps its own course / type / term even if it is no longer in the
        // (active / tab-scoped) list, so Edit still shows and saves it.
        function keepOption($sel, value) {
            if (value && !$sel.find('option').filter(function () { return this.value === value; }).length) {
                $sel.append($('<option class="edc-kept">').val(value).text(value));
            }
            $sel.val(value || '').trigger('change.select2');
        }

        if (isEdit) {
            $('#edcDriveName').val($row.attr('data-edc-name') || '');
            keepOption($type, $row.attr('data-edc-type'));
            keepOption($term, $row.attr('data-edc-term'));
            keepOption($course, $row.attr('data-edc-course'));
            $('#edcSession').val($row.attr('data-edc-session') || '');
            $status.val($row.attr('data-edc-status') || 'Draft').trigger('change.select2');
            // Convert dd-mm-yyyy → yyyy-mm-dd for date input
            function toIso(d) { if (!d) return ''; var p = d.split('-'); return p.length === 3 ? p[2]+'-'+p[1]+'-'+p[0] : d; }
            $('#edcStartDate').val(toIso($row.attr('data-edc-start')));
            $('#edcEndDate').val(toIso($row.attr('data-edc-end')));
            $.get('{{ url("admin/coe/examination-drive") }}/' + $row.attr('data-coe-id') + '/mappings')
                .done(function (rows) {
                    $mapBody.empty();
                    (rows.length ? rows : [{ subject: '', faculty: '' }]).forEach(function (r) {
                        appendMapRow(r.subject, r.faculty);
                    });
                });
        } else {
            $('#edcDriveName').val('');
            $course.val('').trigger('change.select2');
            $type.val('').trigger('change.select2');
            $term.val('').trigger('change.select2');
            $('#edcSession').val('');
            $status.val('Draft').trigger('change.select2');
            $('#edcStartDate, #edcEndDate').val('');
            appendMapRow('', '');
        }

        edcApplyCourseRange();
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    /* Start / End Date calendars only offer the selected course's dates. */
    function edcCourseRange() { return COURSE_DATES[$course.val()] || [null, null]; }
    function edcShow(d) { if (!d) { return '…'; } var p = d.split('-'); return p[2] + '-' + p[1] + '-' + p[0]; }
    function edcApplyCourseRange() {
        var r = edcCourseRange();
        $('#edcStartDate, #edcEndDate').attr('min', r[0] || null).attr('max', r[1] || null);
        $('#edcCourseRange').text($course.val() && (r[0] || r[1]) ? 'Course runs ' + edcShow(r[0]) + ' to ' + edcShow(r[1]) + '. The drive dates must be within it.' : '');
    }
    // Choosing a course fills Start / End Date with the course's dates (they can then be
    // narrowed). On Edit, only when the drive's dates fall outside the newly chosen course.
    $course.on('change', function () {
        edcApplyCourseRange();
        var r = edcCourseRange(), sd = $('#edcStartDate').val(), ed = $('#edcEndDate').val();
        var outside = !sd || !ed || (r[0] && sd < r[0]) || (r[1] && ed > r[1]);
        if (!$editingRow || outside) {
            if (r[0]) { $('#edcStartDate').val(r[0]); }
            if (r[1]) { $('#edcEndDate').val(r[1]); }
        }
    });

    $('#edcCreateBtn').on('click', function () { openForm(null); });
    $table.on('click', '.coe-act--edit', function () { openForm($(this).closest('tr')); });
    $modal.on('hidden.bs.modal', function () { $editingRow = null; clearErrors(); });

    $('#edcForm').on('submit', function (e) {
        e.preventDefault();
        var ok = true;
        var isEdit = !!$editingRow;
        ok = fieldError($('#edcDriveName'), $.trim($('#edcDriveName').val()) ? '' : 'Enter the drive name.') && ok;
        ok = fieldError($course, $course.val() ? '' : 'Select a course.') && ok;
        ok = fieldError($term,   $term.val()   ? '' : 'Select a term.') && ok;
        ok = fieldError($('#edcSession'), $.trim($('#edcSession').val()) ? '' : 'Enter the academic session.') && ok;
        ok = fieldError($('#edcStartDate'), $('#edcStartDate').val() ? '' : 'Select the start date.') && ok;
        ok = fieldError($('#edcEndDate'),   $('#edcEndDate').val()   ? '' : 'Select the end date.') && ok;
        // Same limit the server applies: the drive sits inside the course dates.
        var cr = edcCourseRange(), sd = $('#edcStartDate').val(), ed = $('#edcEndDate').val();
        if (sd && cr[0] && sd < cr[0]) { ok = fieldError($('#edcStartDate'), 'Must be on or after the course start (' + edcShow(cr[0]) + ').') && ok; }
        if (ed && cr[1] && ed > cr[1]) { ok = fieldError($('#edcEndDate'), 'Must be on or before the course end (' + edcShow(cr[1]) + ').') && ok; }
        if (!ok) { return; }

        var mappings = [];
        $mapBody.find('.edc-map-row').each(function () {
            mappings.push({
                subject: $(this).find('.edc-map-subject').val() || '',
                faculty: $(this).find('.edc-map-faculty').val() || ''
            });
        });

        // At least one complete Subject + Faculty row; no row with only one of the two.
        var half = mappings.filter(function (m) { return (m.subject === '') !== (m.faculty === ''); }).length;
        var full = mappings.filter(function (m) { return m.subject !== '' && m.faculty !== ''; }).length;
        if (half || !full) {
            Swal.fire({ icon: 'warning', title: 'Subject and Faculty Mapping',
                text: half ? 'Select both the Subject and the Faculty in every mapping row.'
                           : 'Add at least one Subject and Faculty in Subject and Faculty Mapping.' });
            return;
        }

        var payload = {
            course: $course.val(),
            exam_type: $type.val() || '',
            term: $term.val(),
            academic_session: $.trim($('#edcSession').val()),
            status: $status.val(),
            start_date: $('#edcStartDate').val(),
            end_date: $('#edcEndDate').val(),
            mappings: mappings
        };
        payload.drive_name = $.trim($('#edcDriveName').val());

        var url = isEdit
            ? '{{ url("admin/coe/examination-drive") }}/' + $editingRow.attr('data-coe-id')
            : '{{ route("admin.coe.examination-drive.store") }}';

        var $submitBtn = $('#edcFormSubmit');
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: url,
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: payload
        }).done(function (res) {
            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            dt.ajax.reload(null, false);
            // The message says how many faculty were notified.
            Swal.fire({ icon: 'success', title: isEdit ? 'Updated' : 'Created', text: (res && res.message) || (isEdit ? 'Drive updated.' : 'Examination drive created.') });
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || ('Could not ' + (isEdit ? 'update' : 'create') + ' the examination drive.');
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        }).always(function () {
            $submitBtn.prop('disabled', false);
        });
    });

    /* ── Re-exam (ReexamController) ──────────────────────────────────────
       The grid renders the Re-exam button hidden on main drives whose exams are over;
       after each draw one request asks which of them have final results with an OT
       failed or absent, and only those show it. So the list itself reads no marks. */
    var RX_READY_URL = @json(route('admin.coe.examination-drive.reexam.ready'));
    var RX_BASE_URL  = @json(url('admin/coe/examination-drive'));
    var $rx = $('#edcReexamModal'), $rxBody = $('#edcReexamBody');
    var rxRows = [], rxTicked = {}, rxFilter = 'all', rxDriveId = null;

    dt.on('draw', function () {
        var ids = [];
        $table.find('tbody .coe-act--reexam').each(function () { ids.push($(this).closest('tr').attr('data-coe-id')); });
        if (!ids.length) { return; }
        $.get(RX_READY_URL, { ids: ids }).done(function (res) {
            (res.ready || []).forEach(function (id) {
                $table.find('tbody tr[data-coe-id="' + id + '"] .coe-act--reexam').removeClass('d-none');
            });
        });
    });

    function rxEsc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function rxShown() { return rxRows.filter(function (r) { return rxFilter === 'all' || String(r.reason) === rxFilter; }); }

    function rxCounts() {
        var ots = { 1: {}, 2: {} }, count = { all: rxRows.length, 1: 0, 2: 0 }, ticked = 0;
        rxRows.forEach(function (r) {
            count[r.reason]++;
            ots[r.reason][r.student_pk] = true;
            if (rxTicked[r.key]) { ticked++; }
        });
        $rx.find('[data-rx-count]').each(function () { $(this).text(count[$(this).attr('data-rx-count')]); });
        $rx.find('[data-rx-ots]').each(function () { $(this).text(Object.keys(ots[$(this).attr('data-rx-ots')]).length); });
        $('#edcReexamTicked').text(ticked);
        $('#edcReexamSubmit').text('Create Re-exam (' + ticked + ')');
        var shown = rxShown(), on = shown.filter(function (r) { return rxTicked[r.key]; }).length;
        $('#edcReexamAll').prop('checked', shown.length > 0 && on === shown.length)
            .prop('indeterminate', on > 0 && on < shown.length);
    }

    function rxRender() {
        var shown = rxShown();
        $rxBody.html(shown.length ? shown.map(function (r) {
            return '<tr>' +
                '<td class="edc-rx-check"><input type="checkbox" class="form-check-input edc-rx-tick" data-key="' + rxEsc(r.key) + '"' + (rxTicked[r.key] ? ' checked' : '') + ' aria-label="Tick ' + rxEsc(r.name) + '"></td>' +
                '<td>' + rxEsc(r.name) + (r.ot_code ? '<small>' + rxEsc(r.ot_code) + '</small>' : '') + '</td>' +
                '<td>' + (r.roll ? rxEsc(r.roll) : '-') + '</td>' +
                '<td>' + rxEsc(r.subject) + '</td>' +
                '<td><span class="edc-rx-reason ' + (String(r.reason) === '2' ? 'edc-rx-reason--absent' : 'edc-rx-reason--failed') + '">' + rxEsc(r.reason_label) + '</span></td>' +
                '<td>' + Number(r.score).toFixed(1) + '%</td>' +
                '</tr>';
        }).join('') : '<tr><td colspan="6" class="edc-rx-empty">No OTs here.</td></tr>');
        rxCounts();
    }

    $table.on('click', '.coe-act--reexam', function () {
        var $btn = $(this).addClass('is-busy');
        rxDriveId = $btn.closest('tr').attr('data-coe-id');
        $.get(RX_BASE_URL + '/' + rxDriveId + '/reexam').done(function (res) {
            if (!res.ready || !res.rows || !res.rows.length) {
                Swal.fire({ icon: 'info', title: 'Re-exam', text: res.message || 'No OT needs a re-exam.' });
                return;
            }
            rxRows = res.rows;
            rxTicked = {};
            rxRows.forEach(function (r) { rxTicked[r.key] = true; });
            rxFilter = 'all';
            $rx.find('[data-rx-filter]').removeClass('is-active').filter('[data-rx-filter="all"]').addClass('is-active');
            $('#edcReexamTitle').text('Create Re-exam ' + res.next_no);
            $('#edcReexamIntro').text(res.drive + ' - Re-exam. OTs who failed or were absent in a subject, from the latest results. '
                + 'Roll numbers stay the same; question papers, seating and marks are new.');
            $('#edcReexamName').val('').attr('placeholder', res.default_name || '');
            $('#edcReexamStart, #edcReexamEnd').val('');
            $rx.find('.coe-field-error').remove();
            $rx.find('.is-invalid').removeClass('is-invalid');
            rxRender();
            bootstrap.Modal.getOrCreateInstance($rx[0]).show();
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not load the re-exam list.' });
        }).always(function () { $btn.removeClass('is-busy'); });
    });

    $rx.on('click', '[data-rx-filter]', function () {
        rxFilter = $(this).attr('data-rx-filter');
        $rx.find('[data-rx-filter]').removeClass('is-active');
        $(this).addClass('is-active');
        rxRender();
    });
    $rxBody.on('change', '.edc-rx-tick', function () {
        rxTicked[$(this).attr('data-key')] = this.checked;
        rxCounts();
    });
    $('#edcReexamAll').on('change', function () {
        var on = this.checked;
        rxShown().forEach(function (r) { rxTicked[r.key] = on; });
        rxRender();
    });

    $('#edcReexamForm').on('submit', function (e) {
        e.preventDefault();
        var start = $('#edcReexamStart').val(), end = $('#edcReexamEnd').val();
        var ok = fieldError($('#edcReexamStart'), start ? '' : 'Select the re-exam start date.');
        ok = fieldError($('#edcReexamEnd'), !end ? 'Select the re-exam end date.'
            : (start && end < start ? 'The re-exam cannot end before it starts.' : '')) && ok;
        var keys = Object.keys(rxTicked).filter(function (k) { return rxTicked[k]; });
        if (!keys.length) {
            Swal.fire({ icon: 'warning', title: 'Re-exam', text: 'Tick at least one OT for the re-exam.' });
            return;
        }
        if (!ok) { return; }

        var $submit = $('#edcReexamSubmit').prop('disabled', true);
        // JSON body: a long list of ticks would pass max_input_vars as form fields.
        $.ajax({
            url: RX_BASE_URL + '/' + rxDriveId + '/reexam',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            data: JSON.stringify({ keys: keys, start_date: start, end_date: end, name: $.trim($('#edcReexamName').val()) })
        }).done(function (res) {
            bootstrap.Modal.getOrCreateInstance($rx[0]).hide();
            dt.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Re-exam created', text: (res && res.message) || 'Re-exam created.' });
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not create the re-exam.' });
        }).always(function () { $submit.prop('disabled', false); });
    });

    /* ── Re-exam Result (ReexamController::result): Pass / Fail on the re-exam's own marks ── */
    var $rxr = $('#edcRxResultModal'), rxrOts = [], rxrFilter = 'all';

    function rxrBadge(result) {
        return '<span class="edc-rx-result edc-rx-result--' + String(result).toLowerCase() + '">' + rxEsc(result) + '</span>';
    }

    function rxrRender() {
        var shown = rxrOts.filter(function (o) { return rxrFilter === 'all' || o.result === rxrFilter; });
        var html = '';
        shown.forEach(function (o) {
            o.rows.forEach(function (r, i) {
                html += '<tr>' +
                    (i === 0 ? '<td rowspan="' + o.rows.length + '">' + rxEsc(o.name) + (o.ot_code ? '<small>' + rxEsc(o.ot_code) + '</small>' : '') + '</td>' +
                        '<td rowspan="' + o.rows.length + '">' + (o.roll ? rxEsc(o.roll) : '-') + '</td>' : '') +
                    '<td>' + rxEsc(r.subject) + '</td>' +
                    '<td><span class="edc-rx-reason ' + (r.previous === 'Absent' ? 'edc-rx-reason--absent' : 'edc-rx-reason--failed') + '">' + rxEsc(r.previous) + '</span>' +
                        '<small>' + Number(r.previous_score).toFixed(1) + '%</small></td>' +
                    '<td>' + (r.score === null ? '-' : Number(r.score).toFixed(1) + '%') + (r.absent ? '<small>Absent</small>' : '') + '</td>' +
                    '<td>' + rxrBadge(r.result) + '</td>' +
                    (i === 0 ? '<td rowspan="' + o.rows.length + '">' + rxrBadge(o.result) + '</td>' : '') +
                    '</tr>';
            });
        });
        $('#edcRxResultBody').html(html || '<tr><td colspan="7" class="edc-rx-empty">No OTs here.</td></tr>');
    }

    $table.on('click', '.coe-act--rxresult', function () {
        var $btn = $(this).addClass('is-busy');
        $.get(RX_BASE_URL + '/' + $btn.closest('tr').attr('data-coe-id') + '/reexam-result').done(function (res) {
            var bySt = {};
            (res.rows || []).forEach(function (r) { (bySt[r.student_pk] = bySt[r.student_pk] || []).push(r); });
            rxrOts = (res.ots || []).map(function (o) { return $.extend({}, o, { rows: bySt[o.student_pk] || [] }); });
            rxrFilter = 'all';
            $rxr.find('[data-rxr-filter]').removeClass('is-active').filter('[data-rxr-filter="all"]').addClass('is-active');
            $rxr.find('[data-rxr-count="all"]').text(rxrOts.length);
            ['Pass', 'Fail', 'Pending'].forEach(function (k) { $rxr.find('[data-rxr-count="' + k + '"]').text((res.counts && res.counts[k]) || 0); });
            $('#edcRxResultTitle').text('Re-exam ' + (res.reexam_no || '') + ' Result');
            $('#edcRxResultIntro').text(res.drive + (res.main ? ' (re-exam of ' + res.main + ')' : '') + '. The re-exam marks replace the old ones for these subjects; '
                + 'no moderation. The re-exam marksheet shows only Pass / Fail.' + (res.message ? ' ' + res.message : ''));
            rxrRender();
            bootstrap.Modal.getOrCreateInstance($rxr[0]).show();
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not load the re-exam result.' });
        }).always(function () { $btn.removeClass('is-busy'); });
    });

    $rxr.on('click', '[data-rxr-filter]', function () {
        rxrFilter = $(this).attr('data-rxr-filter');
        $rxr.find('[data-rxr-filter]').removeClass('is-active');
        $(this).addClass('is-active');
        rxrRender();
    });
});
</script>
@endpush
