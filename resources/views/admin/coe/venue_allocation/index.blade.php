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
.va-alloc-rules    { border:1px solid var(--ds-line);border-radius:8px;padding:12px 14px;margin-top:4px; }
.va-gap-box        { border:1px solid var(--ds-line);border-radius:8px;padding:12px 14px;margin-top:10px; }
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
                    <div class="programme-dt-filter-select">
                        <select id="vaFilterCourse"  class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="vaFilterService" class="form-select" aria-label="Service Name">
                            <option value="">Service Name</option>
                            <option>IAS</option><option>IPS</option><option>IFS</option>
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="vaFilterExam" class="form-select" aria-label="Examination">
                            <option value="">Examination</option>
                            <option>Written Test</option><option>Practical</option>
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="vaFilterCadre" class="form-select" aria-label="Cadre">
                            <option value="">Cadre</option>
                            <option>MP</option><option>UP</option><option>Bihar</option>
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="vaFilterBatch" class="form-select" aria-label="Batch">
                            <option value="">Batch</option>
                            <option>Batch A</option><option>Batch B</option>
                        </select>
                    </div>
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
                                data-va-course="{{ $row['course'] }}"
                                data-va-category="{{ $row['category'] }}"
                                data-va-subject="{{ $row['subject'] }}"
                                data-va-component="{{ $row['component'] }}"
                                data-va-status="{{ $row['status'] }}"
                                data-va-edit-url="{{ route('admin.coe.venue-allocation.edit', $row['id']) }}">
                                <td></td>
                                <td>{{ $row['course'] }}</td>
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
                            <select id="vaMapCourse" class="form-select coe-control">
                                <option value="">Select Course</option>
                                @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapCategory">Candidate Category<span class="coe-req">*</span></label>
                            <select id="vaMapCategory" class="form-select coe-control">
                                <option value="">Select Category</option>
                                @foreach ($categories as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Row 2: Subject + Component --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapSubject">Subject<span class="coe-req">*</span></label>
                            <select id="vaMapSubject" class="form-select coe-control">
                                <option value="">Select Subject</option>
                                @foreach ($subjects as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapComponent">Component<span class="coe-req">*</span></label>
                            <select id="vaMapComponent" class="form-select coe-control">
                                <option value="">Select Component</option>
                                @foreach ($components as $comp)<option value="{{ $comp }}">{{ $comp }}</option>@endforeach
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
                    {{-- Row 4: Building + Floor (dependent selects) --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapBuilding">Building Name<span class="coe-req">*</span></label>
                            <select id="vaMapBuilding" class="form-select coe-control">
                                <option value="">Select Building</option>
                                @foreach ($buildings as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapFloor">Floor<span class="coe-req">*</span></label>
                            <select id="vaMapFloor" class="form-select coe-control" disabled>
                                <option value="">Select Floor</option>
                            </select>
                        </div>
                    </div>
                    {{-- Room --}}
                    <div class="mb-3">
                        <label class="coe-form-label" for="vaMapRoom">Room<span class="coe-req">*</span></label>
                        <select id="vaMapRoom" class="form-select coe-control" disabled>
                            <option value="">Select Room</option>
                        </select>
                    </div>
                    {{-- Row 5: Room Capacity + Laptop Available (readonly) --}}
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapCapacity">Room Capacity<span class="coe-req">*</span></label>
                            <input type="text" id="vaMapCapacity" class="form-control coe-control va-readonly" value="-" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="vaMapAvailLaptop">Laptop Available<span class="coe-req">*</span></label>
                            <input type="text" id="vaMapAvailLaptop" class="form-control coe-control va-readonly" value="-" readonly>
                        </div>
                    </div>
                    {{-- Allocation Rules --}}
                    <div class="va-alloc-rules">
                        <div class="fw-semibold mb-2" style="font-size:13px">Allocation Rules</div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="vaRuleOddEven">
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
                                <input type="number" id="vaGapCount" class="form-control coe-control" value="2" min="1" max="10">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Map Seating</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'vaDeleteModal', 'confirmId' => 'vaDeleteConfirm'])
@include('admin.coe.partials.freeze_modal', ['phoneMasked' => '+91 ******3274'])

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

    var floorsByBuilding = @json($floorsByBuilding);
    var roomsByBuildingFloor = @json($roomsByBuildingFloor);

    /* Sample room capacity/laptop lookup (keyed by room name) */
    var roomData = {
        'First Room – AS-SF02-R1': { capacity: 100, laptop: 22 },
        'Exam Hall – AS-SF-EH1':   { capacity: 60,  laptop: 60 },
    };

    var $table = $('#vaTable');

    /* ── Paint actions ────────────────────────────────────────────────── */
    function paintActions($row) {
        var status   = $row.attr('data-va-status');
        var editUrl  = $row.attr('data-va-edit-url');
        var isFrozen = status === 'Frozen';
        var html = '<div class="va-act-group">';
        if (!isFrozen) {
            html += '<a href="' + editUrl + '" class="va-act va-act--edit"><i class="bi bi-pencil-square"></i><span>Edit Seating</span></a>';
        }
        html += '<button type="button" class="va-act va-act--del"><i class="bi bi-trash3"></i><span>Delete</span></button>';
        if (!isFrozen) {
            html += '<button type="button" class="va-act va-act--freeze"><i class="bi bi-snow2"></i><span>Freeze</span></button>';
        }
        html += '</div>';
        $row.find('.va-cell-actions').html(html);
    }
    $table.find('tbody tr').each(function () { paintActions($(this)); });

    /* ── DataTable ────────────────────────────────────────────────────── */
    var dt = CoeGrid.init({
        table: '#vaTable',
        title: @json($isArchived ? 'Venue Allocation – Archived' : 'Venue Allocation'),
        snoCol: COL.sno,
        order: [[COL.course, 'asc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: { '#vaFilterCourse': COL.course },
        reset: '#vaResetFilters',
        download: '#vaDownloadBtn',
        print: '#vaPrintBtn',
        searchToggle: '#vaSearchToggle',
        searchSlot: '#vaDtSearch',
        colvisGrid: '#vaColumnToggleGrid',
        colvisKey: 'coeVenueAllocation:hiddenColumns:v1',
        empty: { title: 'No Venue Allocations', text: 'Map a seating plan to get started.' }
    });

    /* ── Delete ───────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#vaTable',
        modal: '#vaDeleteModal',
        confirm: '#vaDeleteConfirm',
        onConfirm: function ($row) {
            dt.row($row).remove().draw(false);
            Swal.fire({ icon: 'success', title: 'Deleted', text: 'Allocation deleted.' });
        }
    });

    /* ── Freeze ───────────────────────────────────────────────────────── */
    CoeGrid.freeze({
        table: '#vaTable',
        trigger: '.va-act--freeze',
        onConfirm: function ($row) {
            $row.attr('data-va-status', 'Frozen');
            $row.find('.coe-state').attr('class', 'coe-state va-status-frozen').text('Frozen');
            paintActions($row);
            dt.row($row).invalidate('dom').draw(false);
            Swal.fire({ icon: 'success', title: 'Frozen', text: 'Seating plan frozen. Notifications sent to OTs.' });
        }
    });

    /* ── Map Seating Plan modal ───────────────────────────────────────── */
    var $mapModal = $('#vaMapModal');
    var $body     = $('#vaMapModalBody');

    $mapModal.find('select.coe-control').each(function () {
        $(this).select2({ width: '100%', dropdownParent: $body });
    });

    $('#vaMapBuilding').on('change', function () {
        var building = $(this).val();
        var floors   = (floorsByBuilding[building] || []);
        var $floor   = $('#vaMapFloor');
        $floor.empty().append('<option value="">Select Floor</option>');
        floors.forEach(function (f) { $floor.append('<option value="' + f + '">' + f + '</option>'); });
        $floor.prop('disabled', !building).trigger('change.select2');
        $('#vaMapRoom').empty().append('<option value="">Select Room</option>').prop('disabled', true).trigger('change.select2');
        $('#vaMapCapacity, #vaMapAvailLaptop').val('-');
    });

    $('#vaMapFloor').on('change', function () {
        var building = $('#vaMapBuilding').val();
        var floor    = $(this).val();
        var rooms    = ((roomsByBuildingFloor[building] || {})[floor] || []);
        var $room    = $('#vaMapRoom');
        $room.empty().append('<option value="">Select Room</option>');
        rooms.forEach(function (r) { $room.append('<option value="' + r[0] + '">' + r[0] + '</option>'); });
        $room.prop('disabled', !floor).trigger('change.select2');
        $('#vaMapCapacity, #vaMapAvailLaptop').val('-');
    });

    $('#vaMapRoom').on('change', function () {
        var name = $(this).val();
        var data = roomData[name] || {};
        $('#vaMapCapacity').val(data.capacity || '-');
        $('#vaMapAvailLaptop').val(data.laptop || '-');
    });

    /* Show/hide gap box */
    $('#vaRuleGap').on('change', function () {
        $('#vaGapBox').toggleClass('d-none', !this.checked);
    });

    $('#vaMapBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($mapModal[0]).show();
    });

    $('#vaMapForm').on('submit', function (e) {
        e.preventDefault();
        bootstrap.Modal.getOrCreateInstance($mapModal[0]).hide();
        Swal.fire({ icon: 'success', title: 'Mapped', text: 'Seating plan mapped successfully.' });
    });
});
</script>
@endpush
