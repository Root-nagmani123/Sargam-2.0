@extends('admin.layouts.master')

@section('title', 'Venue Allocation')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.va-status-pending { background:#fff3cd;color:#856404; }
.va-status-frozen  { background:#d1f0e0;color:#0d6832; }
/* Row action group – icon + label stacked */
.va-act-group      { display:flex;align-items:center;gap:4px; }
.va-act            { display:inline-flex;flex-direction:column;align-items:center;gap:2px;
                     background:none;border:none;padding:0 4px;cursor:pointer;font-size:11px; }
.va-act i          { font-size:16px; }
.va-act--edit i    { color:#004384; }
.va-act--del  i    { color:#dc3545; }
.va-act--freeze i  { color:#17a2b8; }
/* Modal: readonly computed fields */
.va-readonly       { background:var(--ds-surface-2) !important;cursor:default; }
/* Allocation-rules section inside modal */
.va-alloc-rules    { margin-top:4px; }
.va-alloc-rules .form-check-label { color:#344054; }
.va-gap-box        { border:1px solid var(--ds-line);border-radius:8px;padding:12px 14px;margin-top:10px; }
/* System generated / Manual seating panel */
.va-seating        { background:#eaf1fb;border-radius:12px;padding:16px; }
.va-seating .coe-seg { background:#fff; }
.va-man-row + .va-man-row { margin-top:12px; }
.va-man-act        { display:flex;gap:.5rem;min-width:84px; }
.va-man-btn        { width:36px;height:36px;border:0;border-radius:.5rem;color:#fff;
                     display:inline-flex;align-items:center;justify-content:center; }
.va-man-btn--remove { background:var(--coe-danger); }
.va-man-btn--add    { background:var(--coe-brand); }
</style>
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Venue Allocation" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Venue Allocation']">
        <button type="button" class="btn coe-btn-header" id="vaMapBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Map Seating Plan</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Scope">
            <a href="{{ route('admin.coe.venue-allocation.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.venue-allocation.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex gap-2 coe-secondary-actions">
            <button type="button" id="vaDownloadBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="vaPrintBtn" class="btn programme-dt-btn-columns">
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
@php
    $vaFilters = [
        'vaFilterCourse' => ['Course Name', 'course'],
        'vaFilterCategory' => ['Candidate Category', 'category'],
        'vaFilterSubject' => ['Subject', 'subject'],
        'vaFilterComponent' => ['Component', 'component'],
    ];
@endphp
                    @foreach ($vaFilters as $filterId => [$filterLabel, $filterKey])
                    <div class="programme-dt-filter-select">
                        <select id="{{ $filterId }}" class="form-select" aria-label="{{ $filterLabel }}">
                            <option value="">{{ $filterLabel }}</option>
                            @foreach ($filterOptions[$filterKey] as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                        </select>
                    </div>
                    @endforeach
                    <button type="button" id="vaResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#vaColumnVisibilityModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="vaDtSearch" class="programme-dt-search d-none" data-dt-search-for="vaTable"></div>
                    <button type="button" id="vaSearchToggle" class="coe-search-toggle"
                            aria-controls="vaDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="vaTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Course</th>
                                <th>Candidate Category</th>
                                <th>Subject</th>
                                <th>Component</th>
                                <th>OT Count</th>
                                <th>Laptop Required</th>
                                <th>Scriber Required</th>
                                <th>Building</th>
                                <th>Floor</th>
                                <th>Room</th>
                                <th>Room Capacity</th>
                                <th>Available Laptop</th>
                                <th>Scriber Seating Available</th>
                                <th>Empty Laptop</th>
                                <th class="coe-col-center">Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                            @php $isFrozen = $row['status'] === 'Frozen'; @endphp
                            <tr data-coe-id="{{ $row['id'] }}"
                                data-va-status="{{ $row['status'] }}"
                                data-va-view-url="{{ route('admin.coe.venue-allocation.show', $row['id']) }}"
                                data-va-edit-url="{{ route('admin.coe.venue-allocation.edit', $row['id']) }}"
                                data-va-freeze-url="{{ route('admin.coe.venue-allocation.freeze', $row['id']) }}"
                                data-va-delete-url="{{ route('admin.coe.venue-allocation.destroy', $row['id']) }}">
                                <td></td>
                                <td>{{ $row['course'] }} <span class="text-muted small">({{ $row['batch'] }})</span></td>
                                <td>{{ $row['category'] }}</td>
                                <td>{{ $row['subject'] }}</td>
                                <td>{{ $row['component'] }}</td>
                                <td>{{ $row['ot_count'] }}</td>
                                <td>{{ $row['laptop_req'] }}</td>
                                <td>{{ $row['scriber_req'] }}</td>
                                <td>{{ $row['building'] }}</td>
                                <td>{{ $row['floor'] }}</td>
                                <td>{{ $row['room'] }}</td>
                                <td>{{ $row['capacity'] }}</td>
                                <td>{{ $row['avail_laptop'] }}</td>
                                <td>{{ $row['scriber_seats'] }}</td>
                                <td>{{ $row['empty_laptop'] }}</td>
                                <td class="coe-col-center">
                                    <span class="coe-state {{ $isFrozen ? 'va-status-frozen' : 'va-status-pending' }}">
                                        {{ $row['status'] }}
                                    </span>
                                    @if ($row['unallocated'] > 0)
                                        <div class="text-danger small mt-1" title="OTs of this plan who have no seat yet">{{ $row['unallocated'] }} unseated</div>
                                    @endif
                                </td>
                                <td class="va-cell-actions"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="vaTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Map Seating Plan modal ───────────────────────────────────────────── --}}
<div class="modal fade" id="vaMapModal" tabindex="-1" aria-labelledby="vaMapModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="vaMapForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="vaMapModalLabel">Map Seating Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body" id="vaMapModalBody">
                    {{-- Row 1: Course + Candidate Category --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapCourse">Course Name<span class="coe-req">*</span></label>
                            <select id="vaMapCourse" name="course_batch" class="form-select coe-control">
                                <option value="">Select Course</option>
                                @foreach ($courseBatches as $cb)<option value="{{ $cb['key'] }}">{{ $cb['label'] }}</option>@endforeach
                            </select>
                            @if ($courseBatches->isEmpty())
                                <div class="small text-muted mt-1">No course has roll numbers yet — generate them on the Roll Numbers screen first.</div>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapCategory">Candidate Category<span class="coe-req">*</span></label>
                            <select id="vaMapCategory" name="candidate_category" class="form-select coe-control">
                                <option value="">Select Category</option>
                                @foreach ($categories as $catKey => $cat)<option value="{{ $catKey }}">{{ $cat }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Row 2: Subject + Component --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapSubject">Subject<span class="coe-req">*</span></label>
                            <select id="vaMapSubject" class="form-select coe-control" disabled>
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapComponent">Component<span class="coe-req">*</span></label>
                            <select id="vaMapComponent" name="component_map_id" class="form-select coe-control" disabled>
                                <option value="">Select Component</option>
                            </select>
                        </div>
                    </div>
                    {{-- Row 3: OT Count + Laptop Required (readonly, auto-filled) --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapOtCount">OT Count<span class="coe-req">*</span></label>
                            <input type="text" id="vaMapOtCount" class="form-control coe-control va-readonly" value="-" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapLaptop">Laptop Required<span class="coe-req">*</span></label>
                            <input type="text" id="vaMapLaptop" class="form-control coe-control va-readonly" value="-" readonly>
                        </div>
                    </div>
                    {{-- No laptop-seating choice on the design: a laptop desk goes to every OT
                         who answered "Laptop Required = Yes" on OT Laptop Status. --}}
                    <input type="hidden" id="vaMapLaptopPolicy" name="laptop_requirement"
                           value="{{ \App\Models\COE\SeatPlan::LAPTOP_REQ_DECLARED_YES }}">
                    {{-- System generated / Manual seating --}}
                    <div class="va-seating mb-4">
                        <div class="coe-seg nav mb-3" role="tablist" aria-label="Seating mode">
                            <button type="button" class="coe-seg__btn active" id="vaModeSystemTab" data-mode="system"
                                    data-bs-toggle="tab" data-bs-target="#vaModeSystem" role="tab"
                                    aria-controls="vaModeSystem" aria-selected="true">System generated Seating</button>
                            <button type="button" class="coe-seg__btn" id="vaModeManualTab" data-mode="manual"
                                    data-bs-toggle="tab" data-bs-target="#vaModeManual" role="tab"
                                    aria-controls="vaModeManual" aria-selected="false">Manual Seating</button>
                        </div>
                        @if ($buildings->isEmpty())
                            <div class="small text-muted mb-2">No examination rooms yet — add rooms with a building, floor and capacity in Room Master.</div>
                        @endif
                        <div class="tab-content">
                            {{-- System generated: any number of buildings → floors → rooms --}}
                            <div class="tab-pane fade show active" id="vaModeSystem" role="tabpanel" aria-labelledby="vaModeSystemTab">
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6">
                                        <label class="coe-form-label" for="vaMapBuilding">Building Name<span class="coe-req">*</span></label>
                                        <select id="vaMapBuilding" class="form-select coe-control" multiple data-placeholder="Select Building">
                                            @foreach ($buildings as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="coe-form-label" for="vaMapFloor">Floor<span class="coe-req">*</span></label>
                                        <select id="vaMapFloor" class="form-select coe-control" multiple data-placeholder="Select Floor" disabled></select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="coe-form-label" for="vaMapRoom">Room<span class="coe-req">*</span></label>
                                    <select id="vaMapRoom" class="form-select coe-control" multiple data-placeholder="Select Room" disabled></select>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="coe-form-label" for="vaMapCapacity">Room Capacity<span class="coe-req">*</span></label>
                                        <input type="text" id="vaMapCapacity" class="form-control coe-control va-readonly" value="-" readonly>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="coe-form-label" for="vaMapAvailLaptop">Laptop Available<span class="coe-req">*</span></label>
                                        <input type="text" id="vaMapAvailLaptop" class="form-control coe-control va-readonly" value="-" readonly>
                                    </div>
                                </div>
                            </div>
                            {{-- Manual: one room per row, with the OTs who sit in it --}}
                            <div class="tab-pane fade" id="vaModeManual" role="tabpanel" aria-labelledby="vaModeManualTab">
                                <div id="vaManualRows"></div>
                            </div>
                        </div>
                    </div>
                    {{-- Allocation Rules --}}
                    <div class="va-alloc-rules">
                        <span class="coe-form-label d-block mb-2">Allocation Rules</span>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="vaRuleService" checked>
                            <label class="form-check-label" for="vaRuleService">Avoid same-service sitting</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="vaRuleCadre" checked>
                            <label class="form-check-label" for="vaRuleCadre">Avoid consecutive rolls for same Cadre</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="vaRuleGender">
                            <label class="form-check-label" for="vaRuleGender">Alternate female/male where possible</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="vaRuleOddEven" name="rule_odd_even" value="1">
                            <label class="form-check-label" for="vaRuleOddEven">Keep seating according to the odd and even numbers of roll number.</label>
                        </div>
                        <div class="form-check mb-2" id="vaRuleGapRow">
                            <input class="form-check-input" type="checkbox" id="vaRuleGap">
                            <label class="form-check-label" for="vaRuleGap">Add gap in seating</label>
                        </div>
                        {{-- Gap in two OTs — shown when "Add gap" checked --}}
                        <div id="vaGapBox" class="va-gap-box d-none">
                            <div class="fw-semibold mb-2" style="font-size:13px">Gap in two OT's</div>
                            <div class="d-flex align-items-center gap-3" style="max-width:140px">
                                <input type="number" id="vaGapCount" class="form-control coe-control" value="1" min="1" max="10">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="vaMapSubmit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Map Seating</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'vaDeleteModal', 'confirmId' => 'vaDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="vaColumnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="vaColumnToggleGrid"></div>
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

    var COL = { sno:0,course:1,cat:2,subj:3,comp:4,ot:5,lap:6,scr:7,bld:8,flr:9,room:10,cap:11,avail:12,scrAvail:13,empty:14,status:15,action:16 };

    var ROOMS = @json($roomsByBuildingFloor);
    var BUILDINGS = @json($buildings);
    var COMPONENTS_URL = @json(route('admin.coe.venue-allocation.components'));
    var STORE_URL = @json(route('admin.coe.venue-allocation.store'));
    var POLICY = { YES: 1, NO: 2, ALL: 3, NONE: 4 };
    var CSRF = $('meta[name="csrf-token"]').attr('content');

    var $table = $('#vaTable');

    function fail(xhr, fallback) {
        var r = xhr.responseJSON || {};
        var msg = r.message || fallback;
        if (r.errors) { msg = Object.keys(r.errors).map(function (k) { return r.errors[k][0]; }).join('\n'); }
        Swal.fire({ icon: 'error', title: 'Not saved', text: msg });
    }

    /* ── Paint actions ────────────────────────────────────────────────── */
    function paintActions($row) {
        var isFrozen = $row.attr('data-va-status') === 'Frozen';
        var html = '<div class="va-act-group">';
        // View on every row; a frozen plan can only be viewed.
        html += '<a href="' + $row.attr('data-va-view-url') + '" class="va-act va-act--edit"><i class="bi bi-eye"></i><span>View</span></a>';
        if (!isFrozen) {
            html += '<a href="' + $row.attr('data-va-edit-url') + '" class="va-act va-act--edit"><i class="bi bi-pencil-square"></i><span>Edit Seating</span></a>';
            html += '<button type="button" class="va-act va-act--del"><i class="bi bi-trash3"></i><span>Delete</span></button>';
            html += '<button type="button" class="va-act va-act--freeze"><i class="bi bi-snow2"></i><span>Freeze</span></button>';
        }
        html += '</div>';
        $row.find('.va-cell-actions').html(html);
    }
    $table.find('tbody tr').each(function () { paintActions($(this)); });

    /* ── DataTable ────────────────────────────────────────────────────── */
    CoeGrid.init({
        table: '#vaTable',
        title: @json($isArchived ? 'Venue Allocation – Archived' : 'Venue Allocation'),
        snoCol: COL.sno,
        order: [[COL.course, 'asc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: { '#vaFilterCourse': COL.course, '#vaFilterCategory': COL.cat, '#vaFilterSubject': COL.subj, '#vaFilterComponent': COL.comp },
        reset: '#vaResetFilters',
        download: '#vaDownloadBtn',
        print: '#vaPrintBtn',
        searchToggle: '#vaSearchToggle',
        searchSlot: '#vaDtSearch',
        colvisGrid: '#vaColumnToggleGrid',
        colvisKey: 'coeVenueAllocation:hiddenColumns:v1',
        empty: { title: 'No Venue Allocations', text: 'Map a seating plan to get started.' }
    });

    /* ── Delete (one room of a plan; its OTs are re-seated in the plan's other rooms) ── */
    CoeGrid.confirmDelete({
        table: '#vaTable',
        modal: '#vaDeleteModal',
        confirm: '#vaDeleteConfirm',
        onConfirm: function ($row) {
            $.ajax({ url: $row.attr('data-va-delete-url'), method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } })
                .done(function (r) {
                    Swal.fire({ icon: 'success', title: 'Deleted', text: r.message }).then(function () { location.reload(); });
                })
                .fail(function (xhr) { fail(xhr, 'The room could not be removed.'); });
        }
    });

    /* ── Freeze (publishes the whole plan and notifies its OTs) ───────── */
    $table.on('click', '.va-act--freeze', function () {
        var $row = $(this).closest('tr');
        Swal.fire({
            icon: 'question',
            title: 'Freeze seating plan?',
            text: 'Every room of this plan is frozen and each seated OT is notified of their seat. A frozen plan cannot be changed.',
            showCancelButton: true,
            confirmButtonText: 'Yes, Freeze'
        }).then(function (res) {
            if (!res.isConfirmed) { return; }
            $.ajax({ url: $row.attr('data-va-freeze-url'), method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } })
                .done(function (r) {
                    Swal.fire({ icon: 'success', title: 'Frozen', text: r.message }).then(function () { location.reload(); });
                })
                .fail(function (xhr) { fail(xhr, 'The plan could not be frozen.'); });
        });
    });

    /* ── Map Seating Plan modal ───────────────────────────────────────── */
    var $mapModal = $('#vaMapModal');
    var $body     = $('#vaMapModalBody');
    var $course = $('#vaMapCourse'), $category = $('#vaMapCategory'), $subject = $('#vaMapSubject'),
        $component = $('#vaMapComponent'), $policy = $('#vaMapLaptopPolicy'),
        $building = $('#vaMapBuilding'), $floor = $('#vaMapFloor'), $room = $('#vaMapRoom'),
        $manualRows = $('#vaManualRows');
    var components = [], counts = null, ots = [];
    var mode = 'system';

    // Multiple selects stay open while picking, so several go in one pass.
    function select2On($s) {
        $s.select2({ width: '100%', dropdownParent: $body, placeholder: $s.data('placeholder') || '', closeOnSelect: !$s.prop('multiple') });
    }
    $mapModal.find('select.coe-control').each(function () { select2On($(this)); });

    $mapModal.find('[data-mode]').on('shown.bs.tab', function () {
        mode = $(this).data('mode');
        $mapModal.find('.coe-field-error').remove();
        $mapModal.find('.is-invalid').removeClass('is-invalid');
    });

    // Reopening the dialog starts from a blank form, as the design shows it.
    $mapModal.on('hidden.bs.modal', function () {
        $course.add($category).val('').trigger('change');
        $building.val(null).trigger('change');
        $manualRows.empty();
        addManualRow();
        bootstrap.Tab.getOrCreateInstance(document.getElementById('vaModeSystemTab')).show();
        mode = 'system';
        $('#vaRuleService, #vaRuleCadre').prop('checked', true);
        $('#vaRuleGender, #vaRuleOddEven, #vaRuleGap').prop('checked', false);
        $('#vaGapCount').val(1);
        $('#vaGapBox').addClass('d-none');
        $mapModal.find('.coe-field-error').remove();
        $mapModal.find('.is-invalid').removeClass('is-invalid');
    });

    function resetSelect($sel, placeholder, disabled) {
        $sel.empty().append(new Option(placeholder, '', true, true)).prop('disabled', disabled).trigger('change.select2');
    }

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"], .mb-3');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
    }

    function paintCounts() {
        var c = counts && $category.val() !== '' ? counts[$category.val()] : null;
        if (!c) { $('#vaMapOtCount, #vaMapLaptop').val('-'); return; }
        var laptop = { 1: c.declared_yes, 2: c.declared_no + c.undeclared, 3: c.total, 4: 0 }[$policy.val()];
        $('#vaMapOtCount').val(c.total);
        $('#vaMapLaptop').val(laptop);
    }

    $course.on('change', function () {
        components = []; counts = null; ots = [];
        resetSelect($subject, 'Select Subject', true);
        resetSelect($component, 'Select Component', true);
        paintCounts();
        refreshOts();
        if (!$course.val()) { return; }
        $.getJSON(COMPONENTS_URL, { course_batch: $course.val() })
            .done(function (r) {
                components = r.components; counts = r.counts; ots = r.ots || [];
                refreshOts();
                var subjects = [];
                components.forEach(function (c) { if (subjects.indexOf(c.subject) === -1) { subjects.push(c.subject); } });
                subjects.forEach(function (s) { $subject.append(new Option(s, s)); });
                $subject.prop('disabled', subjects.length === 0).trigger('change.select2');
                if (!components.length) { fieldError($subject, 'No examination drive schedules a component for this course.'); }
                paintCounts();
            })
            .fail(function (xhr) { fail(xhr, 'Could not load this course.'); });
    });

    $subject.on('change', function () {
        fieldError($subject, '');
        resetSelect($component, 'Select Component', !$subject.val());
        components.filter(function (c) { return c.subject === $subject.val(); })
            .forEach(function (c) { $component.append(new Option(c.label, c.id)); });
        $component.trigger('change.select2');
    });

    $category.on('change', function () { paintCounts(); refreshOts(); });

    function vals($s) { return ($s.val() || []).map(String); }

    function buildingName(id) {
        var b = BUILDINGS.find(function (x) { return String(x.id) === String(id); });
        return b ? b.name : '';
    }

    var ROOM_BY_ID = {};
    Object.keys(ROOMS).forEach(function (b) {
        Object.keys(ROOMS[b]).forEach(function (f) {
            ROOMS[b][f].rooms.forEach(function (r) { ROOM_BY_ID[r.id] = r; });
        });
    });

    /* ── System generated: buildings → floors → rooms, any number of each ── */
    // Refill a multiple select, keeping whatever is still on offer; the change cascades down.
    function refill($s, options) {
        var keep = vals($s);
        $s.empty();
        options.forEach(function (o) { $s.append(new Option(o.text, o.id, false, keep.indexOf(String(o.id)) !== -1)); });
        $s.prop('disabled', options.length === 0).trigger('change');
    }

    $building.on('change', function () {
        var floors = {};
        vals($building).forEach(function (b) {
            Object.keys(ROOMS[b] || {}).forEach(function (f) { floors[f] = ROOMS[b][f].name; });
        });
        refill($floor, Object.keys(floors).map(function (id) { return { id: id, text: floors[id] }; }));
    });

    $floor.on('change', function () {
        // With more than one building or floor picked, a room name alone is ambiguous.
        var many = vals($building).length > 1 || vals($floor).length > 1;
        var options = [];
        vals($building).forEach(function (b) {
            vals($floor).forEach(function (f) {
                var floor = (ROOMS[b] || {})[f];
                if (!floor) { return; }
                floor.rooms.forEach(function (r) {
                    options.push({ id: r.id, text: many ? r.name + ' (' + buildingName(b) + ', ' + floor.name + ')' : r.name });
                });
            });
        });
        refill($room, options);
    });

    // Capacity and laptops of every room picked, added up.
    $room.on('change', function () {
        var picked = vals($room).map(function (id) { return ROOM_BY_ID[id]; }).filter(Boolean);
        var sum = function (key) { return picked.reduce(function (n, r) { return n + (r[key] || 0); }, 0); };
        $('#vaMapCapacity').val(picked.length ? sum('capacity') : '-');
        $('#vaMapAvailLaptop').val(picked.length ? sum('laptop') : '-');
    });

    /* ── Manual: one room per row, and the OTs who sit in it ─────────── */
    function manualField(label, cls, placeholder, multiple) {
        return '<div class="col-sm-6 col-lg">' +
            '<label class="coe-form-label">' + label + '<span class="coe-req">*</span></label>' +
            '<select class="form-select coe-control ' + cls + '" aria-label="' + label + '"' +
            (multiple ? ' multiple data-placeholder="' + placeholder + '"' : '') + '></select></div>';
    }

    function addManualRow() {
        var $row = $('<div class="row g-2 align-items-end va-man-row">' +
            manualField('Building Name', 'va-man-building') +
            manualField('Floor', 'va-man-floor') +
            manualField('Room', 'va-man-room') +
            manualField('OT Roll No.', 'va-man-ots', 'Select OT', true) +
            '<div class="col-auto va-man-act">' +
                '<button type="button" class="va-man-btn va-man-btn--remove" title="Remove row" aria-label="Remove row"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>' +
                '<button type="button" class="va-man-btn va-man-btn--add" title="Add row" aria-label="Add row"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>' +
            '</div></div>').appendTo($manualRows);

        var $b = $row.find('.va-man-building').append(new Option('Select Building', ''));
        BUILDINGS.forEach(function (b) { $b.append(new Option(b.name, b.id)); });
        resetSelect($row.find('.va-man-floor'), 'Select Floor', true);
        resetSelect($row.find('.va-man-room'), 'Select Room', true);
        $row.find('.va-man-building, .va-man-floor, .va-man-room').each(function () { select2On($(this)); });
        // The list reads "roll — name"; once picked only the roll number is kept in the box.
        $row.find('.va-man-ots').select2({
            width: '100%', dropdownParent: $body, placeholder: 'Select OT', closeOnSelect: false,
            templateSelection: function (o) { return String(o.text || '').split(' — ')[0]; }
        });
        fillOts($row);
        syncManualRows();
    }

    function eligibleOts() {
        var category = String($category.val() || '');
        // 0 = All, 1 = General (no disability), 2 = PH — as the server reads them.
        return ots.filter(function (o) { return category === '' || category === '0' || (category === '2') === o.ph; });
    }

    function fillOts($row) {
        var $s = $row.find('.va-man-ots'), keep = vals($s);
        $s.empty();
        eligibleOts().forEach(function (o) {
            $s.append(new Option(o.roll + ' — ' + o.name, o.pk, false, keep.indexOf(String(o.pk)) !== -1));
        });
        $s.trigger('change.select2');
    }

    function refreshOts() {
        $manualRows.children().each(function () { fillOts($(this)); });
        syncManualRows();
    }

    // A room or an OT taken in one row is greyed out in the others; "+" only on the last row.
    function syncManualRows() {
        var $rows = $manualRows.children();
        $rows.each(function () {
            var $row = $(this), otherRooms = [], otherOts = [];
            $rows.not($row).each(function () {
                var room = $(this).find('.va-man-room').val();
                if (room) { otherRooms.push(String(room)); }
                otherOts = otherOts.concat(vals($(this).find('.va-man-ots')));
            });
            $row.find('.va-man-room option').each(function () { this.disabled = !!this.value && otherRooms.indexOf(this.value) !== -1; });
            $row.find('.va-man-ots option').each(function () { this.disabled = otherOts.indexOf(this.value) !== -1; });
        });
        $rows.find('.va-man-btn--add').addClass('d-none');
        $rows.last().find('.va-man-btn--add').removeClass('d-none');
    }

    $manualRows.on('change', '.va-man-building', function () {
        var floors = ROOMS[this.value] || {};
        var $f = $(this).closest('.va-man-row').find('.va-man-floor');
        resetSelect($f, 'Select Floor', !this.value);
        Object.keys(floors).forEach(function (id) { $f.append(new Option(floors[id].name, id)); });
        $f.trigger('change');
    });

    $manualRows.on('change', '.va-man-floor', function () {
        var $row = $(this).closest('.va-man-row');
        var floor = (ROOMS[$row.find('.va-man-building').val()] || {})[this.value];
        var $r = $row.find('.va-man-room');
        resetSelect($r, 'Select Room', !floor);
        ((floor || {}).rooms || []).forEach(function (r) { $r.append(new Option(r.name, r.id)); });
        $r.trigger('change.select2');
        syncManualRows();
    });

    $manualRows.on('change', '.va-man-room, .va-man-ots', syncManualRows);
    $manualRows.on('change', 'select', function () { fieldError($(this), ''); });
    $manualRows.on('click', '.va-man-btn--add', addManualRow);
    // The last row is cleared rather than removed, so there is always one to fill.
    $manualRows.on('click', '.va-man-btn--remove', function () {
        var $row = $(this).closest('.va-man-row');
        if ($manualRows.children().length > 1) {
            $row.find('select').select2('destroy');
            $row.remove();
        } else {
            $row.find('.va-man-building').val('').trigger('change');
            $row.find('.va-man-ots').val(null).trigger('change');
        }
        syncManualRows();
    });

    addManualRow();

    /* Show/hide gap box */
    $('#vaRuleGap').on('change', function () {
        $('#vaGapBox').toggleClass('d-none', !this.checked);
    });

    $('#vaMapBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($mapModal[0]).show();
    });

    $mapModal.find('select').on('change', function () { fieldError($(this), ''); });

    function validateMap() {
        var required = [[$course, 'Select a course.'], [$category, 'Select a candidate category.'], [$subject, 'Select a subject.'],
            [$component, 'Select a component.']];
        if (mode === 'system') {
            required.push([$building, 'Select a building.'], [$floor, 'Select a floor.'], [$room, 'Select a room.']);
        } else {
            $manualRows.children().each(function () {
                var $r = $(this);
                required.push([$r.find('.va-man-building'), 'Select a building.'], [$r.find('.va-man-floor'), 'Select a floor.'],
                    [$r.find('.va-man-room'), 'Select a room.'], [$r.find('.va-man-ots'), 'Select the OTs for this room.']);
            });
        }
        var ok = true;
        required.forEach(function (pair) {
            var v = pair[0].val();
            var missing = !v || (Array.isArray(v) && !v.length);
            fieldError(pair[0], missing ? pair[1] : '');
            ok = ok && !missing;
        });
        return ok;
    }

    function mapPayload() {
        var checked = function (id) { return $(id).is(':checked') ? 1 : 0; };
        var data = {
            course_batch: $course.val(),
            candidate_category: $category.val(),
            component_map_id: $component.val(),
            laptop_requirement: $policy.val(),
            seating_mode: mode,
            rule_avoid_same_service: checked('#vaRuleService'),
            rule_avoid_same_cadre: checked('#vaRuleCadre'),
            rule_alternate_gender: checked('#vaRuleGender'),
            rule_odd_even: checked('#vaRuleOddEven'),
            seat_gap: $('#vaRuleGap').is(':checked') ? (parseInt($('#vaGapCount').val(), 10) || 1) : 0
        };
        if (mode === 'system') {
            data.venue_ids = vals($room);
        } else {
            data.rooms = $manualRows.children().map(function () {
                return { venue_id: $(this).find('.va-man-room').val(), ots: vals($(this).find('.va-man-ots')) };
            }).get();
        }
        return data;
    }

    // A 422 goes back under the field it is about; anything else is a dialog.
    function mapFail(xhr, fallback) {
        var errors = (xhr.responseJSON || {}).errors;
        if (xhr.status === 422 && errors) {
            var fields = { course_batch: $course, candidate_category: $category, component_map_id: $component, venue_ids: $room };
            var target = function (k) {
                if (fields[k]) { return fields[k]; }
                var m = /^rooms\.(\d+)\.(venue_id|ots)/.exec(k);
                var $row = m ? $manualRows.children().eq(+m[1]) : $();
                return $row.length ? $row.find(m[2] === 'ots' ? '.va-man-ots' : '.va-man-room') : null;
            };
            Object.keys(errors).forEach(function (k) { var $f = target(k); if ($f) { fieldError($f, errors[k][0]); } });
            if (Object.keys(errors).some(function (k) { return !target(k); })) { fail(xhr, 'Check the form.'); }
            return;
        }
        fail(xhr, fallback);
    }

    function submitMap($btn) {
        $btn.prop('disabled', true);
        $.ajax({ url: STORE_URL, method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, data: mapPayload() })
            .done(function (r) {
                bootstrap.Modal.getOrCreateInstance($mapModal[0]).hide();
                Swal.fire({ icon: r.status === 'warning' ? 'warning' : 'success', title: 'Mapped', text: r.message })
                    .then(function () { location.reload(); });
            })
            .fail(function (xhr) { mapFail(xhr, 'The seating could not be saved.'); })
            .always(function () { $btn.prop('disabled', false); });
    }

    $('#vaMapForm').on('submit', function (e) {
        e.preventDefault();
        if (validateMap()) { submitMap($('#vaMapSubmit')); }
    });
});
</script>
@endpush
