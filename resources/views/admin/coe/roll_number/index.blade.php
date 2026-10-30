@extends('admin.layouts.master')

@section('title', 'Roll Numbers')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Status badges */
.rn-status-frozen  { background:#d1f0e0;color:#0d6832; }
.rn-status-pending { background:#fff3cd;color:#856404; }
.rn-status-none    { background:#eef0f3;color:#5c6670; }
/* Row-level action icons */
.rn-act            { display:inline-flex;flex-direction:column;align-items:center;gap:2px;background:none;border:none;padding:0 5px;cursor:pointer;font-size:11px;line-height:1.2; }
.rn-act i          { font-size:16px; }
.rn-act--edit i,
.rn-act--assign i  { color:#004384; }
.rn-act--del i     { color:#dc3545; }
.rn-act--notify i  { color:#6c757d; }
.rn-act--freeze i  { color:#17a2b8; }
.rn-act:hover i    { opacity:.75; }
.rn-act:disabled   { opacity:.5;cursor:wait; }
.rn-act-group      { display:flex;align-items:center;gap:2px; }
/* Bulk header buttons */
.rn-bulk-btn-del   { background:#dc3545;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:13px;font-weight:600; }
.rn-bulk-btn-del:hover{ background:#b02a37; }
.rn-bulk-btn-freeze{ background:#198754;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:13px;font-weight:600; }
.rn-bulk-btn-freeze:hover{ background:#146c43; }
.rn-bulk-btn-notify{ background:#6c757d;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:13px;font-weight:600; }
.rn-bulk-btn-notify:hover{ background:#565e64; }
/* Generate modal allocation-rules checkbox area */
.rn-alloc-rules    { border:1px solid var(--ds-line);border-radius:8px;padding:12px 14px;margin-top:8px; }
/* Import modal tabs */
.rn-imp-tabs       { display:flex;border-bottom:1px solid var(--ds-line);margin-bottom:18px; }
.rn-imp-tab        { flex:1;padding:9px 0;text-align:center;font-size:13px;font-weight:600;cursor:pointer;border-radius:8px 8px 0 0;color:var(--ds-ink-muted); }
.rn-imp-tab.is-active{ background:var(--ds-brand);color:#fff; }
/* Dropzone */
.rn-drop           { border:2px dashed var(--ds-line);border-radius:10px;padding:36px 20px;text-align:center;color:var(--ds-ink-muted);font-size:13px; }
.rn-drop i         { font-size:36px;display:block;margin-bottom:8px;color:var(--ds-brand); }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Roll Numbers" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Roll Numbers']">
        <div id="rnBulkActions" class="d-none d-flex gap-2">
            <button type="button" class="rn-bulk-btn-del" id="rnDeleteAll">Delete Selected</button>
            <button type="button" class="rn-bulk-btn-freeze" id="rnFreezeAll">Freeze Selected</button>
            <button type="button" class="rn-bulk-btn-notify" id="rnNotifyAll">Notify Selected</button>
        </div>
        <button type="button" class="btn btn-outline-primary rounded-3 px-3 py-2" id="rnImportBtn"
                style="font-size:13px;font-weight:600;border-width:1.5px">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add or Import Roll Number
        </button>
        <button type="button" class="btn coe-btn-header" id="rnGenerateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Generate Roll Number</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Scope">
            <a href="{{ route('admin.coe.roll-number.index') }}"
               class="coe-tab {{ $scope === 'all' ? 'is-active' : '' }}"
               @if($scope==='all') aria-current="page" @endif>All</a>
            <a href="{{ route('admin.coe.roll-number.index', ['scope' => 'generated']) }}"
               class="coe-tab {{ $scope === 'generated' ? 'is-active' : '' }}"
               @if($scope==='generated') aria-current="page" @endif>Generated</a>
            <a href="{{ route('admin.coe.roll-number.index', ['scope' => 'frozen']) }}"
               class="coe-tab {{ $scope === 'frozen' ? 'is-active' : '' }}"
               @if($scope==='frozen') aria-current="page" @endif>Frozen</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="rnDownloadBtn" class="btn programme-dt-btn-columns" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="rnPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar. Every filter matches a <tr data-rn-*> key, so it filters on the
                 record's id rather than on display text that may repeat. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterCourse" class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($filterCourses as $pk => $name)<option value="{{ $pk }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterService" class="form-select" aria-label="Service Name">
                            <option value="">Service Name</option>
                            @foreach ($filterServices as $pk => $name)<option value="{{ $pk }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterCadre" class="form-select" aria-label="Cadre">
                            <option value="">Cadre</option>
                            @foreach ($filterCadres as $pk => $name)<option value="{{ $pk }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterBatch" class="form-select" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($filterBatches as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                        </select>
                    </div>
                    <button type="button" id="rnResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#rnColumnVisibilityModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="rnDtSearch" class="programme-dt-search d-none" data-dt-search-for="rnTable"></div>
                    <button type="button" id="rnSearchToggle" class="coe-search-toggle"
                            aria-controls="rnDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="rnTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="rnCheckAll" class="form-check-input" style="width:16px;height:16px;accent-color:#004384" aria-label="Select all"></th>
                                <th>S.No</th>
                                <th>Name</th>
                                <th>Service</th>
                                <th>Cadre</th>
                                <th>Gender</th>
                                <th>OT Code</th>
                                <th>Roll Number</th>
                                <th class="coe-col-center">Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                            <tr data-coe-id="{{ $row['student_pk'] }}"
                                data-rn-id="{{ $row['roll_pk'] }}"
                                data-rn-state="{{ $row['state'] }}"
                                data-rn-name="{{ $row['name'] }}"
                                data-rn-ot="{{ $row['ot_code'] }}"
                                data-rn-roll="{{ $row['roll_number'] }}"
                                data-rn-prefix="{{ $row['prefix'] }}"
                                data-rn-number="{{ $row['number'] }}"
                                data-rn-course="{{ $row['course_pk'] }}"
                                data-rn-batch="{{ $row['batch'] }}"
                                data-rn-service="{{ $row['service_pk'] }}"
                                data-rn-cadre="{{ $row['cadre_pk'] }}">
                                <td><input type="checkbox" class="rn-row-check form-check-input" style="width:16px;height:16px;accent-color:#004384" aria-label="Select row"></td>
                                <td></td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['service'] }}</td>
                                <td>{{ $row['cadre'] }}</td>
                                <td>{{ $row['gender'] }}</td>
                                <td>{{ $row['ot_code'] }}</td>
                                <td class="rn-cell-roll">{{ $row['roll_number'] ?? '—' }}</td>
                                <td class="coe-col-center"><span class="coe-state"></span></td>
                                <td class="rn-cell-actions"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="rnTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Generate Roll Number modal ───────────────────────────────────────── --}}
<div class="modal fade" id="rnGenerateModal" tabindex="-1" aria-labelledby="rnGenerateModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="rnGenerateForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="rnGenerateModalLabel">Generate Roll Number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="coe-form-label" for="rnGenCourse">Course Name<span class="coe-req">*</span></label>
                            <select id="rnGenCourse" class="form-select coe-control rn-course-select" data-batch-target="#rnGenBatch">
                                <option value="">Select Course</option>
                                @foreach ($courses as $c)<option value="{{ $c->pk }}">{{ $c->course_name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="coe-form-label" for="rnGenBatch">Batch<span class="coe-req">*</span></label>
                            <select id="rnGenBatch" class="form-select coe-control">
                                <option value="">Select Batch</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="coe-form-label" for="rnGenPrefix">Prefix<span class="coe-req">*</span></label>
                            <input type="text" id="rnGenPrefix" class="form-control coe-control" placeholder="eg. FC101" maxlength="20" autocomplete="off">
                        </div>
                        <div class="col-6" id="rnGenStartWrap">
                            <label class="coe-form-label" for="rnGenStart">Starting Number<span class="coe-req">*</span></label>
                            <input type="text" id="rnGenStart" class="form-control coe-control" placeholder="eg. 0001" maxlength="9" autocomplete="off" inputmode="numeric">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="coe-form-label" for="rnGenStrategy">Allocation Strategy<span class="coe-req">*</span></label>
                        <select id="rnGenStrategy" class="form-select coe-control">
                            @foreach ($strategies as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    {{-- Allocation Rules checkboxes — visible only for strategies that honour them --}}
                    <div id="rnAllocRulesBox" class="rn-alloc-rules">
                        <div class="fw-semibold mb-2" style="font-size:13px">Allocation Rules</div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="rnRuleService" value="avoid_same_service" checked>
                            <label class="form-check-label" for="rnRuleService">Avoid same-service sitting together</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rnRuleGender" value="alternate_gender">
                            <label class="form-check-label" for="rnRuleGender">Alternate female/male where possible</label>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Generating again for a course and batch replaces its existing roll numbers, unless any of them is frozen.</p>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Generate Roll Number</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Edit Roll Number modal ───────────────────────────────────────────── --}}
<div class="modal fade" id="rnEditModal" tabindex="-1" aria-labelledby="rnEditModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="rnEditForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="rnEditModalLabel">Edit Roll Number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="mb-3">
                        <label class="coe-form-label" for="rnEditOt">OT Name<span class="coe-req">*</span></label>
                        <select id="rnEditOt" class="form-select coe-control" disabled>
                            <option value="">Select OT</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="coe-form-label" for="rnEditCourse">Course Name<span class="coe-req">*</span></label>
                        <select id="rnEditCourse" class="form-select coe-control" disabled>
                            <option value="">Select Course</option>
                            @foreach ($courses as $c)<option value="{{ $c->pk }}">{{ $c->course_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="coe-form-label" for="rnEditPrefix">Prefix<span class="coe-req">*</span></label>
                            <input type="text" id="rnEditPrefix" class="form-control coe-control" maxlength="20" autocomplete="off">
                        </div>
                        <div class="col-6">
                            <label class="coe-form-label" for="rnEditStartNum">Number<span class="coe-req">*</span></label>
                            <input type="text" id="rnEditStartNum" class="form-control coe-control" maxlength="9" autocomplete="off" inputmode="numeric">
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Update Roll Number</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Add or Import Roll Number modal ─────────────────────────────────── --}}
<div class="modal fade" id="rnImportModal" tabindex="-1" aria-labelledby="rnImportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header coe-modal-header--rule">
                <h5 class="modal-title" id="rnImportModalLabel">Import Roll Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body">
                {{-- Tabs --}}
                <div class="rn-imp-tabs mb-3">
                    <div class="rn-imp-tab is-active" data-tab="bulk">Import In Bulk</div>
                    <div class="rn-imp-tab" data-tab="single">Add Single Record</div>
                </div>

                {{-- Bulk pane --}}
                <div id="rnImpBulk">
                    <div class="rn-drop mb-3">
                        <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                        Drop file here<br>
                        <span style="font-size:11px">File type: .xlsx, .xls, .csv | Max size: 5 MB</span><br>
                        <span class="text-muted" style="font-size:12px">Or</span>
                        <div class="mt-2">
                            <label class="btn coe-btn coe-btn-primary coe-btn--narrow" for="rnFileInput">
                                <i class="bi bi-upload me-1" aria-hidden="true"></i>Upload
                            </label>
                            <input type="file" id="rnFileInput" class="d-none" accept=".xlsx,.xls,.csv">
                        </div>
                        <div id="rnFileName" class="mt-2 fw-semibold text-body" style="font-size:12px"></div>
                    </div>
                    <div>
                        <strong style="font-size:13px">Instructions &amp; Excel Template</strong>
                        <ul class="mt-2 ps-3" style="font-size:13px">
                            <li><a href="{{ route('admin.coe.roll-number.template') }}" id="rnTemplateLink" class="text-primary">Download the template file</a> — it lists the participants (of the Course and Batch picked in the filters, when set); fill in the Roll Number column only and keep the Participant ID column unchanged.</li>
                            <li class="text-muted">Tip: Make sure your Excel file matches the template format exactly to avoid import errors. Frozen roll numbers are not changed by an import.</li>
                        </ul>
                    </div>
                </div>

                {{-- Single Record pane --}}
                <div id="rnImpSingle" class="d-none">
                    <form id="rnSingleForm" novalidate>
                        <div class="row g-3 mb-3">
                            <div class="col-8">
                                <label class="coe-form-label" for="rnSingleCourse">Course Name<span class="coe-req">*</span></label>
                                <select id="rnSingleCourse" class="form-select coe-control rn-course-select" data-batch-target="#rnSingleBatch">
                                    <option value="">Select Course</option>
                                    @foreach ($courses as $c)<option value="{{ $c->pk }}">{{ $c->course_name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="coe-form-label" for="rnSingleBatch">Batch<span class="coe-req">*</span></label>
                                <select id="rnSingleBatch" class="form-select coe-control">
                                    <option value="">Select Batch</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="coe-form-label" for="rnSingleOt">OT Name<span class="coe-req">*</span></label>
                            <select id="rnSingleOt" class="form-select coe-control">
                                <option value="">Select OT</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="coe-form-label" for="rnSinglePrefix">Prefix<span class="coe-req">*</span></label>
                                <input type="text" id="rnSinglePrefix" class="form-control coe-control" placeholder="FC101" maxlength="20" autocomplete="off">
                            </div>
                            <div class="col-6">
                                <label class="coe-form-label" for="rnSingleStart">Number<span class="coe-req">*</span></label>
                                <input type="text" id="rnSingleStart" class="form-control coe-control" placeholder="0001" maxlength="9" autocomplete="off" inputmode="numeric">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer coe-modal-footer">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="rnImportSave">Import Roll Numbers</button>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'rnDeleteModal', 'confirmId' => 'rnDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="rnColumnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="rnColumnToggleGrid"></div>
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

    var SCOPE = @json($scope);
    var COURSE_BATCHES = @json((object) $courseBatches);
    var RULE_STRATEGIES = @json($ruleStrategies);
    var OT_CODE_STRATEGY = @json($otCodeStrategy);
    var ROUTES = {
        generate: @json(route('admin.coe.roll-number.generate')),
        single: @json(route('admin.coe.roll-number.single')),
        import: @json(route('admin.coe.roll-number.import')),
        template: @json(route('admin.coe.roll-number.template')),
        participants: @json(route('admin.coe.roll-number.participants')),
        freeze: @json(route('admin.coe.roll-number.freeze')),
        notify: @json(route('admin.coe.roll-number.notify')),
        bulkDestroy: @json(route('admin.coe.roll-number.bulk-destroy')),
        // __ID__ is swapped for coe_roll_numbers.pk.
        row: @json(route('admin.coe.roll-number.update', ['rollNumber' => 0])).replace(/\/0$/, '/__ID__')
    };
    var STATE = {
        not_generated: { label: 'Not Generated', cls: 'rn-status-none' },
        generated: { label: 'Pending', cls: 'rn-status-pending' },
        frozen: { label: 'Frozen', cls: 'rn-status-frozen' }
    };

    /* col indexes */
    var COL = { chk:0, sno:1, name:2, service:3, cadre:4, gender:5, ot:6, roll:7, status:8, action:9 };

    var $table  = $('#rnTable');
    var $checkAll = $('#rnCheckAll');

    /* ── AJAX helpers ─────────────────────────────────────────────────── */
    function csrf() { return $('meta[name="csrf-token"]').attr('content') || ''; }

    function send(url, data, opts) {
        return $.ajax($.extend({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }, opts || {}));
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

    function busy($btn, on) { $btn.prop('disabled', !!on); }

    /* ── Paint state-dependent cells ─────────────────────────────────── */
    function paintRow($row) {
        var state = $row.attr('data-rn-state');
        var s = STATE[state] || STATE.not_generated;
        $row.find('.coe-state').attr('class', 'coe-state ' + s.cls).text(s.label);
        $row.find('.rn-cell-roll').text($row.attr('data-rn-roll') || '—');
        // Nothing to freeze, notify or delete on a participant without a number.
        $row.find('.rn-row-check').prop('disabled', state === 'not_generated')
            .prop('checked', false);

        var html = '<div class="rn-act-group">';
        if (state === 'generated') {
            html += '<button type="button" class="rn-act rn-act--edit" title="Edit"><i class="bi bi-pencil-square"></i><span>Edit</span></button>' +
                '<button type="button" class="rn-act rn-act--del"  title="Delete"><i class="bi bi-trash3"></i><span>Delete</span></button>' +
                '<button type="button" class="rn-act rn-act--freeze" title="Freeze"><i class="bi bi-snow2"></i><span>Freeze</span></button>';
        } else if (state === 'frozen') {
            // Frozen numbers can no longer be edited or deleted — the server refuses both.
            html += '<button type="button" class="rn-act rn-act--notify" title="Notify"><i class="bi bi-bell"></i><span>Notify</span></button>';
        } else {
            html += '<button type="button" class="rn-act rn-act--assign" title="Assign a roll number"><i class="bi bi-plus-square"></i><span>Assign</span></button>';
        }
        html += '</div>';
        $row.find('.rn-cell-actions').html(html);
    }
    $table.find('tbody tr').each(function () { paintRow($(this)); });

    /* ── DataTable ────────────────────────────────────────────────────── */
    var dt = CoeGrid.init({
        table: '#rnTable',
        title: 'Roll Numbers',
        snoCol: COL.sno,
        order: [],
        noSort: [COL.chk, COL.sno, COL.action],
        exclude: [COL.chk, COL.action],
        rowFilters: {
            '#rnFilterCourse':  'rn-course',
            '#rnFilterService': 'rn-service',
            '#rnFilterCadre':   'rn-cadre',
            '#rnFilterBatch':   'rn-batch'
        },
        reset: '#rnResetFilters',
        download: '#rnDownloadBtn',
        print: '#rnPrintBtn',
        searchToggle: '#rnSearchToggle',
        searchSlot: '#rnDtSearch',
        colvisGrid: '#rnColumnToggleGrid',
        colvisKey: 'coeRollNumber:hiddenColumns:v1',
        empty: { title: 'No Roll Numbers', text: 'Generate or import roll numbers to get started.' }
    });

    function allRows() { return $(dt.rows().nodes()); }

    /* Apply a server-confirmed change to one row. */
    function setState($row, state, roll) {
        if (state === 'not_generated') {
            $row.attr({ 'data-rn-id': '', 'data-rn-roll': '', 'data-rn-prefix': '', 'data-rn-number': '' });
        }
        if (roll !== undefined) { $row.attr('data-rn-roll', roll); }
        $row.attr('data-rn-state', state);
        paintRow($row);
        dt.row($row).invalidate('dom');
    }

    function rowsById(ids) {
        var want = (ids || []).map(String);
        return allRows().filter(function () { return want.indexOf(this.getAttribute('data-rn-id')) !== -1; });
    }

    function afterDelete(ids) {
        rowsById(ids).each(function () {
            var $row = $(this);
            // On "All" the participant is still on the course, now without a number.
            if (SCOPE === 'all') { setState($row, 'not_generated'); }
            else { dt.row($row).remove(); }
        });
        dt.draw(false);
        syncBulk();
    }

    function afterFreeze(ids) {
        rowsById(ids).each(function () {
            var $row = $(this);
            if (SCOPE === 'generated') { dt.row($row).remove(); }
            else { setState($row, 'frozen'); }
        });
        dt.draw(false);
        syncBulk();
    }

    /* ── Checkbox logic & bulk actions ───────────────────────────────── */
    function selectedIds() {
        return allRows().filter(function () { return $(this).find('.rn-row-check').is(':checked'); })
            .map(function () { return this.getAttribute('data-rn-id'); }).get()
            .filter(function (id) { return id; });
    }

    function syncBulk() {
        var $boxes = allRows().find('.rn-row-check:enabled');
        var checked = $boxes.filter(':checked').length;
        $('#rnBulkActions').toggleClass('d-none', checked === 0).toggleClass('d-flex', checked > 0);
        $checkAll.prop('indeterminate', checked > 0 && checked < $boxes.length);
        $checkAll.prop('checked', checked > 0 && checked === $boxes.length);
    }

    $checkAll.on('change', function () {
        $(dt.rows({ search: 'applied' }).nodes()).find('.rn-row-check:enabled').prop('checked', this.checked);
        syncBulk();
    });
    $table.on('change', '.rn-row-check', syncBulk);

    function confirmThen(opts, fn) {
        Swal.fire($.extend({
            showCancelButton: true, cancelButtonText: 'Cancel'
        }, opts)).then(function (r) { if (r.isConfirmed) { fn(); } });
    }

    $('#rnDeleteAll').on('click', function () {
        var ids = selectedIds();
        if (!ids.length) { return; }
        confirmThen({
            icon: 'warning', title: 'Confirm Delete?',
            text: 'Delete ' + ids.length + " selected roll number(s)? Frozen ones are kept. This action can't be undone.",
            iconColor: '#dc3545', confirmButtonText: 'Yes, Delete', confirmButtonColor: '#dc3545'
        }, function () {
            send(ROUTES.bulkDestroy, { ids: ids }).done(function (res) {
                afterDelete(res.deleted_ids);
                Swal.fire({ icon: 'success', title: 'Deleted', text: res.message });
            }).fail(showError);
        });
    });

    function freezeIds(ids) {
        confirmThen({
            icon: 'info', title: 'Confirm Freeze?',
            text: 'Freeze ' + ids.length + " roll number(s)? Frozen roll numbers can no longer be edited, deleted or regenerated. This action can't be undone.",
            iconColor: '#17a2b8', confirmButtonText: 'Yes, Freeze', confirmButtonColor: '#198754'
        }, function () {
            send(ROUTES.freeze, { ids: ids }).done(function (res) {
                afterFreeze(res.frozen_ids);
                Swal.fire({ icon: 'success', title: 'Frozen', text: res.message });
            }).fail(showError);
        });
    }

    function notifyIds(ids) {
        confirmThen({
            icon: 'question', title: 'Send Notification?',
            text: 'Notify the OT(s) of their frozen roll number? Roll numbers that are not frozen are skipped.',
            confirmButtonText: 'Yes, Notify', confirmButtonColor: '#004384'
        }, function () {
            send(ROUTES.notify, { ids: ids }).done(function (res) {
                Swal.fire({ icon: res.no_account || res.skipped_not_frozen || res.failed ? 'info' : 'success',
                    title: 'Notification Sent', text: res.message });
            }).fail(showError);
        });
    }

    $('#rnFreezeAll').on('click', function () { var ids = selectedIds(); if (ids.length) { freezeIds(ids); } });
    $('#rnNotifyAll').on('click', function () { var ids = selectedIds(); if (ids.length) { notifyIds(ids); } });

    /* ── Row-level delete ─────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#rnTable',
        trigger: '.rn-act--del',
        modal: '#rnDeleteModal',
        confirm: '#rnDeleteConfirm',
        onConfirm: function ($row) {
            var id = $row.attr('data-rn-id');
            send(ROUTES.row.replace('__ID__', id), { _method: 'DELETE' }).done(function (res) {
                afterDelete(res.deleted_ids);
                Swal.fire({ icon: 'success', title: 'Deleted', text: res.message });
            }).fail(showError);
        }
    });

    /* ── Row-level freeze / notify — a plain confirm, then the server ─── */
    $table.on('click', '.rn-act--freeze', function () {
        freezeIds([$(this).closest('tr').attr('data-rn-id')]);
    });
    $table.on('click', '.rn-act--notify', function () {
        notifyIds([$(this).closest('tr').attr('data-rn-id')]);
    });

    /* ── Course → Batch pickers ───────────────────────────────────────── */
    function fillBatches($batch, courseId, selected) {
        var list = COURSE_BATCHES[courseId] || [];
        $batch.empty().append('<option value="">Select Batch</option>');
        list.forEach(function (b) { $batch.append($('<option>').val(b).text(b)); });
        if (selected) { $batch.val(String(selected)); }
        else if (list.length === 1) { $batch.val(String(list[0])); }
        $batch.trigger('change');
    }
    $('.rn-course-select').on('change', function () {
        fillBatches($($(this).data('batch-target')), this.value);
    });

    /* ── Generate modal ───────────────────────────────────────────────── */
    var $genModal    = $('#rnGenerateModal');
    var $genStrategy = $('#rnGenStrategy');
    var $allocBox    = $('#rnAllocRulesBox');

    function syncStrategy() {
        var key = $genStrategy.val();
        $allocBox.toggleClass('d-none', RULE_STRATEGIES.indexOf(key) === -1);
        // Sequential by OT Code takes each serial from the OT code — no start to ask for.
        $('#rnGenStartWrap').toggleClass('d-none', key === OT_CODE_STRATEGY);
    }
    $genStrategy.on('change', syncStrategy);
    syncStrategy();

    $genModal.find('.coe-control').each(function () {
        if (this.tagName === 'SELECT') { $(this).select2({ width: '100%', dropdownParent: $genModal.find('.modal-body') }); }
    });

    $('#rnGenerateBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($genModal[0]).show();
    });

    $('#rnGenerateForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $(this).find('[type=submit]');
        var data = {
            course: $('#rnGenCourse').val(),
            batch: $('#rnGenBatch').val(),
            prefix: $.trim($('#rnGenPrefix').val()),
            start_number: $genStrategy.val() === OT_CODE_STRATEGY ? '' : $.trim($('#rnGenStart').val()),
            strategy: $genStrategy.val(),
            rules: $allocBox.hasClass('d-none') ? [] : $allocBox.find('input:checked').map(function () { return this.value; }).get()
        };
        if (!data.course || !data.batch) {
            Swal.fire({ icon: 'warning', title: 'Missing details', text: 'Select a course and a batch.' });
            return;
        }
        busy($btn, true);
        send(ROUTES.generate, data).done(function (res) {
            bootstrap.Modal.getOrCreateInstance($genModal[0]).hide();
            Swal.fire({ icon: 'success', title: 'Generated', text: res.message })
                .then(function () { window.location.reload(); });
        }).fail(showError).always(function () { busy($btn, false); });
    });

    /* ── Edit modal ───────────────────────────────────────────────────── */
    var $editModal = $('#rnEditModal');
    var $editing = null;
    $editModal.find('select').each(function () {
        $(this).select2({ width: '100%', dropdownParent: $editModal.find('.modal-body') });
    });

    $table.on('click', '.rn-act--edit', function () {
        $editing = $(this).closest('tr');
        var ot = $editing.attr('data-rn-ot') + ' – ' + $editing.attr('data-rn-name');
        $('#rnEditOt').empty().append($('<option selected>').val($editing.attr('data-coe-id')).text(ot))
            .trigger('change.select2');
        $('#rnEditCourse').val($editing.attr('data-rn-course')).trigger('change.select2');
        $('#rnEditPrefix').val($editing.attr('data-rn-prefix') || '');
        $('#rnEditStartNum').val($editing.attr('data-rn-number') || '');
        bootstrap.Modal.getOrCreateInstance($editModal[0]).show();
    });
    $editModal.on('hidden.bs.modal', function () { $editing = null; });

    $('#rnEditForm').on('submit', function (e) {
        e.preventDefault();
        if (!$editing) { return; }
        var $row = $editing;
        var $btn = $(this).find('[type=submit]');
        var prefix = $.trim($('#rnEditPrefix').val());
        var number = $.trim($('#rnEditStartNum').val());
        busy($btn, true);
        send(ROUTES.row.replace('__ID__', $row.attr('data-rn-id')), { _method: 'PUT', prefix: prefix, number: number })
            .done(function (res) {
                $row.attr({ 'data-rn-prefix': prefix, 'data-rn-number': number });
                setState($row, 'generated', res.roll_number);
                dt.draw(false);
                bootstrap.Modal.getOrCreateInstance($editModal[0]).hide();
                Swal.fire({ icon: 'success', title: 'Updated', text: res.message });
            }).fail(showError).always(function () { busy($btn, false); });
    });

    /* ── Import/Add modal ─────────────────────────────────────────────── */
    var $impModal = $('#rnImportModal');
    var $saveBtn = $('#rnImportSave');
    var $file = $('#rnFileInput');
    var chosenFile = null;
    var activeTab = 'bulk';

    $impModal.find('select').each(function () {
        $(this).select2({ width: '100%', dropdownParent: $impModal.find('.modal-body') });
    });

    function templateHref() {
        var q = {};
        if ($('#rnFilterCourse').val()) { q.course = $('#rnFilterCourse').val(); }
        if ($('#rnFilterBatch').val()) { q.batch = $('#rnFilterBatch').val(); }
        var qs = $.param(q);
        return ROUTES.template + (qs ? '?' + qs : '');
    }

    function showTab(tab) {
        activeTab = tab;
        $('.rn-imp-tab').removeClass('is-active').filter('[data-tab="' + tab + '"]').addClass('is-active');
        $('#rnImpBulk').toggleClass('d-none', tab !== 'bulk');
        $('#rnImpSingle').toggleClass('d-none', tab !== 'single');
        $saveBtn.text(tab === 'bulk' ? 'Import Roll Numbers' : 'Add Roll Number');
    }

    $('#rnImportBtn').on('click', function () {
        $('#rnTemplateLink').attr('href', templateHref());
        bootstrap.Modal.getOrCreateInstance($impModal[0]).show();
    });

    $('.rn-imp-tab').on('click', function () { showTab($(this).data('tab')); });

    function chooseFile(f) {
        chosenFile = f || null;
        $('#rnFileName').text(chosenFile ? chosenFile.name : '');
    }
    $file.on('change', function () { chooseFile(this.files[0]); });

    /* Drag-and-drop */
    var $drop = $impModal.find('.rn-drop');
    $drop.on('dragover', function (e) { e.preventDefault(); $(this).css('border-color', '#004384'); });
    $drop.on('dragleave', function () { $(this).css('border-color', ''); });
    $drop.on('drop', function (e) {
        e.preventDefault();
        $(this).css('border-color', '');
        var dtf = e.originalEvent.dataTransfer;
        if (dtf && dtf.files && dtf.files[0]) { chooseFile(dtf.files[0]); }
    });

    /* Single record: OTs of the chosen course + batch, numbered ones shown with their number. */
    var pendingOt = null;
    $('#rnSingleBatch').on('change', function () {
        var $ot = $('#rnSingleOt');
        var course = $('#rnSingleCourse').val();
        var batch = this.value;
        $ot.empty().append('<option value="">Select OT</option>').trigger('change.select2');
        if (!course || !batch) { return; }
        $.getJSON(ROUTES.participants, { course: course, batch: batch }).done(function (res) {
            (res.data || []).forEach(function (p) {
                var text = p.label + (p.roll_number ? ' (' + p.roll_number + (p.frozen ? ', frozen' : '') + ')' : '');
                $ot.append($('<option>').val(p.pk).text(text).prop('disabled', !!p.frozen));
            });
            if (pendingOt) { $ot.val(String(pendingOt)); pendingOt = null; }
            $ot.trigger('change.select2');
        }).fail(showError);
    });

    /* "Assign" on a participant without a number opens Add Single Record on them. */
    $table.on('click', '.rn-act--assign', function () {
        var $row = $(this).closest('tr');
        showTab('single');
        pendingOt = $row.attr('data-coe-id');
        $('#rnSingleCourse').val($row.attr('data-rn-course')).trigger('change.select2');
        fillBatches($('#rnSingleBatch'), $row.attr('data-rn-course'), $row.attr('data-rn-batch'));
        $('#rnTemplateLink').attr('href', templateHref());
        bootstrap.Modal.getOrCreateInstance($impModal[0]).show();
    });

    $saveBtn.on('click', function () {
        var req;
        if (activeTab === 'bulk') {
            if (!chosenFile) {
                Swal.fire({ icon: 'warning', title: 'No file', text: 'Choose a file to import.' });
                return;
            }
            var fd = new FormData();
            fd.append('file', chosenFile);
            req = send(ROUTES.import, fd, { processData: false, contentType: false });
        } else {
            req = send(ROUTES.single, {
                course: $('#rnSingleCourse').val(),
                batch: $('#rnSingleBatch').val(),
                student_master_pk: $('#rnSingleOt').val(),
                prefix: $.trim($('#rnSinglePrefix').val()),
                number: $.trim($('#rnSingleStart').val())
            });
        }
        busy($saveBtn, true);
        req.done(function (res) {
            bootstrap.Modal.getOrCreateInstance($impModal[0]).hide();
            // An import that also had failing rows still reports them in the message.
            Swal.fire({ icon: res.failed ? 'warning' : 'success', title: activeTab === 'bulk' ? 'Import finished' : 'Added', text: res.message })
                .then(function () { window.location.reload(); });
        }).fail(showError).always(function () { busy($saveBtn, false); });
    });

    $impModal.on('hidden.bs.modal', function () {
        chooseFile(null);
        $file.val('');
        pendingOt = null;
        showTab('bulk');
    });
});
</script>
@endpush
