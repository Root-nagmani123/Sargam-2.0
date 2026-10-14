@extends('admin.layouts.master')

@section('title', 'Map Faculty to Examination Drive')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Info bar */
.fedc-info-bar      { background:var(--ds-surface-2);border:1px solid var(--ds-line);border-radius:8px;
                      padding:14px 20px;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:24px; }
.fedc-info-item     { display:flex;flex-direction:column;gap:2px; }
.fedc-info-label    { font-size:10px;font-weight:700;color:var(--ds-ink-muted);text-transform:uppercase;letter-spacing:.5px; }
.fedc-info-value    { font-size:14px;font-weight:700;color:var(--ds-ink); }
/* Component mapping table */
.fedc-map-table     { width:100%;border-collapse:collapse; }
.fedc-map-table th  { padding:9px 10px;background:var(--ds-surface-2);font-size:12px;font-weight:700;
                      color:var(--ds-ink-muted);border-bottom:1px solid var(--ds-line);white-space:nowrap; }
.fedc-map-table td  { padding:7px 6px;border-bottom:1px solid var(--ds-line);vertical-align:middle;font-size:13px; }
.fedc-map-table tr:last-child td { border-bottom:none; }
/* Sortable column headers */
.fedc-map-table th[data-sort] { cursor:pointer;user-select:none; }
.fedc-map-table th[data-sort]::after { content:' ⇅';opacity:.4;font-size:10px; }
/* Faculty placeholder text */
.fedc-fac-placeholder { font-size:11px;color:var(--ds-ink-muted);font-style:italic; }
/* Row buttons */
.fedc-row-del  { background:#dc3545;color:#fff;border:none;border-radius:50%;width:28px;height:28px;
                 display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0; }
.fedc-row-add  { background:#004384;color:#fff;border:none;border-radius:50%;width:28px;height:28px;
                 display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0; }
.fedc-row-del:hover{ background:#b02a37; }
.fedc-row-add:hover{ background:#003070; }
/* Total weightage row */
.fedc-total-row td  { background:var(--ds-surface-2);font-weight:700;padding:8px 10px;font-size:13px; }
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

    {{-- Breadcrumb --}}
    <div class="d-flex align-items-center gap-3 mb-1" style="font-size:12px;color:var(--ds-ink-muted)">
        <a href="{{ route('admin.coe.faculty-examination-drive.index') }}"
           class="d-inline-flex align-items-center gap-1 text-decoration-none" style="color:inherit">
            <i class="bi bi-arrow-left" style="font-size:18px;color:var(--ds-ink)"></i>
        </a>
        <span>Setup &rsaquo; COE &rsaquo; Examination Management &rsaquo; Map Faculty to Examination Drive</span>
    </div>
    <h4 class="fw-bold mb-3" style="font-size:22px">Map Faculty to Examination Drive</h4>

    <div class="d-flex justify-content-end gap-2 mb-3 coe-secondary-actions">
        <button type="button" class="btn programme-dt-btn-columns" id="fedcDownloadBtn">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" class="btn programme-dt-btn-columns" id="fedcPrintBtn">
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
            <span class="fedc-info-value">{{ $drive['component_rows'][0]['component'] ?? '—' }}</span>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="fedc-map-table" id="fedcMapTable">
                    <thead>
                        <tr>
                            <th style="width:14%" data-sort="component">Components</th>
                            <th style="width:9%"  data-sort="max">Max Marks</th>
                            <th style="width:9%"  data-sort="pass">Passing Marks</th>
                            <th style="width:10%">Source</th>
                            <th style="width:20%">Faculty</th>
                            <th style="width:9%"  data-sort="weightage">Weightage %</th>
                            <th style="width:10%">Exam Date</th>
                            <th style="width:10%">Exam Type</th>
                            <th style="width:9%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="fedcMapBody">
                        @foreach ($drive['component_rows'] as $row)
                        <tr class="fedc-map-row">
                            <td>
                                <select class="form-select form-select-sm">
                                    @foreach ($components as $c)
                                    <option value="{{ $c }}" @if($c === $row['component']) selected @endif>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm fedc-max-marks"
                                       min="0" max="1000" value="{{ $row['max_marks'] }}" style="width:70px">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm fedc-pass-marks"
                                       min="0" max="1000" step="0.01" value="{{ $row['passing_marks'] }}" style="width:70px">
                            </td>
                            <td>
                                <select class="form-select form-select-sm fedc-source">
                                    @foreach ($sources as $s)
                                    <option value="{{ $s }}" @if($s === $row['source']) selected @endif>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <button type="button" class="fedc-fac-open-btn w-100 text-start btn btn-sm btn-outline-secondary"
                                        data-row-idx="{{ $loop->index }}"
                                        title="Click to assign Faculty & Sub coordinates">
                                    @if($row['faculty'] && $row['faculty'] !== 'Sargam 2.0')
                                        {{ $row['faculty'] }}
                                    @else
                                        <span class="fedc-fac-placeholder">Click to add Faculty &amp; Sub coordinates</span>
                                    @endif
                                </button>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm fedc-weightage"
                                       min="0" max="100" step="0.01" value="{{ $row['weightage'] }}" style="width:70px">
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm"
                                       value="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $row['exam_date'])->format('Y-m-d') }}">
                            </td>
                            <td>
                                <select class="form-select form-select-sm">
                                    @foreach ($examModes as $m)
                                    <option value="{{ $m }}" @if($m === $row['exam_type']) selected @endif>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
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
                            <td colspan="5" class="text-end pe-3">Total Weightage:</td>
                            <td id="fedcTotalWeightage">{{ array_sum(array_column($drive['component_rows'], 'weightage')) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.coe.faculty-examination-drive.index') }}"
                   class="btn coe-btn coe-btn-cancel">Cancel</a>
                <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="fedcSaveBtn">Save</button>
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
                {{-- Range quick-select tabs --}}
                <div class="fedc-rn-ranges" id="fedcRnRanges">
                    <span class="fedc-rn-range is-active" data-range="0-50">0-50</span>
                    <span class="fedc-rn-range" data-range="50-100">50-100</span>
                    <span class="fedc-rn-range" data-range="100-150">100-150</span>
                    <span class="fedc-rn-range" data-range="150-200">150-200</span>
                    <span class="fedc-rn-range" data-range="all">Select All</span>
                </div>
                <div class="fedc-rn-list" id="fedcRnList">
                    @foreach ($uniqueCodes as $uc)
                    <div class="fedc-rn-item">
                        <input type="checkbox" class="fedc-rn-check" value="{{ $uc }}" id="fedc_uc_{{ $loop->index }}">
                        <label for="fedc_uc_{{ $loop->index }}">{{ $uc }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-0 pt-1">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3"
                        data-bs-dismiss="modal">Close</button>
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
            '<td><select class="form-select form-select-sm"><option value="">Component</option>' + componentOptions('') + '</select></td>' +
            '<td><input type="number" class="form-control form-control-sm fedc-max-marks" min="0" max="1000" value="100" style="width:70px"></td>' +
            '<td><input type="number" class="form-control form-control-sm fedc-pass-marks" min="0" max="1000" step="0.01" value="40" style="width:70px"></td>' +
            '<td><select class="form-select form-select-sm fedc-source">' + sourceOptions(SOURCES[0]) + '</select></td>' +
            '<td><button type="button" class="fedc-fac-open-btn w-100 text-start btn btn-sm btn-outline-secondary">' +
            '<span class="fedc-fac-placeholder">Click to add Faculty &amp; Sub coordinates</span></button></td>' +
            '<td><input type="number" class="form-control form-control-sm fedc-weightage" min="0" max="100" step="0.01" value="0" style="width:70px"></td>' +
            '<td><input type="date" class="form-control form-control-sm"></td>' +
            '<td><select class="form-select form-select-sm">' + modeOptions(EXAM_MODES[0]) + '</select></td>' +
            '<td><div class="d-flex gap-1">' +
            '<button type="button" class="fedc-row-del fedc-row-del-btn" aria-label="Remove row"><i class="bi bi-dash"></i></button>' +
            '<button type="button" class="fedc-row-add fedc-row-add-btn" aria-label="Add row"><i class="bi bi-plus"></i></button>' +
            '</div></td></tr>';
    }

    var $body = $('#fedcMapBody');
    $body.on('click', '.fedc-row-del-btn', function () {
        if ($body.find('.fedc-map-row').length > 1) {
            $(this).closest('.fedc-map-row').remove();
            recalcTotal();
        }
    });
    $body.on('click', '.fedc-row-add-btn', function () {
        $(this).closest('.fedc-map-row').after(buildRow());
    });

    /* ── Save ─────────────────────────────────────────────────────────── */
    $('#fedcSaveBtn').on('click', function () {
        Swal.fire({ icon: 'success', title: 'Saved', text: 'Faculty mapping saved.' });
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

    /* Single Faculty click — opens Select Roll Number sub-modal */
    $('#fedcFacList').on('click', '.fedc-fac-item', function () {
        var name = $(this).data('faculty');
        // Update button text in the row
        if ($activeRowBtn) {
            $activeRowBtn.html($('<div>').text(name).html());
        }
        bootstrap.Modal.getOrCreateInstance($assignModal[0]).hide();
        // Show Select Roll Number sub-modal
        setTimeout(function () {
            bootstrap.Modal.getOrCreateInstance($('#fedcRollModal')[0]).show();
        }, 300);
    });

    /* Confirm assign (multi tab) */
    $('#fedcAssignConfirm').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($assignModal[0]).hide();
        Swal.fire({ icon: 'success', title: 'Assigned', text: 'Faculty assigned.', timer: 1500, showConfirmButton: false });
    });

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
    var ALL_CODES = @json($uniqueCodes);

    $('#fedcRnRanges').on('click', '.fedc-rn-range', function () {
        $('.fedc-rn-range').removeClass('is-active');
        $(this).addClass('is-active');
        var range = $(this).data('range');
        var $checks = $('#fedcRnList .fedc-rn-check');
        if (range === 'all') {
            $checks.prop('checked', true);
            return;
        }
        var parts = range.split('-');
        var lo = parseInt(parts[0], 10), hi = parseInt(parts[1], 10);
        $checks.each(function (i) { $(this).prop('checked', i >= lo && i < hi); });
    });
});
</script>
@endpush
