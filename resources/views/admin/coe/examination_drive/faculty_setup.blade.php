@extends('admin.layouts.master')

@section('title', 'Map Faculty to Examination Drive')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Toolbar */
.fedc-toolbar       { display:flex;justify-content:flex-end;gap:12px;margin-bottom:18px; }
.fedc-tool-btn      { display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:0;border-radius:8px;
                      background:#fff;color:var(--ds-brand);font-size:15px;font-weight:500;box-shadow:0 1px 2px rgba(16,24,40,.06); }
.fedc-tool-btn:hover{ background:var(--ds-surface-2); }
/* Info bar */
.fedc-info-bar      { background:#fff;border-radius:12px;padding:24px 30px;margin-bottom:22px;
                      display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:20px; }
.fedc-info-item     { display:flex;flex-direction:column;gap:6px;min-width:0; }
.fedc-info-label    { font-size:13px;color:var(--ds-ink-muted);text-transform:uppercase;letter-spacing:.3px; }
.fedc-info-value    { font-size:17px;font-weight:600;color:var(--ds-ink);overflow-wrap:anywhere; }
@media (max-width: 991.98px) { .fedc-info-bar { grid-template-columns:repeat(2,minmax(0,1fr)); } }
/* Component mapping table */
.fedc-map-table     { width:100%;border-collapse:separate;border-spacing:0;min-width:1080px;table-layout:fixed; }
.fedc-map-table thead th { padding:10px 8px;background:var(--ds-surface-2);font-size:14px;font-weight:500;line-height:1.2;
                      color:var(--ds-ink);white-space:normal;vertical-align:middle; }
.fedc-map-table thead th:first-child { padding-left:14px; }
.fedc-map-table thead th:first-child { border-radius:10px 0 0 0; }
.fedc-map-table thead th:last-child  { border-radius:0 10px 0 0; }
.fedc-map-table tbody td { padding:12px 5px;border-bottom:1px solid var(--ds-line);vertical-align:middle; }
.fedc-map-table tbody td:first-child { padding-left:12px; }
.fedc-map-table tbody td:last-child  { padding-right:12px; }
.fedc-map-table thead th:last-child  { padding-right:12px; }
.fedc-map-table input[type=date] { padding:6px 6px 6px 10px; }
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
.fedc-fac-open-btn:hover:not(:disabled) { border-color:var(--ds-brand); }
.fedc-fac-open-btn::after { content:'';flex-shrink:0;width:10px;height:10px;margin-left:auto;
                      background:no-repeat center/10px url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); }
.fedc-fac-open-btn:disabled { background:var(--ds-surface-2);color:var(--ds-ink);cursor:not-allowed; }
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
.fedc-footer        { display:flex;justify-content:flex-end;gap:16px;margin-top:28px; }
.fedc-footer .btn   { min-width:116px;height:48px;font-size:17px; }
/* Assign Faculty modal tabs */
.fedc-af-tabs  { display:flex;border:1px solid var(--ds-line);border-radius:8px;overflow:hidden;margin-bottom:18px; }
.fedc-af-tab   { flex:1;padding:9px 0;text-align:center;font-size:13px;font-weight:600;cursor:pointer;
                 color:var(--ds-ink-muted);background:var(--ds-surface-2); }
.fedc-af-tab.is-active { background:var(--ds-brand);color:#fff; }
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
.fedc-rn-range:hover,.fedc-rn-range.is-active { background:var(--ds-brand);color:#fff;border-color:var(--ds-brand); }
.fedc-rn-list    { max-height:260px;overflow-y:auto;border:1px solid var(--ds-line);border-radius:6px; }
.fedc-rn-item    { display:flex;align-items:center;gap:10px;padding:8px 12px;font-size:13px;
                   border-bottom:1px solid var(--ds-line); }
.fedc-rn-item:last-child { border-bottom:none; }
.fedc-rn-item input[type=checkbox] { width:16px;height:16px;accent-color:var(--ds-brand);flex-shrink:0; }
</style>
@endpush

@section('setup_content')
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
            <span class="fedc-info-value">{{ implode(', ', $drive['subjects']) ?: '—' }}</span>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="fedc-map-table" id="fedcMapTable">
                    <thead>
                        <tr>
                            <th style="width:15%" data-sort="component">Components<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:8%"  data-sort="max">Max Marks<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:8%"  data-sort="pass">Passing Marks<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:11%">Source</th>
                            <th style="width:17%">Faculty</th>
                            <th style="width:8%"  data-sort="weightage">Weightage %<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:13%">Exam Date</th>
                            <th style="width:11%">Exam Type</th>
                            <th style="width:9%" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="fedcMapBody">
                        @foreach ($drive['component_rows'] as $row)
                        <tr class="fedc-map-row">
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
                                <input type="number" class="form-control fedc-pass-marks"
                                       min="0" max="1000" step="0.01" value="{{ $row['passing_marks'] }}">
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
                                <input type="date" class="form-control fedc-exam-date"
                                       value="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $row['exam_date'])->format('Y-m-d') }}">
                            </td>
                            <td>
                                <select class="form-select fedc-sel-mode">
                                    @foreach ($examModes as $m)
                                    <option value="{{ $m }}" @if($m === $row['exam_type']) selected @endif>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="fedc-actions">
                                    <button type="button" class="fedc-row-del fedc-row-del-btn" aria-label="Remove row">
                                        <i class="bi bi-dash" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="fedc-row-add fedc-row-add-btn" aria-label="Add row">
                                        <i class="bi bi-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fedc-total-row">
                            <td colspan="5" class="text-end pe-4">Total Weightage:</td>
                            <td id="fedcTotalWeightage" class="ps-3">{{ array_sum(array_column($drive['component_rows'], 'weightage')) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="fedc-footer">
                <a href="{{ route('admin.coe.faculty-examination-drive.index') }}"
                   class="btn coe-btn coe-btn-cancel">Cancel</a>
                <button type="button" class="btn coe-btn coe-btn-primary" id="fedcSaveBtn">Save</button>
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
                                <tr class="fedc-multi-row">
                                    <td>
                                        <select class="form-select form-select-sm fedc-multi-fac">
                                            <option value="">Faculty</option>
                                            @foreach ($faculty as $f)
                                            <option value="{{ $f }}">{{ $f }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm fedc-multi-codes">
                                            <option value="">Codes</option>
                                            @foreach ([32, 24, 18, 12] as $n)
                                            <option value="{{ $n }}">{{ $n }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="fedc-row-del fedc-multi-del me-1" aria-label="Remove"><i class="bi bi-dash"></i></button>
                                        <button type="button" class="fedc-row-add fedc-multi-add" aria-label="Add"><i class="bi bi-plus"></i></button>
                                    </td>
                                </tr>
                                <tr class="fedc-multi-row">
                                    <td>
                                        <select class="form-select form-select-sm fedc-multi-fac">
                                            <option value="">Faculty</option>
                                            @foreach ($faculty as $f)
                                            <option value="{{ $f }}">{{ $f }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm fedc-multi-codes">
                                            <option value="">Codes</option>
                                            @foreach ([32, 24, 18, 12] as $n)
                                            <option value="{{ $n }}">{{ $n }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="fedc-row-del fedc-multi-del me-1" aria-label="Remove"><i class="bi bi-dash"></i></button>
                                        <button type="button" class="fedc-row-add fedc-multi-add" aria-label="Add"><i class="bi bi-plus"></i></button>
                                    </td>
                                </tr>
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

{{-- ── Select Roll Number sub-modal ────────────────────────────────────── --}}
<div class="modal fade" id="fedcRollModal" tabindex="-1" aria-labelledby="fedcRollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title fw-bold" id="fedcRollModalLabel">Select Roll Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="small text-muted mb-2" id="fedcRnFor"></p>
                @if (count($otList) > 0)
                    {{-- Range quick-select tabs, in steps of 50 over the course's OTs --}}
                    <div class="fedc-rn-ranges" id="fedcRnRanges">
                        @for ($lo = 0; $lo < count($otList); $lo += 50)
                            <span class="fedc-rn-range" data-range="{{ $lo }}-{{ min($lo + 50, count($otList)) }}">
                                {{ $lo + 1 }}-{{ min($lo + 50, count($otList)) }}
                            </span>
                        @endfor
                        <span class="fedc-rn-range" data-range="all">Select All</span>
                        <span class="fedc-rn-range" data-range="none">Clear</span>
                    </div>
                    <div class="fedc-rn-list" id="fedcRnList">
                        @foreach ($otList as $ot)
                        <div class="fedc-rn-item">
                            <input type="checkbox" class="fedc-rn-check" value="{{ $ot['code'] }}" id="fedc_ot_{{ $loop->index }}">
                            <label for="fedc_ot_{{ $loop->index }}"><strong>{{ $ot['code'] }}</strong> &mdash; {{ $ot['name'] }}</label>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning small mb-0">No OTs with an OT code are enrolled in this drive's course yet.</div>
                @endif
            </div>
            <div class="modal-footer border-0 pt-1">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Close</button>
                @if (count($otList) > 0)
                    <button type="button" class="btn coe-btn coe-btn-primary" id="fedcRnAssign">Assign</button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var COMPONENTS = @json($components);
    var SOURCES    = @json($sources);
    var FACULTY    = @json($faculty);
    var EXAM_MODES = @json($examModes);

    /* ── Total Weightage live update ──────────────────────────────────── */
    function recalcTotal() {
        var total = 0;
        $('#fedcMapBody .fedc-weightage').each(function () { total += parseFloat($(this).val()) || 0; });
        $('#fedcTotalWeightage').text(total % 1 === 0 ? total : total.toFixed(2));
    }
    $('#fedcMapBody').on('input', '.fedc-weightage', recalcTotal);

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

    function buildRow() {
        return '<tr class="fedc-map-row">' +
            '<td><select class="form-select fedc-sel-component"><option value="">Component</option>' + componentOptions('') + '</select></td>' +
            '<td><input type="number" class="form-control fedc-max-marks" min="0" max="1000" step="0.01" value="100"></td>' +
            '<td><input type="number" class="form-control fedc-pass-marks" min="0" max="1000" step="0.01" value="40"></td>' +
            '<td><select class="form-select fedc-source">' + sourceOptions(SOURCES[0]) + '</select></td>' +
            '<td><button type="button" class="fedc-fac-open-btn" data-faculty="" data-sub="[]" data-codes="{}">' +
            '<span class="fedc-fac-text"><span class="fedc-fac-placeholder">Click to add Faculty &amp; Sub coordinates.</span></span></button></td>' +
            '<td><input type="number" class="form-control fedc-weightage" min="0" max="100" step="0.01" value="0"></td>' +
            '<td><input type="date" class="form-control fedc-exam-date"></td>' +
            '<td><select class="form-select fedc-sel-mode">' + modeOptions(EXAM_MODES[0]) + '</select></td>' +
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
            $btn.data('faculty', '').data('sub', []).data('codes', {}).prop('disabled', true)
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
        $(this).closest('.fedc-map-row').after(buildRow());
    });

    if ($body.find('.fedc-map-row').length === 0) {
        $body.append(buildRow());
        recalcTotal();
    }

    /* ── Save ─────────────────────────────────────────────────────────── */
    $('#fedcSaveBtn').on('click', function () {
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
                weightage: $r.find('.fedc-weightage').val(),
                exam_date: $r.find('.fedc-exam-date').val(),
                exam_type: $r.find('.fedc-sel-mode').val()
            });
        });

        $.ajax({
            url: '{{ url("admin/coe/examination-drive") }}/{{ (int) $drive["id"] }}/components',
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { rows: rows }
        }).done(function () {
            Swal.fire({ icon: 'success', title: 'Saved', text: 'Faculty mapping saved.' });
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
        var currentCodes = $activeRowBtn.data('codes') || {};
        (current.length ? current : ['']).forEach(function (name) {
            var $tr = $(buildMultiRow());
            $tr.find('.fedc-multi-fac').val(name);
            $tr.find('.fedc-multi-codes').val(currentCodes[name] || '');
            $multi.append($tr);
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
        var names = [], codes = {};
        $('#fedcMultiBody .fedc-multi-row').each(function () {
            var v = $.trim($(this).find('.fedc-multi-fac').val() || '');
            var code = $.trim($(this).find('.fedc-multi-codes').val() || '');
            if (v && names.indexOf(v) === -1) { names.push(v); }
            if (v && code) { codes[v] = code; }
            else if (v && Array.isArray(($activeRowBtn.data('codes') || {})[v])) { codes[v] = $activeRowBtn.data('codes')[v]; }
        });
        if (!names.length) {
            Swal.fire({ icon: 'warning', title: 'No faculty selected', text: 'Pick at least one faculty.' });
            return;
        }
        setRowFaculty($activeRowBtn, names, codes);
        bootstrap.Modal.getOrCreateInstance($assignModal[0]).hide();
        persistRowFaculty($activeRowBtn, names, true);
    });

    /* Components already stored for this drive. A row whose component is in here is
       saved straight away; a new row has nothing to update yet and waits for Save. */
    var SAVED_COMPONENTS = @json(collect($drive['component_rows'])->pluck('component')->values());

    function persistRowFaculty($btn, names, announce) {
        var component = $btn.closest('.fedc-map-row').find('.fedc-sel-component').val();
        if (SAVED_COMPONENTS.indexOf(component) === -1) {
            // The single tab goes on to open the roll-number modal; no second overlay.
            if (!announce) { return; }
            Swal.fire({ icon: 'info', title: 'Not saved yet',
                text: 'This is a new row. Click Save at the bottom to store it with its faculty.' });
            return;
        }
        $.ajax({
            url: '{{ url("admin/coe/examination-drive") }}/{{ (int) $drive["id"] }}/components/assign-faculty',
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { component: component, faculty: names[0] || '', sub_faculty: names.slice(1),
                    codes: $.isEmptyObject($btn.data('codes') || {}) ? '' : $btn.data('codes') }
        }).done(function () {
            if (announce) {
                Swal.fire({ icon: 'success', title: 'Saved', text: names.length + ' faculty assigned to ' + component + '.', timer: 1800, showConfirmButton: false });
            }
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Not saved',
                text: ((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save faculty.') + ' Click Save to try again.' });
        });
    }

    /* Row faculty = first name; the rest are further sub-coordinators. Saved on Save. */
    function setRowFaculty($btn, names, codes) {
        // Codes not given (single tab) = keep the existing code of any name that stays.
        var source = codes || $btn.data('codes') || {}, kept = {};
        names.forEach(function (n) { if (source[n]) { kept[n] = source[n]; } });
        $btn.data('faculty', names[0] || '').data('sub', names.slice(1)).data('codes', kept);
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
            FACULTY.map(function (f) { return '<option>' + f + '</option>'; }).join('') + '</select></td>' +
            '<td><select class="form-select form-select-sm fedc-multi-codes"><option value="">Codes</option>' +
            [32,24,18,12].map(function (n) { return '<option>' + n + '</option>'; }).join('') + '</select></td>' +
            '<td><button type="button" class="fedc-row-del fedc-multi-del me-1"><i class="bi bi-dash"></i></button>' +
            '<button type="button" class="fedc-row-add fedc-multi-add"><i class="bi bi-plus"></i></button></td></tr>';
    }
    $('#fedcMultiBody').on('click', '.fedc-multi-del', function () {
        if ($('#fedcMultiBody .fedc-multi-row').length > 1) { $(this).closest('.fedc-multi-row').remove(); }
    });
    $('#fedcMultiBody').on('click', '.fedc-multi-add', function () {
        $(this).closest('.fedc-multi-row').after(buildMultiRow());
    });

    /* ── Select Roll Number sub-modal ─────────────────────────────────── */
    var rollTarget = null; // { $btn, name } the window was opened for

    function openRollModal($btn, name) {
        rollTarget = { $btn: $btn, name: name };
        var current = ($btn.data('codes') || {})[name];
        var picked = Array.isArray(current) ? current : [];
        $('#fedcRnFor').text('OTs for ' + name);
        $('#fedcRnList .fedc-rn-check').each(function () { $(this).prop('checked', picked.indexOf(this.value) !== -1); });
        $('.fedc-rn-range').removeClass('is-active');
        setTimeout(function () { bootstrap.Modal.getOrCreateInstance($('#fedcRollModal')[0]).show(); }, 300);
    }

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

    /* Assign: the ticked OT codes become this faculty's roll numbers on the row. */
    $('#fedcRnAssign').on('click', function () {
        if (!rollTarget) { return; }
        var picked = $('#fedcRnList .fedc-rn-check:checked').map(function () { return this.value; }).get();
        var $btn = rollTarget.$btn, codes = $.extend({}, $btn.data('codes') || {});
        if (picked.length) { codes[rollTarget.name] = picked; } else { delete codes[rollTarget.name]; }
        var names = [$btn.data('faculty')].concat($btn.data('sub') || []).filter(Boolean);
        setRowFaculty($btn, names, codes);
        bootstrap.Modal.getOrCreateInstance($('#fedcRollModal')[0]).hide();
        persistRowFaculty($btn, names, true);
    });
});
</script>
@endpush
