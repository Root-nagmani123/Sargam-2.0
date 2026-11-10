@extends('admin.layouts.master')

@section('title', 'Map Faculty to Examination Drive')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Toolbar */
.fedc-toolbar       { display:flex;justify-content:flex-end;gap:12px;margin-bottom:18px; }
.fedc-tool-btn      { display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:0;border-radius:8px;
                      background:#fff;color:var(--ds-primary);font-size:15px;font-weight:500;box-shadow:0 1px 2px rgba(16,24,40,.06); }
.fedc-tool-btn:hover{ background:var(--ds-surface-2); }
/* Info bar */
.fedc-info-bar      { background:#fff;border-radius:12px;padding:24px 30px;margin-bottom:22px;
                      display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:20px; }
.fedc-info-item     { display:flex;flex-direction:column;gap:6px;min-width:0; }
.fedc-info-label    { font-size:13px;color:var(--ds-ink-muted);text-transform:uppercase;letter-spacing:.3px; }
.fedc-info-value    { font-size:17px;font-weight:600;color:var(--ds-ink);overflow-wrap:anywhere; }
@media (max-width: 991.98px) { .fedc-info-bar { grid-template-columns:repeat(2,minmax(0,1fr)); } }
/* Component mapping table */
.fedc-map-table     { width:100%;border-collapse:separate;border-spacing:0;min-width:1380px;table-layout:fixed; }
.fedc-map-table .fedc-subject { font-size:14px;font-weight:600;color:var(--ds-ink);overflow-wrap:anywhere; }
.fedc-map-table thead th { padding:10px 8px;background:var(--ds-surface-2);font-size:14px;font-weight:500;line-height:1.2;
                      color:var(--ds-ink);white-space:normal;vertical-align:middle; }
.fedc-map-table thead th:first-child { padding-left:14px; }
.fedc-map-table thead th:first-child { border-radius:10px 0 0 0; }
.fedc-map-table thead th:last-child  { border-radius:0 10px 0 0; }
.fedc-map-table tbody td { padding:12px 5px;border-bottom:1px solid var(--ds-line);vertical-align:middle; }
.fedc-map-table tbody td:first-child { padding-left:12px; }
.fedc-map-table tbody td:last-child  { padding-right:12px; }
.fedc-map-table thead th:last-child  { padding-right:12px; }
.fedc-map-table .fedc-exam-range { padding:6px 8px 6px 10px;background:#fff; }
.fedc-map-table .form-control,
.fedc-map-table .form-select { height:40px;font-size:14px;border-color:var(--ds-line);border-radius:6px;color:var(--ds-ink); }
.fedc-map-table .form-select  { padding-left:12px;padding-right:30px;background-position:right 10px center;text-overflow:ellipsis; }
.fedc-map-table input[type=number] { padding:6px 8px 6px 12px; }
/* Sortable column headers */
.fedc-map-table th[data-sort] { cursor:pointer;user-select:none; }
.fedc-map-table th[data-sort] .bi { margin-left:4px;font-size:12px;color:var(--ds-ink-muted); }
/* Faculty picker: looks like a select; disabled for system-sourced rows */
.fedc-fac-open-btn  { display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;min-height:40px;
                      padding:6px 12px;border:1px solid var(--ds-line);border-radius:6px;background:#fff;
                      color:var(--ds-ink);font-size:13px;line-height:1.25;text-align:left; }
.fedc-fac-open-btn:hover:not(:disabled) { border-color:var(--ds-primary); }
.fedc-fac-open-btn::after { content:'';flex-shrink:0;width:10px;height:10px;margin-left:auto;
                      background:no-repeat center/10px url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); }
.fedc-fac-open-btn:disabled { background:var(--ds-surface-2);color:var(--ds-ink);cursor:not-allowed; }
.fedc-fac-open-btn.is-invalid { border-color:#dc3545; }
.fedc-fac-text      { display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.fedc-fac-placeholder { color:var(--ds-ink); }
/* Row buttons: red minus on every row, blue plus only on the last */
.fedc-row-del, .fedc-row-add { width:36px;height:36px;border:none;border-radius:6px;color:#fff;font-size:18px;
                 display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0; }
.fedc-row-del  { background:#e5141a; }
.fedc-row-add  { background:#004a93; }
.fedc-row-del:hover{ background:#b80f14; }
.fedc-row-add:hover{ background:#003670; }
.fedc-map-row:not(:last-child) .fedc-row-add-btn { visibility:hidden; }
.fedc-actions  { display:flex;justify-content:flex-end;gap:10px; }
/* Total weightage bar */
.fedc-total-row td  { background:#e5ecfb;padding:14px 8px;font-size:15px;color:var(--ds-ink); }
.fedc-total-row td:first-child { border-radius:0 0 0 10px; }
.fedc-total-row td:last-child  { border-radius:0 0 10px 0; }
.fedc-total-row .is-over { color:#e5141a;font-weight:600; }
.fedc-map-table .fedc-pass-marks[readonly] { background:var(--ds-surface-2); }
.fedc-footer        { display:flex;justify-content:flex-end;gap:16px;margin-top:28px; }
.fedc-footer .btn   { min-width:116px;height:48px;font-size:17px; }
/* Assign Faculty modal tabs */
.fedc-af-tabs  { display:flex;border:1px solid var(--ds-line);border-radius:8px;overflow:hidden;margin-bottom:18px; }
.fedc-af-tab   { flex:1;padding:9px 0;text-align:center;font-size:13px;font-weight:600;cursor:pointer;
                 color:var(--ds-ink-muted);background:var(--ds-surface-2); }
.fedc-af-tab.is-active { background:var(--ds-primary);color:#fff; }
/* Single Faculty list */
.fedc-fac-search input { width:100%;padding:8px 12px;border:1px solid var(--ds-line);border-radius:6px;
                          font-size:13px;margin-bottom:10px; }
.fedc-fac-list  { list-style:none;margin:0;padding:0; }
.fedc-fac-item  { padding:9px 12px;border-bottom:1px solid var(--ds-line);font-size:13px;cursor:pointer; }
.fedc-fac-item:last-child { border-bottom:none; }
.fedc-fac-item:hover { background:var(--ds-surface-2); }
/* Multi Faculty table */
.fedc-multi-table    { width:100%;border-collapse:collapse; }
.fedc-multi-table th { padding:7px 10px;background:var(--ds-surface-2);font-size:12px;font-weight:700;
                       color:var(--ds-ink-muted);border-bottom:1px solid var(--ds-line); }
.fedc-multi-table td { padding:7px 8px;border-bottom:1px solid var(--ds-line);font-size:13px;vertical-align:middle; }
/* Select Roll Number sub-modal */
.fedc-rn-ranges  { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px; }
.fedc-rn-range   { background:var(--ds-surface-2);border:1px solid var(--ds-line);border-radius:16px;
                   padding:4px 12px;font-size:12px;font-weight:600;cursor:pointer;color:var(--ds-ink-muted); }
.fedc-rn-range:hover,.fedc-rn-range.is-active { background:var(--ds-primary);color:#fff;border-color:var(--ds-primary); }
.fedc-rn-list    { max-height:260px;overflow-y:auto;border:1px solid var(--ds-line);border-radius:6px; }
.fedc-rn-item    { display:flex;align-items:center;gap:10px;padding:8px 12px;font-size:13px;
                   border-bottom:1px solid var(--ds-line); }
.fedc-rn-item:last-child { border-bottom:none; }
.fedc-multi-uniq { width:100%;text-align:left;font-size:12px;padding:4px 8px;border:1px solid var(--ds-line);border-radius:6px;
                   background:#fff;color:var(--ds-ink); }
.fedc-multi-uniq.has-codes { border-color:var(--ds-primary);color:var(--ds-primary);font-weight:600; }
.fedc-rn-item input[type=checkbox] { width:16px;height:16px;accent-color:var(--ds-primary);flex-shrink:0; }
</style>
@endpush

@section('setup_content')
@php
    // Passing marks come from Service Passing Marks; total max marks is capped by
    // Subject Max Marks on a single-subject drive (ExaminationDriveController::marksLimits).
    $passFixed = $limits['passing_marks'];
    $missingMaster = $passFixed === null || $limits['subject_max'] === null;
    $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
@endphp
<div class="container-fluid coe-page">

    <x-breadcrum title="Map Faculty to Examination Drive" :showBack="true"
                 :backUrl="route('admin.coe.faculty-examination-drive.index')"
                 :items="['Setup', 'COE', 'Examination Management', 'Map Faculty to Examination Drive']" />

    <div class="fedc-toolbar">
        <button type="button" class="fedc-tool-btn" id="fedcDownloadBtn">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" class="fedc-tool-btn" id="fedcPrintBtn">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    {{-- Drive info bar --}}
    <div class="fedc-info-bar">
        <div class="fedc-info-item">
            <span class="fedc-info-label">Drive Name</span>
            <span class="fedc-info-value">{{ $drive['name'] }}</span>
        </div>
        <div class="fedc-info-item">
            <span class="fedc-info-label">Term Name</span>
            <span class="fedc-info-value">{{ $drive['term'] }}</span>
        </div>
        <div class="fedc-info-item">
            <span class="fedc-info-label">Course Name</span>
            <span class="fedc-info-value">{{ $drive['course'] }}</span>
        </div>
        <div class="fedc-info-item">
            <span class="fedc-info-label">Academic Session</span>
            <span class="fedc-info-value">{{ $drive['session'] }}</span>
        </div>
        <div class="fedc-info-item">
            <span class="fedc-info-label">Subject</span>
            <span class="fedc-info-value">{{ $subjectTabs[$subjectPk] ?? '—' }}</span>
        </div>
        <div class="fedc-info-item">
            <span class="fedc-info-label">Service</span>
            <span class="fedc-info-value" title="{{ $drive['service_name'] }}">{{ $drive['service'] }}</span>
        </div>
    </div>

    @if ($passFixed === null)
        <div class="alert alert-warning">
            @if ($limits['service_name'])
                Passing marks for <strong>{{ $limits['service_name'] }}</strong> are not set. Ask the COE admin to add them in Master Data › Service Passing Marks.
            @else
                This drive has no Service yet. Ask the COE admin to set it in Edit Examination Drive.
            @endif
        </div>
    @endif
    @if ($limits['subject_max'] === null)
        <div class="alert alert-warning">Max marks for <strong>{{ $limits['subject_name'] }}</strong> are not set. Ask the COE admin to add them in Master Data › Subject Max Marks.</div>
    @endif

    {{-- One subject per page: a faculty sees only their own; an admin can switch. --}}
    @if (count($subjectTabs) > 1)
        <div class="d-flex flex-wrap gap-2 mb-3" role="tablist" aria-label="Subjects">
            @foreach ($subjectTabs as $pk => $name)
                <a href="{{ route('admin.coe.faculty-examination-drive.setup', ['id' => $drive['id'], 'subject' => $pk]) }}"
                   class="btn {{ $pk === $subjectPk ? 'btn-primary' : 'btn-outline-primary' }}" role="tab"
                   aria-selected="{{ $pk === $subjectPk ? 'true' : 'false' }}">{{ $name }}</a>
            @endforeach
        </div>
    @endif

    {{-- Faculty locks (ExaminationDriveController::facultyLockMessage; the server enforces them too). --}}
    @if ($readOnly)
        <div class="alert alert-success"><i class="bi bi-lock me-1" aria-hidden="true"></i>This drive has been <strong>approved by the Course Coordinator</strong>. The setup is view only.</div>
    @elseif ($keepSubmitted)
        <div class="alert alert-info"><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>This drive is <strong>pending at the Course Coordinator</strong>. You can still correct values, but submitted components cannot be removed.</div>
    @endif

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Approved: every input, picker and button in the table is disabled. --}}
            <fieldset @disabled($readOnly)>
            <div class="table-responsive">
                <table class="fedc-map-table" id="fedcMapTable">
                    <thead>
                        <tr>
                            <th style="width:8%">Subject</th>
                            <th style="width:12%" data-sort="component">Components<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:7%"  data-sort="max">Max Marks<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:8%"  data-sort="pass">Passing Marks<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:8%">Evaluation Through</th>
                            <th style="width:11%">Faculty</th>
                            <th style="width:8%"  data-sort="weightage">Weightage %<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:13%">Exam Range</th>
                            <th style="width:9%">Exam Mode</th>
                            <th style="width:9%">Exam Type</th>
                            <th style="width:7%" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="fedcMapBody">
                        @foreach ($drive['component_rows'] as $row)
                        <tr class="fedc-map-row">
                            <td class="fedc-subject">{{ $row['subject'] }}</td>
                            <td>
                                <select class="form-select fedc-sel-component">
                                    @foreach ($components as $c)
                                    <option value="{{ $c }}" @if($c === $row['component']) selected @endif>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control fedc-max-marks"
                                       min="0" max="1000" step="0.01" value="{{ $row['max_marks'] }}">
                            </td>
                            <td>
                                <input type="number" class="form-control fedc-pass-marks" readonly
                                       title="From Service Passing Marks" value="{{ $passFixed === null ? $row['passing_marks'] : $num($passFixed) }}">
                            </td>
                            <td>
                                <select class="form-select fedc-source">
                                    @foreach ($sources as $s)
                                    <option value="{{ $s }}" @if($s === $row['source']) selected @endif>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                @php($systemSource = in_array($row['source'], ['Sargam', 'Sargam 2.0'], true))
                                <button type="button" class="fedc-fac-open-btn"
                                        data-row-idx="{{ $loop->index }}"
                                        data-faculty="{{ $systemSource ? '' : ($row['sub_coordinators'][0] ?? '') }}"
                                        data-sub='@json($systemSource ? [] : array_slice($row['sub_coordinators'], 1))'
                                        data-codes='@json($systemSource ? new \stdClass : (object) $row['faculty_codes'])'
                                        data-uniq='@json($systemSource ? new \stdClass : (object) $row['faculty_unique_codes'])'
                                        @disabled($systemSource)
                                        title="Click to assign Faculty & Sub coordinates">
                                    <span class="fedc-fac-text">
                                    @if($systemSource)
                                        {{ $row['source'] }}
                                    @elseif(count($row['sub_coordinators']) > 0)
                                        {{ implode(', ', $row['sub_coordinators']) }}
                                    @else
                                        <span class="fedc-fac-placeholder">Click to add Faculty &amp; Sub coordinates.</span>
                                    @endif
                                    </span>
                                </button>
                            </td>
                            <td>
                                <input type="number" class="form-control fedc-weightage"
                                       min="0" max="100" step="0.01" value="{{ $row['weightage'] }}">
                            </td>
                            <td>
                                <input type="text" class="form-control fedc-exam-range" readonly
                                       data-start="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $row['exam_date'])->format('Y-m-d') }}"
                                       data-end="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $row['exam_end_date'])->format('Y-m-d') }}"
                                       placeholder="dd/mm/yyyy - dd/mm/yyyy">
                            </td>
                            <td>
                                <select class="form-select fedc-sel-mode">
                                    @foreach ($examModes as $m)
                                    <option value="{{ $m }}" @if($m === $row['exam_mode']) selected @endif>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select fedc-sel-type">
                                    <option value="">Select</option>
                                    @foreach ($componentExamTypes as $t)
                                    <option value="{{ $t }}" @if($t === $row['exam_type']) selected @endif>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="fedc-actions">
                                    {{-- A submitted component stays while the drive is pending at the CC. --}}
                                    @unless ($readOnly || $keepSubmitted)
                                    <button type="button" class="fedc-row-del fedc-row-del-btn" aria-label="Remove row">
                                        <i class="bi bi-dash" aria-hidden="true"></i>
                                    </button>
                                    @endunless
                                    @unless ($readOnly)
                                    <button type="button" class="fedc-row-add fedc-row-add-btn" aria-label="Add row">
                                        <i class="bi bi-plus" aria-hidden="true"></i>
                                    </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fedc-total-row">
                            <td></td>
                            <td class="text-end pe-2">Total Max Marks:</td>
                            <td class="ps-2"><span id="fedcTotalMax">{{ $num(array_sum(array_column($drive['component_rows'], 'max_marks'))) }}</span>@if ($limits['subject_max'] !== null) / {{ $num($limits['subject_max']) }}@endif</td>
                            <td colspan="3" class="text-end pe-4">Total Weightage:</td>
                            <td class="ps-3"><span id="fedcTotalWeightage">{{ $num(array_sum(array_column($drive['component_rows'], 'weightage'))) }}</span> / 100</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </fieldset>

            <div class="fedc-footer">
                <a href="{{ route('admin.coe.faculty-examination-drive.index') }}"
                   class="btn coe-btn coe-btn-cancel">{{ $readOnly ? 'Back' : 'Cancel' }}</a>
                @unless ($readOnly)
                <button type="button" class="btn coe-btn coe-btn-primary" id="fedcSaveBtn" @disabled($missingMaster)>Save</button>
                @endunless
            </div>
        </div>
    </div>
</div>

{{-- ── Assign Faculty modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="fedcAssignModal" tabindex="-1" aria-labelledby="fedcAssignModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header border-0 pb-1">
                <h5 class="modal-title fw-bold" id="fedcAssignModalLabel">Assign Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body pt-2" id="fedcAssignBody">

                {{-- Tabs --}}
                <div class="fedc-af-tabs mb-3">
                    <div class="fedc-af-tab is-active" data-tab="single">Single Faculty</div>
                    <div class="fedc-af-tab" data-tab="multi">Multi Faculty</div>
                </div>

                {{-- Single Faculty pane --}}
                <div id="fedcAfSingle">
                    <div class="fedc-fac-search">
                        <input type="text" id="fedcFacSearch" placeholder="Search Faculty" autocomplete="off">
                    </div>
                    <ul class="fedc-fac-list" id="fedcFacList">
                        @foreach ($faculty as $f)
                        <li class="fedc-fac-item" data-faculty="{{ $f }}">{{ $f }}</li>
                        @endforeach
                    </ul>
                </div>

                {{-- Multi Faculty pane --}}
                <div id="fedcAfMulti" class="d-none">
                    <div class="table-responsive">
                        <table class="fedc-multi-table" id="fedcMultiTable">
                            <thead>
                                <tr>
                                    <th style="width:45%">Faculty</th>
                                    <th style="width:35%">Unique Codes of OT's</th>
                                    <th style="width:20%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="fedcMultiBody">
                                {{-- Rows built by JS from the row's faculty when the window opens --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer coe-modal-footer border-0">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="fedcAssignConfirm">
                    Add Roll Number
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Code picker: Select Roll Number / Select Unique Codes (list built by JS) ── --}}
<div class="modal fade" id="fedcRollModal" tabindex="-1" aria-labelledby="fedcRollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title fw-bold" id="fedcRollModalLabel">Select Roll Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="small text-muted mb-2" id="fedcRnFor"></p>
                <div class="fedc-rn-ranges" id="fedcRnRanges"></div>
                <div class="fedc-rn-list" id="fedcRnList"></div>
                <div class="alert alert-warning small mb-0 d-none" id="fedcRnEmpty"></div>
            </div>
            <div class="modal-footer border-0 pt-1">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn coe-btn coe-btn-primary" id="fedcRnAssign">Assign</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin_assets/libs/select2/dist/js/select2.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
<script>
$(function () {
    'use strict';

    var COMPONENTS = @json($components);
    var SOURCES    = @json($sources);
    var FACULTY    = @json($faculty);
    // Generated in COE › Roll Numbers / Unique Participant Code for the drive's course.
    var ROLL_NUMBERS = @json($rollNumbers);
    var UNIQUE_CODES = @json($uniqueCodes);
    var EXAM_MODES = @json($examModes);
    var EXAM_TYPES = @json($componentExamTypes);
    // null = no limit / not set; see the warnings above the table.
    var SUBJECT_MAX  = @json($limits['subject_max']);
    var PASS_FIXED   = @json($passFixed);
    var SUBJECT_PK   = @json($subjectPk);
    // Drive dates [start, end] (Y-m-d): the bounds of every component's exam range.
    var DRIVE_RANGE  = @json($driveRange);
    function fpDate(iso) { var p = iso.split('-'); return new Date(p[0], p[1] - 1, p[2]); }
    var SUBJECT_NAME = @json($subjectTabs[$subjectPk] ?? '');

    /* ── Total Weightage live update ──────────────────────────────────── */
    function sum(sel) {
        var total = 0;
        $('#fedcMapBody ' + sel).each(function () { total += parseFloat($(this).val()) || 0; });
        return Math.round(total * 100) / 100;
    }
    function recalcTotal() {
        var weight = sum('.fedc-weightage'), max = sum('.fedc-max-marks');
        $('#fedcTotalWeightage').text(weight).toggleClass('is-over', weight > 100);
        $('#fedcTotalMax').text(max).toggleClass('is-over', SUBJECT_MAX !== null && max > SUBJECT_MAX);
    }
    $('#fedcMapBody').on('input', '.fedc-weightage, .fedc-max-marks', recalcTotal);
    // A field flagged by the save check clears as soon as it is filled in.
    $('#fedcMapBody').on('change input', 'select, input', function () { $(this).removeClass('is-invalid'); });
    recalcTotal();

    /* ── Build a new component row ────────────────────────────────────── */
    function componentOptions(sel) {
        return COMPONENTS.map(function (c) {
            return '<option value="' + c + '"' + (c === sel ? ' selected' : '') + '>' + c + '</option>';
        }).join('');
    }
    function sourceOptions(sel) {
        return SOURCES.map(function (s) {
            return '<option value="' + s + '"' + (s === sel ? ' selected' : '') + '>' + s + '</option>';
        }).join('');
    }
    function modeOptions(sel) {
        return EXAM_MODES.map(function (m) {
            return '<option value="' + m + '"' + (m === sel ? ' selected' : '') + '>' + m + '</option>';
        }).join('');
    }
    function typeOptions(sel) {
        return '<option value="">Select</option>' + EXAM_TYPES.map(function (t) {
            return '<option value="' + t + '"' + (t === sel ? ' selected' : '') + '>' + t + '</option>';
        }).join('');
    }

    /* ── Exam Range: one flatpickr range per row; start/end kept as Y-m-d ── */
    function initRange(el) {
        var $el = $(el);
        flatpickr(el, {
            mode: 'range',
            dateFormat: 'd/m/Y',
            locale: { rangeSeparator: ' - ' },
            // The exam range must sit inside the drive's dates (the server checks it too).
            minDate: DRIVE_RANGE[0] ? fpDate(DRIVE_RANGE[0]) : null,
            maxDate: DRIVE_RANGE[1] ? fpDate(DRIVE_RANGE[1]) : null,
            defaultDate: [$el.attr('data-start'), $el.attr('data-end')].filter(Boolean),
            parseDate: function (str) { var p = str.split('-'); return p.length === 3 ? new Date(p[0], p[1] - 1, p[2]) : undefined; },
            onClose: function (dates, str, fp) {
                // A single pick means a one-day exam.
                var start = dates[0] ? fp.formatDate(dates[0], 'Y-m-d') : '';
                var end = dates[1] ? fp.formatDate(dates[1], 'Y-m-d') : start;
                $el.attr('data-start', start).attr('data-end', end);
                if (dates.length === 1) { fp.setDate([dates[0], dates[0]], false); }
            }
        });
    }

    function buildRow() {
        return '<tr class="fedc-map-row">' +
            '<td class="fedc-subject">' + $('<div>').text(SUBJECT_NAME).html() + '</td>' +
            '<td><select class="form-select fedc-sel-component"><option value="">Component</option>' + componentOptions('') + '</select></td>' +
            '<td><input type="number" class="form-control fedc-max-marks" min="0" step="0.01" value=""></td>' +
            '<td><input type="number" class="form-control fedc-pass-marks" readonly title="From Service Passing Marks" value="' + (PASS_FIXED === null ? '' : PASS_FIXED) + '"></td>' +
            '<td><select class="form-select fedc-source">' + sourceOptions(SOURCES[0]) + '</select></td>' +
            '<td><button type="button" class="fedc-fac-open-btn" data-faculty="" data-sub="[]" data-codes="{}" data-uniq="{}">' +
            '<span class="fedc-fac-text"><span class="fedc-fac-placeholder">Click to add Faculty &amp; Sub coordinates.</span></span></button></td>' +
            '<td><input type="number" class="form-control fedc-weightage" min="0" max="100" step="0.01" value="0"></td>' +
            // A new component's exam range starts as the whole drive (narrowed in the picker).
            '<td><input type="text" class="form-control fedc-exam-range" readonly placeholder="dd/mm/yyyy - dd/mm/yyyy"' +
                ' data-start="' + (DRIVE_RANGE[0] || '') + '" data-end="' + (DRIVE_RANGE[1] || '') + '"></td>' +
            '<td><select class="form-select fedc-sel-mode">' + modeOptions(EXAM_MODES[0]) + '</select></td>' +
            '<td><select class="form-select fedc-sel-type">' + typeOptions('') + '</select></td>' +
            '<td><div class="fedc-actions">' +
            '<button type="button" class="fedc-row-del fedc-row-del-btn" aria-label="Remove row"><i class="bi bi-dash"></i></button>' +
            '<button type="button" class="fedc-row-add fedc-row-add-btn" aria-label="Add row"><i class="bi bi-plus"></i></button>' +
            '</div></td></tr>';
    }

    var $body = $('#fedcMapBody');

    /* A system-sourced component (Sargam / Sargam 2.0) has no faculty: grey the picker out. */
    var SYSTEM_SOURCES = ['Sargam', 'Sargam 2.0'];
    $body.on('change', '.fedc-source', function () {
        var source = $(this).val();
        var $btn = $(this).closest('.fedc-map-row').find('.fedc-fac-open-btn');
        if (SYSTEM_SOURCES.indexOf(source) !== -1) {
            $btn.data('faculty', '').data('sub', []).data('codes', {}).data('uniq', {}).prop('disabled', true)
                .empty().append($('<span class="fedc-fac-text"></span>').text(source));
        } else if ($btn.prop('disabled')) {
            $btn.prop('disabled', false);
            setRowFaculty($btn, []);
        }
    });
    $body.on('click', '.fedc-row-del-btn', function () {
        if ($body.find('.fedc-map-row').length > 1) {
            $(this).closest('.fedc-map-row').remove();
            recalcTotal();
        }
    });
    $body.on('click', '.fedc-row-add-btn', function () {
        var $row = $(buildRow());
        $(this).closest('.fedc-map-row').after($row);
        initRange($row.find('.fedc-exam-range')[0]);
    });

    if ($body.find('.fedc-map-row').length === 0) {
        $body.append(buildRow());
        recalcTotal();
    }
    $body.find('.fedc-exam-range').each(function () { initRange(this); });

    /* ── Save ─────────────────────────────────────────────────────────── */
    $('#fedcSaveBtn').on('click', function () {
        // Every component row complete (the server checks the same): the first gap is
        // highlighted and named.
        var missing = null;
        $('#fedcMapBody .fedc-map-row').each(function () {
            var $r = $(this), comp = $r.find('.fedc-sel-component').val();
            var checks = [
                [$r.find('.fedc-sel-component'), !comp, 'Select the Component'],
                [$r.find('.fedc-max-marks'), $.trim($r.find('.fedc-max-marks').val()) === '', 'Enter the Max Marks'],
                [$r.find('.fedc-fac-open-btn'), $r.find('.fedc-source').val() === 'Faculty' && !$r.find('.fedc-fac-open-btn').data('faculty'), 'Assign the Faculty'],
                [$r.find('.fedc-weightage'), $.trim($r.find('.fedc-weightage').val()) === '', 'Enter the Weightage'],
                [$r.find('.fedc-exam-range').next('input').length ? $r.find('.fedc-exam-range').next('input') : $r.find('.fedc-exam-range'),
                    !$r.find('.fedc-exam-range').attr('data-start'), 'Select the Exam Range'],
                [$r.find('.fedc-sel-mode'), !$r.find('.fedc-sel-mode').val(), 'Select the Exam Mode'],
                [$r.find('.fedc-sel-type'), !$r.find('.fedc-sel-type').val(), 'Select the Exam Type']
            ];
            checks.forEach(function (c) { c[0].toggleClass('is-invalid', c[1]); if (c[1] && !missing) { missing = c[2] + (comp ? ' for ' + comp : '') + '.'; } });
        });
        if (missing) {
            Swal.fire({ icon: 'warning', title: 'Complete every component', text: missing });
            return;
        }

        // Same limits the server enforces, checked first for a quicker message.
        var weight = sum('.fedc-weightage'), max = sum('.fedc-max-marks');
        if (weight > 100) {
            Swal.fire({ icon: 'warning', title: 'Weightage over 100%', text: 'Total weightage is ' + weight + '%. It cannot exceed 100%.' });
            return;
        }
        if (SUBJECT_MAX !== null && max > SUBJECT_MAX) {
            Swal.fire({ icon: 'warning', title: 'Max marks over the limit', text: 'Total max marks is ' + max + '. It cannot exceed ' + SUBJECT_MAX + ' for this subject.' });
            return;
        }

        var rows = [];
        $('#fedcMapBody .fedc-map-row').each(function () {
            var $r = $(this);
            rows.push({
                component: $r.find('.fedc-sel-component').val(),
                max_marks: $r.find('.fedc-max-marks').val(),
                passing_marks: $r.find('.fedc-pass-marks').val(),
                source: $r.find('.fedc-source').val(),
                faculty: $r.find('.fedc-fac-open-btn').data('faculty') || '',
                // Every name after the first; JSON so an emptied list still reaches the server.
                sub_faculty: JSON.stringify($r.find('.fedc-fac-open-btn').data('sub') || []),
                codes: JSON.stringify($r.find('.fedc-fac-open-btn').data('codes') || {}),
                unique_codes: JSON.stringify($r.find('.fedc-fac-open-btn').data('uniq') || {}),
                weightage: $r.find('.fedc-weightage').val(),
                exam_date: $r.find('.fedc-exam-range').attr('data-start') || '',
                exam_end_date: $r.find('.fedc-exam-range').attr('data-end') || '',
                exam_mode: $r.find('.fedc-sel-mode').val(),
                exam_type: $r.find('.fedc-sel-type').val()
            });
        });

        $.ajax({
            url: '{{ url("admin/coe/examination-drive") }}/{{ (int) $drive["id"] }}/components',
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { subject_master_pk: SUBJECT_PK, rows: rows }
        }).done(function (res) {
            // Setup done: back to the drive list, where the drive now shows as set up.
            Swal.fire({ icon: 'success', title: 'Saved', text: (res && res.message) || 'Faculty mapping saved.',
                        timer: 1800, showConfirmButton: false })
                .then(function () { window.location.href = @json(route('admin.coe.faculty-examination-drive.index')); });
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save faculty mapping.' });
        });
    });

    /* ── Assign Faculty modal ─────────────────────────────────────────── */
    var $assignModal = $('#fedcAssignModal');
    var $activeRowBtn = null;

    $body.on('click', '.fedc-fac-open-btn', function () {
        $activeRowBtn = $(this);
        // Reset to single tab
        $('.fedc-af-tab[data-tab="single"]').click();
        $('#fedcFacSearch').val('').trigger('input');
        $('#fedcAssignConfirm').text('Add Roll Number');

        // Multi tab starts from the row's current names (one empty row when none).
        var current = [];
        if ($activeRowBtn.data('faculty')) { current.push($activeRowBtn.data('faculty')); }
        current = current.concat($activeRowBtn.data('sub') || []);
        var $multi = $('#fedcMultiBody').empty();
        var currentUniq = $activeRowBtn.data('uniq') || {};
        (current.length ? current : ['']).forEach(function (name) {
            var $tr = $(buildMultiRow());
            $tr.find('.fedc-multi-fac').val(name);
            setMultiCodes($tr, currentUniq[name] || []);
            $multi.append($tr);
            initMultiFaculty($tr);
        });

        bootstrap.Modal.getOrCreateInstance($assignModal[0]).show();
    });

    /* Tab switch */
    $('.fedc-af-tab').on('click', function () {
        $('.fedc-af-tab').removeClass('is-active');
        $(this).addClass('is-active');
        var tab = $(this).data('tab');
        $('#fedcAfSingle, #fedcAfMulti').addClass('d-none');
        if (tab === 'single') {
            $('#fedcAfSingle').removeClass('d-none');
            $('#fedcAssignConfirm').text('Add Roll Number');
        } else {
            $('#fedcAfMulti').removeClass('d-none');
            $('#fedcAssignConfirm').text('Assign Faculty');
        }
    });

    /* Search filter */
    $('#fedcFacSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('#fedcFacList .fedc-fac-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    /* Single Faculty click — sets the row's faculty, then opens Select Roll Number sub-modal.
       Client-side only: Save sends every row's faculty (data-faculty), so this works for
       rows that are not saved yet. Persisting here instead 404'd on any unsaved row. */
    $('#fedcFacList').on('click', '.fedc-fac-item', function () {
        var name = $(this).data('faculty');
        if (!$activeRowBtn) { return; }

        var component = $activeRowBtn.closest('.fedc-map-row').find('.fedc-sel-component').val();
        if (!component) {
            Swal.fire({ icon: 'warning', title: 'Pick a component first', text: 'Choose a component for this row before assigning faculty.' });
            return;
        }

        setRowFaculty($activeRowBtn, [name]);
        persistRowFaculty($activeRowBtn, [name], false);
        bootstrap.Modal.getOrCreateInstance($assignModal[0]).hide();
        openRollModal($activeRowBtn, name);
    });

    /* Confirm assign (multi tab) */
    $('#fedcAssignConfirm').on('click', function () {
        if (!$activeRowBtn) { return; }
        // Single tab assigns by clicking a name; this button only applies the Multi tab.
        if ($('#fedcAfMulti').hasClass('d-none')) {
            bootstrap.Modal.getOrCreateInstance($assignModal[0]).hide();
            return;
        }
        if (!$activeRowBtn.closest('.fedc-map-row').find('.fedc-sel-component').val()) {
            Swal.fire({ icon: 'warning', title: 'Pick a component first', text: 'Choose a component for this row before assigning faculty.' });
            return;
        }
        var names = [], uniq = {};
        $('#fedcMultiBody .fedc-multi-row').each(function () {
            var v = $.trim($(this).find('.fedc-multi-fac').val() || '');
            var picked = $(this).data('uniq') || [];
            if (v && names.indexOf(v) === -1) { names.push(v); }
            if (v && picked.length) { uniq[v] = picked; }
        });
        if (!names.length) {
            Swal.fire({ icon: 'warning', title: 'No faculty selected', text: 'Pick at least one faculty.' });
            return;
        }
        setRowFaculty($activeRowBtn, names, null, uniq);
        bootstrap.Modal.getOrCreateInstance($assignModal[0]).hide();
        persistRowFaculty($activeRowBtn, names, true);
    });

    /* Components already stored for this drive. A row whose component is in here is
       saved straight away; a new row has nothing to update yet and waits for Save. */
    var SAVED_COMPONENTS = @json(collect($drive['component_rows'])->pluck('component')->values());

    /* Short non-blocking confirmation in the corner. */
    function fedcToast(text) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: text, timer: 3000, showConfirmButton: false });
    }

    // done: optional confirmation text (e.g. roll numbers assigned) instead of the default "Saved".
    function persistRowFaculty($btn, names, announce, done) {
        var component = $btn.closest('.fedc-map-row').find('.fedc-sel-component').val();
        if (SAVED_COMPONENTS.indexOf(component) === -1) {
            // A new row: the faculty / roll numbers / codes stay on the row and are stored
            // with it when Save is clicked, so there is nothing to send now.
            if (done) { fedcToast(done + ' They will be saved when you click Save.'); }
            return;
        }
        $.ajax({
            url: '{{ url("admin/coe/examination-drive") }}/{{ (int) $drive["id"] }}/components/assign-faculty',
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { subject_master_pk: SUBJECT_PK, component: component, faculty: names[0] || '', sub_faculty: names.slice(1),
                    codes: $.isEmptyObject($btn.data('codes') || {}) ? '' : $btn.data('codes'),
                    unique_codes: $.isEmptyObject($btn.data('uniq') || {}) ? '' : $btn.data('uniq') }
        }).done(function () {
            if (done) {
                fedcToast(done + ' Saved.');
            } else if (announce) {
                Swal.fire({ icon: 'success', title: 'Saved', text: names.length + ' faculty assigned to ' + component + '.', timer: 1800, showConfirmButton: false });
            }
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Not saved',
                text: ((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save faculty.') + ' Click Save to try again.' });
        });
    }

    /* Row faculty = first name; the rest are further sub-coordinators. Saved on Save. */
    function setRowFaculty($btn, names, codes, uniq) {
        // A list not given = keep the existing codes of any name that stays.
        function keep(source) { var kept = {}; names.forEach(function (n) { if (source[n]) { kept[n] = source[n]; } }); return kept; }
        $btn.removeClass('is-invalid');
        $btn.data('faculty', names[0] || '').data('sub', names.slice(1))
            .data('codes', keep(codes || $btn.data('codes') || {}))
            .data('uniq', keep(uniq || $btn.data('uniq') || {}));
        var $text = $('<span class="fedc-fac-text"></span>');
        if (names.length) {
            $text.text(names.join(', '));
        } else {
            $text.html('<span class="fedc-fac-placeholder">Click to add Faculty &amp; Sub coordinates.</span>');
        }
        $btn.empty().append($text);
    }

    /* Multi Faculty row add/del */
    function buildMultiRow() {
        return '<tr class="fedc-multi-row">' +
            '<td><select class="form-select form-select-sm fedc-multi-fac"><option value="">Faculty</option>' +
            FACULTY.map(function (f) { return '<option value="' + esc(f) + '">' + esc(f) + '</option>'; }).join('') + '</select></td>' +
            '<td><button type="button" class="fedc-multi-uniq">Select codes</button></td>' +
            '<td><button type="button" class="fedc-row-del fedc-multi-del me-1"><i class="bi bi-dash"></i></button>' +
            '<button type="button" class="fedc-row-add fedc-multi-add"><i class="bi bi-plus"></i></button></td></tr>';
    }
    $('#fedcMultiBody').on('click', '.fedc-multi-del', function () {
        if ($('#fedcMultiBody .fedc-multi-row').length > 1) { $(this).closest('.fedc-multi-row').remove(); }
    });
    $('#fedcMultiBody').on('click', '.fedc-multi-add', function () {
        var $tr = $(buildMultiRow());
        $(this).closest('.fedc-multi-row').after($tr);
        initMultiFaculty($tr);
    });

    /* Searchable faculty picker on a Multi row (select2 inside the Assign Faculty window).
       A name already picked on another row is disabled, so each faculty appears once;
       changing the faculty clears that row's codes (they belonged to the previous name). */
    function initMultiFaculty($tr) {
        var $sel = $tr.find('.fedc-multi-fac');
        if (!$.fn.select2) { return; }
        $sel.select2({ dropdownParent: $assignModal, width: '100%', placeholder: 'Search faculty', allowClear: true });
        $sel.on('select2:opening', function () {
            var taken = $('#fedcMultiBody .fedc-multi-fac').not($sel).map(function () { return $(this).val(); }).get().filter(Boolean);
            $sel.find('option').each(function () { this.disabled = this.value !== '' && taken.indexOf(this.value) !== -1; });
        }).on('change', function () { setMultiCodes($tr, []); });
    }

    /* A Multi row's Unique Participant Codes live on the row until Assign Faculty. */
    function setMultiCodes($tr, picked) {
        $tr.data('uniq', picked);
        $tr.find('.fedc-multi-uniq').toggleClass('has-codes', picked.length > 0)
            .text(picked.length ? picked.length + ' code' + (picked.length === 1 ? '' : 's') + ' selected' : 'Select codes');
    }
    $('#fedcMultiBody').on('click', '.fedc-multi-uniq', function () {
        var $tr = $(this).closest('.fedc-multi-row');
        var name = $.trim($tr.find('.fedc-multi-fac').val() || '');
        if (!name) {
            Swal.fire({ icon: 'warning', title: 'Pick a faculty first', text: 'Choose the faculty before selecting their codes.' });
            return;
        }
        // Bootstrap modals do not stack: step out of Assign Faculty, come back after.
        bootstrap.Modal.getOrCreateInstance($assignModal[0]).hide();
        openPicker({
            title: 'Select Unique Codes', forText: 'Unique Participant Codes for ' + name,
            items: UNIQUE_CODES.map(function (c) { return { value: c, label: '<strong>' + esc(c) + '</strong>' }; }),
            picked: $tr.data('uniq') || [],
            empty: 'No Unique Participant Codes have been generated for this drive\'s course yet (COE › Master Data › Unique Participant Code).',
            onAssign: function (picked) {
                setMultiCodes($tr, picked);
                // Unique codes are applied to the row with Assign Faculty.
                fedcToast(picked.length
                    ? picked.length + ' unique code' + (picked.length === 1 ? '' : 's') + ' selected for ' + name + '. Click Assign Faculty to apply.'
                    : 'Unique codes cleared for ' + name + '.');
            },
            onClose: function () { bootstrap.Modal.getOrCreateInstance($assignModal[0]).show(); }
        });
    });

    /* ── Code picker (Select Roll Number / Select Unique Codes) ─────────── */
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    var picker = null; // { onAssign, onClose } of the open window
    var $pickModal = $('#fedcRollModal');

    function openPicker(opts) {
        picker = opts;
        $('#fedcRollModalLabel').text(opts.title);
        $('#fedcRnFor').text(opts.forText);
        var $list = $('#fedcRnList').empty(), $ranges = $('#fedcRnRanges').empty(), n = opts.items.length;
        opts.items.forEach(function (item, i) {
            $list.append('<div class="fedc-rn-item"><input type="checkbox" class="fedc-rn-check" id="fedc_pick_' + i + '" value="' + esc(item.value) + '"' +
                (opts.picked.indexOf(item.value) !== -1 ? ' checked' : '') + '><label for="fedc_pick_' + i + '">' + item.label + '</label></div>');
        });
        // Range quick-select tabs, in steps of 50 over the list.
        for (var lo = 0; lo < n; lo += 50) {
            $ranges.append('<span class="fedc-rn-range" data-range="' + lo + '-' + Math.min(lo + 50, n) + '">' + (lo + 1) + '-' + Math.min(lo + 50, n) + '</span>');
        }
        if (n) { $ranges.append('<span class="fedc-rn-range" data-range="all">Select All</span><span class="fedc-rn-range" data-range="none">Clear</span>'); }
        $('#fedcRnEmpty').toggleClass('d-none', n > 0).text(opts.empty);
        $list.toggleClass('d-none', n === 0);
        $('#fedcRnAssign').toggleClass('d-none', n === 0);
        setTimeout(function () { bootstrap.Modal.getOrCreateInstance($pickModal[0]).show(); }, 300);
    }

    $pickModal.on('hidden.bs.modal', function () {
        var done = picker; picker = null;
        if (done && done.onClose) { done.onClose(); }
    });

    $('#fedcRnRanges').on('click', '.fedc-rn-range', function () {
        $('.fedc-rn-range').removeClass('is-active');
        $(this).addClass('is-active');
        var range = String($(this).data('range'));
        var $checks = $('#fedcRnList .fedc-rn-check');
        if (range === 'all' || range === 'none') {
            $checks.prop('checked', range === 'all');
            return;
        }
        var parts = range.split('-');
        var lo = parseInt(parts[0], 10), hi = parseInt(parts[1], 10);
        $checks.each(function (i) { $(this).prop('checked', i >= lo && i < hi); });
    });

    $('#fedcRnAssign').on('click', function () {
        if (!picker) { return; }
        var picked = $('#fedcRnList .fedc-rn-check:checked').map(function () { return this.value; }).get();
        picker.onAssign(picked);
        bootstrap.Modal.getOrCreateInstance($pickModal[0]).hide();
    });

    /* Single Faculty: the ticked roll numbers become this faculty's roll numbers on the row. */
    function openRollModal($btn, name) {
        var current = ($btn.data('codes') || {})[name];
        openPicker({
            title: 'Select Roll Number', forText: 'OTs for ' + name,
            items: ROLL_NUMBERS.map(function (r) { return { value: r.code, label: '<strong>' + esc(r.code) + '</strong> &mdash; ' + esc(r.name) }; }),
            picked: Array.isArray(current) ? current : [],
            empty: 'Roll numbers have not been generated for this drive\'s course yet (COE › Examination Management › Roll Numbers).',
            onAssign: function (picked) {
                var codes = $.extend({}, $btn.data('codes') || {});
                if (picked.length) { codes[name] = picked; } else { delete codes[name]; }
                var names = [$btn.data('faculty')].concat($btn.data('sub') || []).filter(Boolean);
                setRowFaculty($btn, names, codes);
                var component = $btn.closest('.fedc-map-row').find('.fedc-sel-component').val();
                persistRowFaculty($btn, names, true, picked.length
                    ? picked.length + ' roll number' + (picked.length === 1 ? '' : 's') + ' assigned to ' + name + (component ? ' for ' + component : '') + '.'
                    : 'Roll numbers cleared for ' + name + '.');
            }
        });
    }
});
</script>
@endpush
