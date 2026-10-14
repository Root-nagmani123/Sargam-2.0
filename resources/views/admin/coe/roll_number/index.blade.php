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
/* Row-level action icons */
.rn-act            { display:inline-flex;flex-direction:column;align-items:center;gap:2px;background:none;border:none;padding:0 5px;cursor:pointer;font-size:11px;line-height:1.2; }
.rn-act i          { font-size:16px; }
.rn-act--edit i    { color:#004384; }
.rn-act--del i     { color:#dc3545; }
.rn-act--notify i  { color:#6c757d; }
.rn-act--freeze i  { color:#17a2b8; }
.rn-act:hover i    { opacity:.75; }
.rn-act-group      { display:flex;align-items:center;gap:2px; }
/* Bulk header buttons */
.rn-bulk-btn-del   { background:#dc3545;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:13px;font-weight:600; }
.rn-bulk-btn-del:hover{ background:#b02a37; }
.rn-bulk-btn-freeze{ background:#198754;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:13px;font-weight:600; }
.rn-bulk-btn-freeze:hover{ background:#146c43; }
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
            <button type="button" class="rn-bulk-btn-del" id="rnDeleteAll">Delete All</button>
            <button type="button" class="rn-bulk-btn-freeze" id="rnFreezeAll">Freeze All</button>
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

            {{-- Toolbar --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterCourse" class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterService" class="form-select" aria-label="Service Name">
                            <option value="">Service Name</option>
                            @foreach ($services as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterExam" class="form-select" aria-label="Examination">
                            <option value="">Examination</option>
                            @foreach ($examinations as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterCadre" class="form-select" aria-label="Cadre">
                            <option value="">Cadre</option>
                            @foreach ($cadres as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rnFilterBatch" class="form-select" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
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
                                <th><input type="checkbox" id="rnCheckAll" class="form-check-input" style="width:16px;height:16px;accent-color:#004384"></th>
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
                            @php $isFrozen = $row['status'] === 'Frozen'; @endphp
                            <tr data-coe-id="{{ $row['id'] }}"
                                data-rn-name="{{ $row['name'] }}"
                                data-rn-service="{{ $row['service'] }}"
                                data-rn-cadre="{{ $row['cadre'] }}"
                                data-rn-gender="{{ $row['gender'] }}"
                                data-rn-ot="{{ $row['ot_code'] }}"
                                data-rn-roll="{{ $row['roll_number'] }}"
                                data-rn-status="{{ $row['status'] }}"
                                data-rn-course="{{ $row['course'] }}">
                                <td><input type="checkbox" class="rn-row-check form-check-input" style="width:16px;height:16px;accent-color:#004384"></td>
                                <td></td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['service'] }}</td>
                                <td>{{ $row['cadre'] }}</td>
                                <td>{{ $row['gender'] }}</td>
                                <td>{{ $row['ot_code'] }}</td>
                                <td>{{ $row['roll_number'] }}</td>
                                <td class="coe-col-center">
                                    <span class="coe-state {{ $isFrozen ? 'rn-status-frozen' : 'rn-status-pending' }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
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
                    <div class="mb-3">
                        <label class="coe-form-label" for="rnGenCourse">Course Name<span class="coe-req">*</span></label>
                        <select id="rnGenCourse" class="form-select coe-control">
                            <option value="">Select Course</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="coe-form-label" for="rnGenPrefix">Prefix<span class="coe-req">*</span></label>
                            <input type="text" id="rnGenPrefix" class="form-control coe-control" placeholder="eg. FC101" maxlength="20" autocomplete="off">
                        </div>
                        <div class="col-6">
                            <label class="coe-form-label" for="rnGenStart">Starting Number<span class="coe-req">*</span></label>
                            <input type="text" id="rnGenStart" class="form-control coe-control" placeholder="eg. 0001" maxlength="10" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="coe-form-label" for="rnGenStrategy">Allocation Strategy<span class="coe-req">*</span></label>
                        <select id="rnGenStrategy" class="form-select coe-control">
                            @foreach ($strategies as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                        </select>
                    </div>
                    {{-- Allocation Rules checkboxes — visible only when strategy = Allocation Rules --}}
                    <div id="rnAllocRulesBox" class="rn-alloc-rules">
                        <div class="fw-semibold mb-2" style="font-size:13px">Allocation Rules</div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="rnRuleService" checked>
                            <label class="form-check-label" for="rnRuleService">Avoid same-service sitting together</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="rnRuleCadre" checked>
                            <label class="form-check-label" for="rnRuleCadre">Avoid consecutive rolls for same Cadre</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rnRuleGender">
                            <label class="form-check-label" for="rnRuleGender">Alternate female/male where possible</label>
                        </div>
                    </div>
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
                        <select id="rnEditOt" class="form-select coe-control">
                            <option value="">Select OT</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="coe-form-label" for="rnEditCourse">Course Name<span class="coe-req">*</span></label>
                        <select id="rnEditCourse" class="form-select coe-control">
                            <option value="">Select Course</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="coe-form-label" for="rnEditPrefix">Prefix<span class="coe-req">*</span></label>
                            <input type="text" id="rnEditPrefix" class="form-control coe-control" maxlength="20" autocomplete="off">
                        </div>
                        <div class="col-6">
                            <label class="coe-form-label" for="rnEditStartNum">Starting Number<span class="coe-req">*</span></label>
                            <input type="text" id="rnEditStartNum" class="form-control coe-control" maxlength="10" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Generate Roll Number</button>
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
                    </div>
                    <div>
                        <strong style="font-size:13px">Instructions &amp; Excel Template</strong>
                        <ul class="mt-2 ps-3" style="font-size:13px">
                            <li><a href="#" class="text-primary">Download the template file</a> to ensure your Excel file has the correct format for importing members.</li>
                            <li class="text-muted">Tip: Make sure your Excel file matches the template format exactly to avoid import errors.</li>
                        </ul>
                    </div>
                </div>

                {{-- Single Record pane --}}
                <div id="rnImpSingle" class="d-none">
                    <form id="rnSingleForm" novalidate>
                        <div class="mb-3">
                            <label class="coe-form-label" for="rnSingleCourse">Course Name<span class="coe-req">*</span></label>
                            <select id="rnSingleCourse" class="form-select coe-control">
                                <option value="">Select Course</option>
                                @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="coe-form-label" for="rnSingleOt">OT Name<span class="coe-req">*</span></label>
                            <select id="rnSingleOt" class="form-select coe-control">
                                <option value="">Select OT</option>
                                <option>A77 – Priya Nair</option>
                                <option>A78 – Anjali Gupta</option>
                                <option>A79 – Vikram Patel</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="coe-form-label" for="rnSinglePrefix">Prefix<span class="coe-req">*</span></label>
                                <input type="text" id="rnSinglePrefix" class="form-control coe-control" placeholder="FC101" maxlength="20" autocomplete="off">
                            </div>
                            <div class="col-6">
                                <label class="coe-form-label" for="rnSingleStart">Starting Number<span class="coe-req">*</span></label>
                                <input type="text" id="rnSingleStart" class="form-control coe-control" placeholder="0001" maxlength="10" autocomplete="off">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer coe-modal-footer">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="rnImportSave">Add Roll Number</button>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'rnDeleteModal', 'confirmId' => 'rnDeleteConfirm'])
@include('admin.coe.partials.freeze_modal', ['phoneMasked' => '+91 ******3274'])

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

    /* col indexes */
    var COL = { chk:0, sno:1, name:2, service:3, cadre:4, gender:5, ot:6, roll:7, status:8, action:9 };

    var $table  = $('#rnTable');
    var $checkAll = $('#rnCheckAll');

    /* ── Paint state-dependent action buttons ─────────────────────────── */
    function paintActions($row) {
        var status   = $row.attr('data-rn-status');
        var isFrozen = status === 'Frozen';
        var html = '<div class="rn-act-group">' +
            '<button type="button" class="rn-act rn-act--edit" title="Edit"><i class="bi bi-pencil-square"></i><span>Edit</span></button>' +
            '<button type="button" class="rn-act rn-act--del"  title="Delete"><i class="bi bi-trash3"></i><span>Delete</span></button>';
        if (isFrozen) {
            html += '<button type="button" class="rn-act rn-act--notify" title="Notify"><i class="bi bi-bell"></i><span>Notify</span></button>';
        } else {
            html += '<button type="button" class="rn-act rn-act--freeze" title="Freeze"><i class="bi bi-snow2"></i><span>Freeze</span></button>';
        }
        html += '</div>';
        $row.find('.rn-cell-actions').html(html);
    }
    $table.find('tbody tr').each(function () { paintActions($(this)); });

    /* ── DataTable ────────────────────────────────────────────────────── */
    var dt = CoeGrid.init({
        table: '#rnTable',
        title: 'Roll Numbers',
        snoCol: COL.sno,
        order: [[COL.name, 'asc']],
        noSort: [COL.chk, COL.sno, COL.action],
        exclude: [COL.chk, COL.action],
        filters: {
            '#rnFilterCourse':  COL.roll,    // course filter targets roll prefix column
            '#rnFilterService': COL.service,
            '#rnFilterCadre':   COL.cadre
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

    /* ── Checkbox logic & bulk actions ───────────────────────────────── */
    function syncBulk() {
        var checked = $table.find('.rn-row-check:checked').length;
        $('#rnBulkActions').toggleClass('d-none', checked === 0).toggleClass('d-flex', checked > 0);
        $checkAll.prop('indeterminate', checked > 0 && checked < $table.find('.rn-row-check').length);
        $checkAll.prop('checked', checked > 0 && checked === $table.find('.rn-row-check').length);
    }

    $checkAll.on('change', function () {
        $table.find('.rn-row-check').prop('checked', this.checked);
        syncBulk();
    });
    $table.on('change', '.rn-row-check', syncBulk);

    $('#rnDeleteAll').on('click', function () {
        Swal.fire({
            icon: 'warning', title: 'Confirm Delete?',
            text: "Are you sure you want to delete Roll Number? This action can't be undone.",
            iconColor: '#dc3545',
            showCancelButton: true, cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes, Delete', confirmButtonColor: '#dc3545'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $table.find('.rn-row-check:checked').each(function () {
                dt.row($(this).closest('tr')).remove();
            });
            dt.draw(false);
            syncBulk();
            Swal.fire({ icon: 'success', title: 'Deleted', text: 'Selected roll numbers deleted.' });
        });
    });

    $('#rnFreezeAll').on('click', function () {
        Swal.fire({
            icon: 'info', title: 'Confirm Freeze?',
            text: "Are you sure you want to freeze Roll Number? This action can't be undone. Once you freeze this a notification send to the respective OT's.",
            iconColor: '#17a2b8',
            showCancelButton: true, cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes, Freeze', confirmButtonColor: '#198754'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $table.find('.rn-row-check:checked').each(function () {
                var $row = $(this).closest('tr');
                $row.attr('data-rn-status', 'Frozen');
                $row.find('.coe-state').attr('class', 'coe-state rn-status-frozen').text('Frozen');
                paintActions($row);
                dt.row($row).invalidate('dom');
            });
            dt.draw(false);
            syncBulk();
            Swal.fire({ icon: 'success', title: 'Frozen', text: 'Selected roll numbers frozen.' });
        });
    });

    /* ── Row-level delete ─────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#rnTable',
        modal: '#rnDeleteModal',
        confirm: '#rnDeleteConfirm',
        onConfirm: function ($row) {
            dt.row($row).remove().draw(false);
            Swal.fire({ icon: 'success', title: 'Deleted', text: 'Roll number deleted.' });
        }
    });

    /* ── Row-level freeze ─────────────────────────────────────────────── */
    CoeGrid.freeze({
        table: '#rnTable',
        trigger: '.rn-act--freeze',
        onConfirm: function ($row) {
            $row.attr('data-rn-status', 'Frozen');
            $row.find('.coe-state').attr('class', 'coe-state rn-status-frozen').text('Frozen');
            paintActions($row);
            dt.row($row).invalidate('dom').draw(false);
            // Show "Notification Sent" toast
            Swal.fire({ icon: 'success', title: 'Notification Sent', text: 'Notification has sent to the respective OT.', timer: 2500, showConfirmButton: false });
        }
    });

    /* ── Row-level notify ─────────────────────────────────────────────── */
    $table.on('click', '.rn-act--notify', function () {
        Swal.fire({ icon: 'success', title: 'Notification Sent', text: 'Notification has sent to the respective OT.', timer: 2500, showConfirmButton: false });
    });

    /* ── Generate modal ───────────────────────────────────────────────── */
    var $genModal    = $('#rnGenerateModal');
    var $genStrategy = $('#rnGenStrategy');
    var $allocBox    = $('#rnAllocRulesBox');

    $genStrategy.on('change', function () {
        $allocBox.toggleClass('d-none', $(this).val() !== 'Allocation Rules');
    });

    $genModal.find('.coe-control').each(function () {
        if (this.tagName === 'SELECT') { $(this).select2({ width: '100%', dropdownParent: $genModal.find('.modal-body') }); }
    });

    $('#rnGenerateBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($genModal[0]).show();
    });

    $('#rnGenerateForm').on('submit', function (e) {
        e.preventDefault();
        bootstrap.Modal.getOrCreateInstance($genModal[0]).hide();
        Swal.fire({ icon: 'success', title: 'Generated', text: 'Roll numbers generated successfully.' });
    });

    /* ── Edit modal ───────────────────────────────────────────────────── */
    var $editModal = $('#rnEditModal');
    $editModal.find('select').each(function () {
        $(this).select2({ width: '100%', dropdownParent: $editModal.find('.modal-body') });
    });

    $table.on('click', '.rn-act--edit', function () {
        var $row = $(this).closest('tr');
        var ot   = $row.attr('data-rn-ot') + ' – ' + $row.attr('data-rn-name');
        var course = $row.attr('data-rn-course');
        var roll   = $row.attr('data-rn-roll');
        var parts  = roll.split('-');
        // Populate OT select with a dynamic option
        var $otSel = $('#rnEditOt');
        $otSel.empty().append('<option value="">Select OT</option>').append('<option value="' + ot + '" selected>' + ot + '</option>');
        $otSel.trigger('change.select2');
        $('#rnEditCourse').val(course).trigger('change.select2');
        $('#rnEditPrefix').val(parts[0] || '');
        $('#rnEditStartNum').val(parts[1] || '');
        bootstrap.Modal.getOrCreateInstance($editModal[0]).show();
    });

    $('#rnEditForm').on('submit', function (e) {
        e.preventDefault();
        bootstrap.Modal.getOrCreateInstance($editModal[0]).hide();
        Swal.fire({ icon: 'success', title: 'Updated', text: 'Roll number updated.' });
    });

    /* ── Import/Add modal ─────────────────────────────────────────────── */
    var $impModal = $('#rnImportModal');
    $impModal.find('select').each(function () {
        $(this).select2({ width: '100%', dropdownParent: $impModal.find('.modal-body') });
    });

    $('#rnImportBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($impModal[0]).show();
    });

    $('.rn-imp-tab').on('click', function () {
        $('.rn-imp-tab').removeClass('is-active');
        $(this).addClass('is-active');
        var tab = $(this).data('tab');
        $('#rnImpBulk, #rnImpSingle').addClass('d-none');
        $('#rnImp' + tab.charAt(0).toUpperCase() + tab.slice(1)).removeClass('d-none');
    });

    $('#rnImportSave').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($impModal[0]).hide();
        Swal.fire({ icon: 'success', title: 'Added', text: 'Roll number added.' });
    });

    /* Drag-and-drop highlight */
    var $drop = $impModal.find('.rn-drop');
    $drop.on('dragover', function (e) { e.preventDefault(); $(this).css('border-color', '#004384'); });
    $drop.on('dragleave drop', function () { $(this).css('border-color', ''); });
});
</script>
@endpush
