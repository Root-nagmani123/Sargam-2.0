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
.va-act--invig     { color:#28a745;line-height:1.15; }
.va-act--invig i   { color:#28a745; }
/* Add Invigilators — as wide as the screen allows; only the table scrolls sideways, never the dialog */
.va-inv-dialog     { max-width:min(1400px, calc(100vw - 2rem)); }
#vaInvBody         { overflow-x:hidden; }
.va-inv-subject    { color:#004384;font-weight:600;font-size:18px;margin:4px 0 10px; }
.va-inv-subject:not(:first-child) { margin-top:22px; }
.va-inv-wrap       { overflow-x:auto; }
.va-inv-table      { width:100%;min-width:1080px;margin-bottom:0;table-layout:fixed; }
.va-inv-frozen    { display:inline-block;margin-left:4px;padding:0 7px;border-radius:9px;font-size:11px;font-weight:500;
                     background:#eef4ff;color:#004384;vertical-align:middle; }
.va-inv-table tr.is-frozen td { color:#667085; }
.va-inv-table thead th { background:#eef0f3;font-weight:500;font-size:13px;color:#344054;vertical-align:middle;border-bottom:0; }
.va-inv-table td   { vertical-align:middle;font-size:14px;padding-top:14px;padding-bottom:14px;overflow-wrap:anywhere; }
.va-inv-table .va-inv-c-comp { width:14%; }
.va-inv-table .va-inv-c-date { width:9%; }
.va-inv-table .va-inv-c-text { width:8%; }
.va-inv-table .va-inv-c-ot   { width:6%; }
.va-inv-table .va-inv-pick   { width:12.5%; }
/* Role headers read as "Chief Invigilator ▾": no box, the pick applies to every room below */
.va-inv-table tbody .select2-container--default .select2-selection--single { height:auto;min-height:38px;display:flex;align-items:center;
                     padding:6px 0; }
/* Up to two lines of the chosen name (the full name is the tooltip), never a third half-line. */
.va-inv-table tbody .select2-container--default .select2-selection--single .select2-selection__rendered {
                     white-space:normal;line-height:1.3;max-height:2.6em;padding:0 26px 0 10px;overflow-wrap:break-word;
                     display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.va-inv-table tbody .select2-container--default .select2-selection--single .select2-selection__arrow { top:50%;transform:translateY(-50%); }
/* The open list is wider than its column (names like "Air Commodore YK Dixit VSM (GUE-00350)"):
   one name per line, no sideways scroll. invSelect2() keeps it inside the window. */
.va-inv-dd.select2-dropdown { min-width:340px;max-width:min(440px, calc(100vw - 32px)); }
.va-inv-dd .select2-results__options { overflow-x:hidden; }
.va-inv-dd .select2-results__option { white-space:normal;overflow-wrap:break-word;line-height:1.35;padding:7px 10px;font-size:14px; }
.va-inv-table thead .select2-container--default .select2-selection--single { border:0;background:transparent;box-shadow:none;padding-left:0; }
.va-inv-table thead .select2-container--default .select2-selection--single .select2-selection__rendered { color:#344054;font-weight:500;padding-left:0;white-space:normal;line-height:1.25; }
/* Modal: readonly computed fields */
.va-readonly       { background:var(--ds-surface-2) !important;cursor:default; }
/* Allocation-rules section inside modal */
.va-alloc-rules    { margin-top:4px; }
.va-alloc-rules .form-check-label { color:#344054; }
.va-gap-box        { border:1px solid var(--ds-line);border-radius:8px;padding:12px 14px;margin-top:10px; }
/* Map Seating Plan: one card per subject, one row per component */
.va-subject        { border:1px solid var(--ds-line);border-radius:12px;padding:16px 16px 18px; }
.va-subject + .va-subject { margin-top:16px; }
.va-subject__title { color:#004384;font-weight:600;font-size:18px;margin:0 0 12px; }
.va-comp + .va-comp { margin-top:14px;padding-top:14px;border-top:1px dashed var(--ds-line); }
.va-comp-row + .va-comp-row { margin-top:4px; }
.va-comp--frozen   { opacity:.7; }
.va-add-room       { padding:0;margin-top:8px;font-size:13px;text-decoration:none; }
.va-room-extra__inner { display:flex;align-items:center;justify-content:space-between;gap:8px;min-height:38px; }
.va-room-extra__label { font-size:13px;color:#667085;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.va-remove-room    { padding:0;font-size:13px;text-decoration:none;flex:none; }
.va-ot-item--elsewhere { display:none !important; }
.va-ot-range:disabled { opacity:.45;cursor:default; }
.va-comp-cover     { margin-top:8px; }
.va-comp-row .form-control, .va-comp-row .va-ot-toggle { white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
/* OT Roll No. picker */
.va-ot-toggle      { width:100%; }
.va-ot-toggle.is-placeholder { color:#344054; }
.va-ot-menu        { width:300px;max-width:90vw;padding:10px;z-index:1080; }
.va-ot-menu__head  { font-weight:600;font-size:13px;margin-bottom:8px; }
.va-ot-ranges      { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px; }
.va-ot-range       { border:1px solid var(--ds-line);background:#fff;border-radius:6px;padding:2px 8px;font-size:12px; }
.va-ot-range.is-active { background:#004384;border-color:#004384;color:#fff; }
.va-ot-list        { max-height:240px;overflow:auto; }
.va-ot-item        { display:flex;align-items:center;gap:8px;padding:4px 2px;font-size:13px;cursor:pointer;margin:0; }
.va-ot-item .form-check-input { margin:0;flex:none; }
.va-ot-item--all   { border-bottom:1px solid var(--ds-line);padding-bottom:6px;margin-bottom:4px;font-weight:600; }
.va-ot-item__roll  { font-weight:600;flex:none; }
.va-ot-item__name  { color:#667085;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.va-ot-item.is-taken { cursor:default;opacity:.6; }
.va-ot-item__tag   { margin-left:auto;font-size:11px;color:#856404;flex:none; }
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
        'vaFilterDrive' => ['Examination Drive', 'drive'],
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
                                <th>Examination Drive</th>
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
                                data-va-delete-url="{{ route('admin.coe.venue-allocation.destroy', $row['id']) }}"
                                @if ($row['invigilators']) data-va-invig-url="{{ route('admin.coe.venue-allocation.invigilators', $row['id']) }}" @endif
                                data-va-invig-has="{{ $row['has_invigilators'] ? 1 : 0 }}">
                                <td></td>
                                <td>{{ $row['drive'] }}</td>
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
{{-- Drive first: every subject of the drive is a card, every component of it a row
     with its room and the OTs that room seats. Rows left blank are skipped. --}}
<div class="modal fade" id="vaMapModal" tabindex="-1" aria-labelledby="vaMapModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="vaMapForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="vaMapModalLabel">Map Seating Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body" id="vaMapModalBody">
                    <div class="mb-3">
                        <label class="coe-form-label" for="vaMapDrive">Drive Name<span class="coe-req">*</span></label>
                        <select id="vaMapDrive" name="drive_id" class="form-select coe-control">
                            <option value="">Select Drive</option>
                            @foreach ($drives as $d)<option value="{{ $d['id'] }}">{{ $d['label'] }}</option>@endforeach
                        </select>
                        @if ($drives->isEmpty())
                            <div class="small text-muted mt-1">No examination drive can be mapped yet — create a drive, and generate roll numbers for its course.</div>
                        @endif
                    </div>
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
                    @if ($buildings->isEmpty())
                        <div class="small text-muted mb-2">No examination rooms yet — add rooms with a building, floor and capacity in Room Master.</div>
                    @endif
                    {{-- Subject cards, filled when a drive is picked --}}
                    <div id="vaSubjects" class="mb-4"></div>
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
                            <input class="form-check-input" type="checkbox" id="vaRuleOddEven">
                            <label class="form-check-label" for="vaRuleOddEven">Keep seating according to the odd and even numbers of roll number.</label>
                        </div>
                        <div class="form-check mb-2" id="vaRuleGapRow">
                            <input class="form-check-input" type="checkbox" id="vaRuleGap">
                            <label class="form-check-label" for="vaRuleGap">Add gap in seating</label>
                        </div>
                        {{-- Gap in two OTs — shown when "Add gap" checked --}}
                        <div id="vaGapBox" class="va-gap-box d-none">
                            <label class="fw-semibold mb-2 d-block" style="font-size:13px" for="vaGapCount">Gap in two OT's</label>
                            <div class="d-flex align-items-center gap-3" style="max-width:140px">
                                <input type="number" id="vaGapCount" class="form-control coe-control" value="1" min="1" max="10">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <span id="vaMapBlocked" class="small text-danger me-auto d-none" role="status">
                        Every OT of a paper needs a seat — fix the errors above to map seating.
                    </span>
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="vaMapSubmit" class="btn coe-btn coe-btn-primary coe-btn--narrow">Map Seating</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Add Invigilators modal: every room of the drive, by subject, one person per role ── --}}
<div class="modal fade" id="vaInvModal" tabindex="-1" aria-labelledby="vaInvModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable va-inv-dialog">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="vaInvForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="vaInvModalLabel">Add Invigilators</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body" id="vaInvBody"></div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="vaInvSubmit" class="btn coe-btn coe-btn-primary">Map Invigilator</button>
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

    var COL = { sno:0,drive:1,course:2,cat:3,subj:4,comp:5,ot:6,lap:7,scr:8,bld:9,flr:10,room:11,cap:12,avail:13,scrAvail:14,empty:15,status:16,action:17 };

    var ROOMS = @json($roomsByBuildingFloor);
    var BUILDINGS = @json($buildings);
    var DRIVE_URL = @json(route('admin.coe.venue-allocation.drive', ['id' => '__ID__']));
    var STORE_DRIVE_URL = @json(route('admin.coe.venue-allocation.store-drive'));
    var INVIG_STORE_URL = @json(route('admin.coe.venue-allocation.invigilators.store'));
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
        // Not for an Offline Objective paper: it takes no invigilators.
        // "Edit" once anyone is assigned to the room; the same dialog opens, prefilled.
        if ($row.attr('data-va-invig-url')) {
            var invVerb = $row.attr('data-va-invig-has') === '1' ? 'Edit' : 'Add';
            html += '<button type="button" class="va-act va-act--invig"><i class="bi bi-person-walking"></i><span>' + invVerb + '<br>Invigilators</span></button>';
        }
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
        filters: { '#vaFilterDrive': COL.drive, '#vaFilterCourse': COL.course, '#vaFilterCategory': COL.cat, '#vaFilterSubject': COL.subj, '#vaFilterComponent': COL.comp },
        reset: '#vaResetFilters',
        download: '#vaDownloadBtn',
        print: '#vaPrintBtn',
        searchToggle: '#vaSearchToggle',
        searchSlot: '#vaDtSearch',
        colvisGrid: '#vaColumnToggleGrid',
        colvisKey: 'coeVenueAllocation:hiddenColumns:v2',
        empty: { title: 'No Venue Allocations', text: 'Map a seating plan to get started.' }
    });

    /* ── Delete (one room of a plan; its OTs are re-seated in the plan's other rooms) ── */
    CoeGrid.confirmDelete({
        table: '#vaTable',
        // This page's buttons are .va-act--del; the CoeGrid default (.coe-act--del) never matched.
        trigger: '.va-act--del',
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

    /* ── Add Invigilators ─────────────────────────────────────────────── */
    var $invModal = $('#vaInvModal'), $invBody = $('#vaInvBody');
    var ROLES = [
        { key: 'chief', label: 'Chief Invigilator', list: 'faculty' },
        { key: 'invigilator', label: 'Invigilator', list: 'faculty' },
        { key: 'assistant', label: 'Assistant Invigilator', list: 'faculty' },
        { key: 'mts', label: 'MTS', list: 'mts' }
    ];
    var invPeople = { faculty: [], mts: [] };
    var invBusy = {};   // day -> { f: [ids], m: [ids] } held in rooms outside this dialog

    function invClear() {
        $invBody.find('select').each(function () { if ($(this).data('select2')) { $(this).select2('destroy'); } });
        $invBody.empty();
    }
    $invModal.on('hidden.bs.modal', invClear);

    // First option is a real, pickable one: "Select" clears a room's role; in a header it is the role's name.
    function invSelect(role, value, first) {
        var $s = $('<select class="form-select coe-control"></select>').attr('data-role', role.key).attr('aria-label', role.label);
        $s.append(new Option(first, '', false, false));
        invPeople[role.list].forEach(function (p) {
            $s.append(new Option(p.text, p.id, false, String(p.id) === String(value)));
        });
        return $s;
    }

    // The list opens at its box's width (no auto-width), so it never widens the dialog body.
    // Someone who cannot be picked here (see invSync) is left out of the list, not just greyed.
    function invSelect2($s) {
        $s.select2({
            // On the modal itself, not the scrolling body: a list opened on a short body was clipped.
            width: '100%', dropdownParent: $invModal,
            templateResult: function (o) { return o.element && o.element.disabled && o.element.value !== '' ? null : o.text; }
        });
        // The list is wider than the column (.va-inv-dd); in the right-hand columns, slide it
        // left so it stays in the window. Select2 re-positions on scroll, so re-check then too.
        var fit = function () {
            var $dd = $('.va-inv-dd');
            if (!$dd.length) { return; }
            var $box = $dd.parent();   // .select2-container--open, absolutely positioned by Select2
            $box.css('margin-left', 0);
            // Against the dialog's own right edge: both move together if the page scrolls.
            var edge = ($invModal.find('.modal-content')[0] || $invModal[0]).getBoundingClientRect().right;
            var limit = Math.min(edge, document.documentElement.clientWidth) - 8;
            var over = $dd[0].getBoundingClientRect().right - limit;
            if (over > 0) { $box.css('margin-left', -over); }
        };
        $s.on('select2:open', function () {
            $($s.data('select2').$dropdown).find('.select2-dropdown').addClass('va-inv-dd');
            setTimeout(fit, 0);
        });
        $s.on('select2:closing', function () {
            $($s.data('select2').$dropdown).css('margin-left', '').find('.select2-dropdown').removeClass('va-inv-dd');
        });
        $invModal.find('.modal-body').off('scroll.vaInvDd').on('scroll.vaInvDd', function () { setTimeout(fit, 0); });
    }

    /*
     * One person, one role, one room a day: a person picked for any room of an exam date —
     * here, or in another drive's room that day (invBusy) — is taken out of every other list
     * of that date. Faculty (Chief / Invigilator / Assistant) and MTS are separate lists.
     * A room's key (data-paper) is its exam date. The server checks the same rule.
     */
    function invSync() {
        var held = {};   // paper -> 'f' | 'm' -> person id -> [select elements holding them]
        var $selects = $invBody.find('tbody .va-inv-one');
        $selects.each(function () {
            if (!this.value) { return; }
            var p = $(this).closest('tr').attr('data-paper'), list = $(this).attr('data-role') === 'mts' ? 'm' : 'f';
            held[p] = held[p] || { f: {}, m: {} };
            (held[p][list][this.value] = held[p][list][this.value] || []).push(this);
        });
        $selects.each(function () {
            var sel = this, p = $(sel).closest('tr').attr('data-paper'), list = $(sel).attr('data-role') === 'mts' ? 'm' : 'f';
            var taken = (held[p] || { f: {}, m: {} })[list];
            var outside = ((invBusy[p] || {})[list] || []).map(String);
            for (var i = 0; i < sel.options.length; i++) {
                var o = sel.options[i];
                if (!o.value) { continue; }
                // A person this room already holds stays visible, so it can be seen and changed.
                o.disabled = o.value !== sel.value
                    && ((taken[o.value] || []).some(function (h) { return h !== sel; }) || outside.indexOf(o.value) !== -1);
            }
        });
    }

    function invRender(subjects) {
        invClear();
        if (!subjects.length) {
            $('<div class="text-muted py-3"></div>').text('No room of this drive needs invigilators.').appendTo($invBody);
            $('#vaInvSubmit').prop('disabled', true);
            return;
        }
        $('#vaInvSubmit').prop('disabled', false);
        subjects.forEach(function (s) {
            $('<h6 class="va-inv-subject"></h6>').text('Subject: ' + s.subject).appendTo($invBody);
            var $table = $('<table class="table align-middle va-inv-table"><thead><tr></tr></thead><tbody></tbody></table>');
            var $head = $table.find('thead tr');
            var COLS = [['Component Name', 'va-inv-c-comp'], ['Exam Date', 'va-inv-c-date'], ['Building Name', 'va-inv-c-text'],
                ['Floor', 'va-inv-c-text'], ['Room', 'va-inv-c-text'], ['OT', 'va-inv-c-ot']];
            COLS.forEach(function (c) { $('<th></th>').addClass(c[1]).text(c[0]).appendTo($head); });
            // The header pick applies one person to every room of this subject; off when no room takes the role.
            ROLES.forEach(function (role) {
                var none = s.rooms.every(function (r) { return (r.locked || []).indexOf(role.key) !== -1; });
                $('<th class="va-inv-pick"></th>').append(invSelect(role, '', role.label).addClass('va-inv-all')
                    .prop('disabled', none)
                    .attr('title', none ? 'Not needed for this exam type' : 'Apply to every room of ' + s.subject)).appendTo($head);
            });
            s.rooms.forEach(function (r) {
                var $tr = $('<tr></tr>').attr('data-id', r.id).attr('data-paper', r.paper).toggleClass('is-frozen', !!r.frozen);
                [r.component, r.exam_date, r.building, r.floor, r.room, r.ot].forEach(function (v) {
                    $('<td></td>').text(v).appendTo($tr);
                });
                // Frozen seating plan: shown for reference, locked, and not sent on save.
                if (r.frozen) { $tr.children().first().append(' <span class="va-inv-frozen">Frozen</span>'); }
                // A role the paper's exam type does not take (Online Objective: no Assistant
                // Invigilator) is disabled and sent empty.
                ROLES.forEach(function (role) {
                    var locked = (r.locked || []).indexOf(role.key) !== -1;
                    var $s = invSelect(role, locked ? '' : r[role.key], 'Select').addClass('va-inv-one')
                        .prop('disabled', !!r.frozen || locked);
                    if (locked) { $s.attr('title', 'Not needed for this exam type'); }
                    $('<td class="va-inv-pick"></td>').append($s).appendTo($tr);
                });
                $table.find('tbody').append($tr);
            });
            $('<div class="va-inv-wrap"></div>').append($table).appendTo($invBody);
        });
        invSync();
        $invBody.find('select').each(function () { invSelect2($(this)); });
        $('#vaInvSubmit').prop('disabled', !$invBody.find('tbody tr:not(.is-frozen)').length);
    }

    function invError($s, msg) {
        var $td = $s.closest('td, th');
        $td.find('.coe-field-error').remove();
        $td.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($td); }
    }

    // A pick changes who is free elsewhere that day; earlier errors of that room no longer hold.
    $invBody.on('change', '.va-inv-one', function () {
        $(this).closest('tr').find('.va-inv-one').each(function () { invError($(this), ''); });
        invSync();
    });

    // Header pick: the person goes to every room of the subject where they are still free —
    // so to one room per exam date, never two roles in a room.
    $invBody.on('select2:select', '.va-inv-all', function () {
        var $all = $(this), role = $all.attr('data-role'), v = $all.val(), skipped = 0;
        invError($all, '');
        if (v) {
            $all.closest('table').find('tbody .va-inv-one[data-role="' + role + '"]').each(function () {
                var $s = $(this), opt = $s.find('option').filter(function () { return this.value === v; })[0];
                if ($s.val() === v || $s.prop('disabled')) { return; }   // frozen room: left as it is
                if (!opt || opt.disabled) { skipped++; return; }
                $s.val(v).trigger('change');
            });
        }
        $all.val('').trigger('change.select2');
        if (skipped) { invError($all, 'Not set in ' + skipped + ' room(s): already invigilating another room on that exam date.'); }
    });

    $table.on('click', '.va-act--invig', function () {
        var $from = $(this).closest('tr'), url = $from.attr('data-va-invig-url');
        $('#vaInvModalLabel').text(($from.attr('data-va-invig-has') === '1' ? 'Edit' : 'Add') + ' Invigilators');
        invClear();
        $invBody.html('<div class="text-muted py-3">Loading…</div>');
        $('#vaInvSubmit').prop('disabled', true);
        bootstrap.Modal.getOrCreateInstance($invModal[0]).show();
        $.getJSON(url)
            .done(function (r) {
                invPeople = { faculty: r.faculty || [], mts: r.mts || [] };
                invBusy = r.busy || {};
                invRender(r.subjects || []);
            })
            .fail(function (xhr) {
                bootstrap.Modal.getOrCreateInstance($invModal[0]).hide();
                fail(xhr, 'Could not load the rooms.');
            });
    });

    $('#vaInvForm').on('submit', function (e) {
        e.preventDefault();
        var $rows = $invBody.find('tbody tr:not(.is-frozen)');
        var rows = $rows.map(function () {
            var $tr = $(this), row = { id: $tr.attr('data-id') };
            ROLES.forEach(function (role) { row[role.key] = $tr.find('.va-inv-one[data-role="' + role.key + '"]').val() || ''; });
            return row;
        }).get();
        $invBody.find('.coe-field-error').remove();
        $invBody.find('.is-invalid').removeClass('is-invalid');
        var $btn = $('#vaInvSubmit').prop('disabled', true);
        $.ajax({ url: INVIG_STORE_URL, method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, data: { rows: rows } })
            .done(function (r) {
                bootstrap.Modal.getOrCreateInstance($invModal[0]).hide();
                // Reload: rooms that now have (or no longer have) invigilators show Add / Edit accordingly.
                Swal.fire({ icon: 'success', title: 'Saved', text: r.message }).then(function () { location.reload(); });
            })
            .fail(function (xhr) {
                var errors = (xhr.responseJSON || {}).errors;
                if (xhr.status !== 422 || !errors) { fail(xhr, 'The invigilators could not be saved.'); return; }
                var loose = [];
                Object.keys(errors).forEach(function (k) {
                    var m = /^rows\.(\d+)\.(\w+)/.exec(k);
                    var $tr = m ? $rows.eq(+m[1]) : $();
                    var $s = $tr.find('.va-inv-one[data-role="' + (m ? m[2] : '') + '"]');
                    if ($s.length) { invError($s, errors[k][0]); } else { loose.push(errors[k][0]); }
                });
                var $first = $invBody.find('.coe-field-error').first();
                if ($first.length) { $first[0].scrollIntoView({ block: 'center', behavior: 'smooth' }); }
                if (loose.length) { Swal.fire({ icon: 'error', title: 'Not saved', text: loose.join('\n') }); }
            })
            .always(function () { $btn.prop('disabled', false); });
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
    var $drive    = $('#vaMapDrive');
    var $subjects = $('#vaSubjects');
    var ots = [];          // the drive cohort's OTs with a roll number, in roll order
    var OT_BY_PK = {};     // pk -> OT, for the live room check (laptop need)
    var ROOM_BY_ID = {};   // venue id -> { name, capacity, laptop }
    Object.keys(ROOMS).forEach(function (b) {
        Object.keys(ROOMS[b]).forEach(function (f) {
            ROOMS[b][f].rooms.forEach(function (rm) { ROOM_BY_ID[rm.id] = rm; });
        });
    });
    var uid = 0;           // ids for the generated labels
    var driveXhr = null;
    var RANGE = 50;        // OT Roll No. quick picks: blocks of this many

    function select2On($s) {
        // On the modal itself, not the scrolling body, so a list near the bottom is never clipped.
        $s.select2({ width: '100%', dropdownParent: $mapModal, placeholder: $s.data('placeholder') || '' });
    }
    select2On($drive);

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

    function clearErrors() {
        $mapModal.find('.coe-field-error').remove();
        $mapModal.find('.is-invalid').removeClass('is-invalid');
    }

    function clearDrive() {
        if (driveXhr) { driveXhr.abort(); driveXhr = null; }
        ots = [];
        $subjects.find('select').each(function () { if ($(this).data('select2')) { $(this).select2('destroy'); } });
        $subjects.empty();
        $('#vaMapOtCount, #vaMapLaptop').val('-');
        refreshSubmit();
    }

    // Reopening the dialog starts from a blank form, as the design shows it.
    $mapModal.on('hidden.bs.modal', function () {
        $drive.val('').trigger('change');
        $('#vaRuleService, #vaRuleCadre').prop('checked', true);
        $('#vaRuleGender, #vaRuleOddEven, #vaRuleGap').prop('checked', false);
        $('#vaGapCount').val(1);
        $('#vaGapBox').addClass('d-none');
        clearErrors();
    });

    $drive.on('change', function () {
        clearDrive();
        fieldError($drive, '');
        if (!$drive.val()) { return; }
        $subjects.html('<div class="text-muted small py-2">Loading the drive…</div>');
        driveXhr = $.getJSON(DRIVE_URL.replace('__ID__', encodeURIComponent($drive.val())))
            .done(function (r) {
                ots = r.ots || [];
                OT_BY_PK = {};
                ots.forEach(function (o) { OT_BY_PK[o.pk] = o; });
                $('#vaMapOtCount').val(r.ot_count);
                $('#vaMapLaptop').val(r.laptop_required);
                renderSubjects(r.subjects || []);
            })
            .fail(function (xhr) {
                if (xhr.statusText === 'abort') { return; }
                $subjects.empty();
                fieldError($drive, (xhr.responseJSON || {}).message || 'Could not load this drive.');
            })
            .always(function () { driveXhr = null; });
    });

    /* ── Subject cards and component rows ────────────────────────────── */
    function renderSubjects(subjects) {
        $subjects.empty();
        if (!subjects.length) {
            $('<div class="small text-muted py-2"></div>')
                .text('This drive has no subject component yet — add them on Examination Drive.').appendTo($subjects);
            return;
        }
        if (!ots.length) {
            $('<div class="small text-danger py-2"></div>')
                .text('No OT of this drive\'s course has a roll number.').appendTo($subjects);
        }
        subjects.forEach(function (s) {
            var $card = $('<section class="va-subject"></section>').appendTo($subjects);
            $('<h6 class="va-subject__title"></h6>').text('Subject: ' + s.subject).appendTo($card);
            s.components.forEach(function (c) { $card.append(buildComponent(c)); });
        });
    }

    function field(label, $control, cls) {
        var id = 'vaF' + (++uid);
        var $col = $('<div class="' + (cls || 'col-sm-6 col-lg-2') + '"></div>');
        $('<label class="coe-form-label"></label>').attr('for', id).text(label).append('<span class="coe-req">*</span>').appendTo($col);
        ($control.is('.va-ot') ? $control.find('.va-ot-toggle') : $control).attr('id', id);
        $control.appendTo($col);
        return $col;
    }

    /*
     * One component = one group of room rows. The first row carries the component and its
     * date; "Add another room" adds rows below it, so one paper can sit in several rooms
     * (each with its own OTs). Saved together, they all join that component's seating plan.
     */
    function buildComponent(c) {
        var $group = $('<div class="va-comp"></div>').attr('data-map-id', c.id)
            .data('taken', (c.taken || []).map(String)).data('comp', c);
        $group.append(buildRoomRow($group, true));

        if (c.frozen) {
            $group.addClass('va-comp--frozen');
            $group.find('select, button').prop('disabled', true);
            $('<div class="small text-muted mt-1"></div>')
                .text('The seating plan of this component is frozen and cannot take another room.').appendTo($group);
        } else {
            if (c.taken && c.taken.length) {
                $('<div class="small text-muted mt-1 va-comp-note"></div>')
                    .text(c.taken.length + ' OT(s) already have a room for this paper; pick rooms for the rest.').appendTo($group);
            }
            $('<button type="button" class="btn btn-link va-add-room"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add another room</button>')
                .attr('aria-label', 'Add another room for ' + c.component).appendTo($group);
        }
        return $group;
    }

    function buildRoomRow($group, first) {
        var c = $group.data('comp');
        var $row = $('<div class="row g-3 va-comp-row"></div>');
        var readonly = function (v) { return $('<input type="text" class="form-control coe-control va-readonly" readonly>').val(v).attr('title', v); };

        if (first) {
            $row.append(field('Component Name', readonly(c.component).addClass('va-row-component')));
            $row.append(field('Exam Date', readonly(c.exam_date)));
        } else {
            // Same width as Component Name + Exam Date, so the room fields line up under the first row's.
            $row.append($('<div class="col-12 col-lg-4 va-room-extra"></div>').append(
                '<span class="coe-form-label d-none d-lg-block" aria-hidden="true">&nbsp;</span>',
                $('<div class="va-room-extra__inner"></div>').append(
                    $('<span class="va-room-extra__label"></span>').text('Another room — ' + c.component),
                    '<button type="button" class="btn btn-link text-danger va-remove-room"><i class="bi bi-trash3" aria-hidden="true"></i> Remove</button>'
                )
            ));
        }
        var $b = $('<select class="form-select coe-control va-row-building" data-placeholder="Select Building"></select>');
        var $f = $('<select class="form-select coe-control va-row-floor" data-placeholder="Select Floor"></select>');
        var $r = $('<select class="form-select coe-control va-row-room" data-placeholder="Select Room"></select>');
        $row.append(field('Building Name', $b), field('Floor', $f), field('Room', $r));
        $row.append(field('OT Roll No.', otPicker($group.data('taken'), compOts($group))));

        $b.append(new Option('Select Building', '', true, true));
        BUILDINGS.forEach(function (x) { $b.append(new Option(x.name, x.id)); });
        resetSelect($f, 'Select Floor', true);
        resetSelect($r, 'Select Room', true);
        [$b, $f, $r].forEach(select2On);
        paintPicker($row);
        return $row;
    }

    /*
     * Inside one component an OT sits in one room, and a room is used once: an OT picked
     * in one row leaves the other rows' lists; a room picked in one row is greyed in theirs.
     */
    function syncComponent($group) {
        var $rows = $group.find('.va-comp-row');
        $rows.each(function () {
            var $row = $(this), otherOts = {}, otherRooms = {};
            $rows.not($row).each(function () {
                picked($(this)).forEach(function (pk) { otherOts[pk] = true; });
                var room = $(this).find('.va-row-room').val();
                if (room) { otherRooms[room] = true; }
            });
            $row.find('.va-ot-box').each(function () {
                var $item = $(this).closest('.va-ot-item'), elsewhere = !!otherOts[this.value];
                if (!$item.hasClass('is-taken')) { this.disabled = elsewhere; }
                $item.toggleClass('va-ot-item--elsewhere', elsewhere);
            });
            $row.find('.va-row-room option').each(function () {
                if (this.value) { this.disabled = !!otherRooms[this.value]; }
            });
            paintPicker($row);
            checkRoom($row);
        });
        checkCoverage($group);
    }

    /*
     * Map Seating saves a paper only when every OT of it has a seat (the server refuses
     * otherwise). Once a component is being filled, the OTs that still have no room —
     * not seated earlier and not picked in any of its rows here — are listed under it.
     */
    function coverageProblem($group) {
        if ($group.hasClass('va-comp--frozen')) { return ''; }
        var $rows = $group.find('.va-comp-row'), have = {};
        var touched = $rows.filter(function () { return $(this).find('.va-row-building').val() || picked($(this)).length; }).length;
        if (!touched) { return ''; }
        ($group.data('taken') || []).forEach(function (pk) { have[pk] = true; });
        $rows.each(function () { picked($(this)).forEach(function (pk) { have[pk] = true; }); });
        var missing = compOts($group).filter(function (o) { return !have[o.pk]; });
        if (!missing.length) { return ''; }
        var rolls = missing.slice(0, 5).map(function (o) { return o.roll; }).join(', ') +
            (missing.length > 5 ? ' and ' + (missing.length - 5) + ' more' : '');
        return missing.length + ' OT(s) of this paper have no room yet (Roll No. ' + rolls + '). ' +
            'Select them in a room, or add another room — Map Seating saves only when every OT has a seat.';
    }

    function checkCoverage($group) {
        var msg = coverageProblem($group), $note = $group.children('.va-comp-cover');
        if (!$note.length) {
            $note = $('<div class="coe-field-error va-comp-cover" aria-live="polite"></div>');
            var $add = $group.children('.va-add-room');
            if ($add.length) { $add.before($note); } else { $group.append($note); }
        }
        $note.text(msg).toggleClass('d-none', !msg);
        $group.data('coverError', !!msg);
        refreshSubmit();
        return !msg;
    }

    // Map Seating stays disabled while any room is over capacity or any paper has OTs without a room.
    function refreshSubmit() {
        var blocked = $subjects.find('.va-comp').filter(function () { return $(this).data('coverError'); }).length +
            $subjects.find('.va-ot-toggle').filter(function () { return $(this).data('roomError'); }).length;
        $('#vaMapSubmit').prop('disabled', blocked > 0);
        $('#vaMapBlocked').toggleClass('d-none', !blocked);
    }

    /*
     * Can the chosen room take the OTs picked for it? Same arithmetic as the server
     * (checkManualRooms): with a gap of g a room seats ceil(capacity / (g + 1)), and the
     * OTs who need a laptop must fit the room's laptop desks. '' when it fits.
     */
    function roomProblem($row) {
        var room = ROOM_BY_ID[$row.find('.va-row-room').val()], chosen = picked($row);
        if (!room || !chosen.length) { return ''; }
        var gap = $('#vaRuleGap').is(':checked') ? (parseInt($('#vaGapCount').val(), 10) || 1) : 0;
        var seats = Math.ceil(room.capacity / (gap + 1));
        if (chosen.length > seats) {
            return room.name + ' seats ' + seats + ' OT(s)' + (gap ? ' with the gap in seating' : '') +
                ', but ' + chosen.length + ' are selected. Remove ' + (chosen.length - seats) + ' or add another room.';
        }
        var laptops = chosen.filter(function (pk) { return (OT_BY_PK[pk] || {}).laptop; }).length;
        if (laptops > room.laptop) {
            return room.name + ' has ' + room.laptop + ' laptop(s), but ' + laptops + ' of the selected OTs need one.';
        }
        return '';
    }

    // Shows (or clears) the room message under OT Roll No.; leaves any other error there alone.
    function checkRoom($row) {
        var msg = roomProblem($row), $t = $row.find('.va-ot-toggle');
        if (msg) {
            fieldError($t, msg);
        } else if ($t.data('roomError')) {
            fieldError($t, '');
        }
        $t.data('roomError', !!msg);
        refreshSubmit();
        return !msg;
    }

    $subjects.on('click', '.va-add-room', function () {
        var $group = $(this).closest('.va-comp');
        $(this).before(buildRoomRow($group, false));
        syncComponent($group);
    });

    $subjects.on('click', '.va-remove-room', function () {
        var $row = $(this).closest('.va-comp-row'), $group = $row.closest('.va-comp');
        $row.find('select').each(function () { if ($(this).data('select2')) { $(this).select2('destroy'); } });
        var dd = bootstrap.Dropdown.getInstance($row.find('.va-ot-toggle')[0]);
        if (dd) { dd.dispose(); }
        $row.remove();
        syncComponent($group);
    });

    $subjects.on('change', '.va-row-building', function () {
        var floors = ROOMS[this.value] || {};
        var $f = $(this).closest('.va-comp-row').find('.va-row-floor');
        resetSelect($f, 'Select Floor', !this.value);
        Object.keys(floors).forEach(function (id) { $f.append(new Option(floors[id].name, id)); });
        $f.trigger('change');
    });

    $subjects.on('change', '.va-row-floor', function () {
        var $row = $(this).closest('.va-comp-row');
        var floor = (ROOMS[$row.find('.va-row-building').val()] || {})[this.value];
        var $r = $row.find('.va-row-room');
        resetSelect($r, 'Select Room', !floor);
        ((floor || {}).rooms || []).forEach(function (rm) {
            $r.append(new Option(rm.name + ' (' + rm.capacity + ' seats, ' + rm.laptop + ' laptops)', rm.id));
        });
        $r.trigger('change.select2');
        syncComponent($row.closest('.va-comp'));
    });

    $subjects.on('change', '.va-row-room', function () { syncComponent($(this).closest('.va-comp')); });

    $subjects.on('change', 'select', function () { fieldError($(this), ''); });

    /*
     * The OTs a component seats: the drive's whole list, or on a re-exam only that paper's
     * re-exam OTs (c.only, sent by VenueAllocationController::drive for re-exam drives only).
     */
    function compOts($group) {
        var c = $group.data('comp') || {};
        if (!c.only) { return ots; }
        var only = {};
        c.only.forEach(function (pk) { only[pk] = true; });
        return ots.filter(function (o) { return only[o.pk]; });
    }

    /* ── OT Roll No. picker: quick ranges, Select All, search, one box per OT ── */
    function otPicker(taken, list) {
        list = list || ots;
        var $wrap = $('<div class="dropdown va-ot"></div>');
        var $btn = $('<button type="button" class="form-select coe-control text-start va-ot-toggle" data-bs-toggle="dropdown" aria-expanded="false">Select OT</button>').appendTo($wrap);
        var $menu = $('<div class="dropdown-menu dropdown-menu-end va-ot-menu"></div>').appendTo($wrap);
        $('<div class="va-ot-menu__head">Select Roll Number</div>').appendTo($menu);
        var $ranges = $('<div class="va-ot-ranges"></div>').appendTo($menu);
        $('<input type="search" class="form-control form-control-sm va-ot-search mb-2" placeholder="Search roll no. or name" aria-label="Search OT">').appendTo($menu);
        $('<label class="va-ot-item va-ot-item--all"><input type="checkbox" class="form-check-input va-ot-all"> <span>Select All</span></label>').appendTo($menu);
        var $list = $('<div class="va-ot-list"></div>').appendTo($menu);

        var free = list.filter(function (o) { return taken.indexOf(String(o.pk)) === -1; });
        for (var i = 0; i < free.length; i += RANGE) {
            var block = free.slice(i, i + RANGE);
            $('<button type="button" class="va-ot-range"></button>')
                .text(block[0].serial + '-' + block[block.length - 1].serial)
                .data('pks', block.map(function (o) { return String(o.pk); }))
                .appendTo($ranges);
        }
        list.forEach(function (o) {
            var isTaken = taken.indexOf(String(o.pk)) !== -1;
            var $item = $('<label class="va-ot-item"></label>').toggleClass('is-taken', isTaken)
                .attr('data-search', (o.roll + ' ' + o.name).toLowerCase());
            $('<input type="checkbox" class="form-check-input va-ot-box">').val(o.pk).prop('disabled', isTaken).appendTo($item);
            $('<span class="va-ot-item__roll"></span>').text(o.roll).appendTo($item);
            $('<span class="va-ot-item__name"></span>').text(o.name).appendTo($item);
            if (isTaken) { $('<span class="va-ot-item__tag">has a room</span>').appendTo($item); }
            $item.appendTo($list);
        });

        // A fixed popper escapes the scrolling modal body instead of being clipped by it.
        bootstrap.Dropdown.getOrCreateInstance($btn[0], { autoClose: 'outside', popperConfig: function (cfg) { return $.extend(true, {}, cfg, { strategy: 'fixed' }); } });
        return $wrap;
    }

    function picked($row) {
        return $row.find('.va-ot-box:checked').map(function () { return this.value; }).get();
    }

    // "1-50, 61-80" for up to three runs of consecutive roll numbers, else a count.
    function summary($row) {
        var chosen = {};
        picked($row).forEach(function (pk) { chosen[pk] = true; });
        var runs = [], run = null;
        ots.forEach(function (o) {
            if (chosen[o.pk]) {
                if (run) { run[1] = o.serial; } else { run = [o.serial, o.serial]; }
            } else if (run) { runs.push(run); run = null; }
        });
        if (run) { runs.push(run); }
        var n = Object.keys(chosen).length;
        if (!n) { return 'Select OT'; }
        if (runs.length > 3) { return n + ' OTs'; }
        return runs.map(function (x) { return x[0] === x[1] ? String(x[0]) : x[0] + '-' + x[1]; }).join(', ');
    }

    function paintPicker($row) {
        var $boxes = $row.find('.va-ot-box:not(:disabled)');
        var on = $boxes.filter(':checked').length;
        var text = summary($row);
        $row.find('.va-ot-toggle').text(text).attr('title', on ? on + ' OT(s) selected' : '').toggleClass('is-placeholder', !on);
        $row.find('.va-ot-all').prop({ checked: on > 0 && on === $boxes.length, indeterminate: on > 0 && on < $boxes.length });
        var chosen = picked($row), open = {};
        $boxes.each(function () { open[this.value] = true; });
        $row.find('.va-ot-range').each(function () {
            var avail = $(this).data('pks').filter(function (pk) { return open[pk]; });
            $(this).toggleClass('is-active', avail.length > 0 && avail.every(function (pk) { return chosen.indexOf(pk) !== -1; }))
                .prop('disabled', avail.length === 0);
        });
    }

    $subjects.on('change', '.va-ot-box', function () {
        var $row = $(this).closest('.va-comp-row');
        fieldError($row.find('.va-ot-toggle'), '');
        syncComponent($row.closest('.va-comp'));
    });
    $subjects.on('change', '.va-ot-all', function () {
        var $row = $(this).closest('.va-comp-row');
        $row.find('.va-ot-box:not(:disabled)').prop('checked', this.checked).first().trigger('change');
    });
    // A range already wholly picked is unpicked; otherwise it is added.
    $subjects.on('click', '.va-ot-range', function () {
        var $row = $(this).closest('.va-comp-row');
        var pks = $(this).data('pks'), off = $(this).hasClass('is-active');
        $row.find('.va-ot-box:not(:disabled)').each(function () { if (pks.indexOf(this.value) !== -1) { this.checked = !off; } });
        $row.find('.va-ot-box').first().trigger('change');
    });
    $subjects.on('input', '.va-ot-search', function () {
        var q = this.value.trim().toLowerCase();
        $(this).closest('.va-ot-menu').find('.va-ot-list .va-ot-item').each(function () {
            $(this).toggleClass('d-none', q !== '' && $(this).attr('data-search').indexOf(q) === -1);
        });
    });

    /* Show/hide gap box */
    $('#vaRuleGap').on('change', function () {
        $('#vaGapBox').toggleClass('d-none', !this.checked);
    });
    // A gap shrinks what every room seats: check them all again.
    $('#vaRuleGap, #vaGapCount').on('change input', function () {
        $subjects.find('.va-comp').each(function () { syncComponent($(this)); });
    });

    $('#vaMapBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($mapModal[0]).show();
    });

    /* ── Submit ───────────────────────────────────────────────────────── */
    // Rows the administrator touched, in dialog order; untouched rows are not sent.
    function touchedRows() {
        return $subjects.find('.va-comp').not('.va-comp--frozen').find('.va-comp-row').filter(function () {
            var $r = $(this);
            return $r.find('.va-row-building').val() || picked($r).length;
        }).toArray().map(function (el) { return $(el); });
    }

    function validateMap($rows) {
        clearErrors();
        if (!$drive.val()) { fieldError($drive, 'Select a drive.'); return false; }
        if (!$rows.length) {
            Swal.fire({ icon: 'error', title: 'Nothing to map', text: 'Pick a building, floor, room and OTs for at least one component.' });
            return false;
        }
        var ok = true;
        $rows.forEach(function ($r) {
            [[$r.find('.va-row-building'), 'Select a building.'], [$r.find('.va-row-floor'), 'Select a floor.'],
             [$r.find('.va-row-room'), 'Select a room.']].forEach(function (pair) {
                var missing = !pair[0].val();
                fieldError(pair[0], missing ? pair[1] : '');
                ok = ok && !missing;
            });
            var none = !picked($r).length;
            fieldError($r.find('.va-ot-toggle'), none ? 'Select the OTs for this room.' : '');
            ok = ok && !none;
            if (!none) { ok = checkRoom($r) && ok; }
        });
        $subjects.find('.va-comp').each(function () { ok = checkCoverage($(this)) && ok; });
        return ok;
    }

    function mapPayload($rows) {
        var checked = function (id) { return $(id).is(':checked') ? 1 : 0; };
        return {
            drive_id: $drive.val(),
            rows: $rows.map(function ($r) {
                return { component_map_id: $r.closest('.va-comp').attr('data-map-id'), venue_id: $r.find('.va-row-room').val(), ots: picked($r) };
            }),
            rule_avoid_same_service: checked('#vaRuleService'),
            rule_avoid_same_cadre: checked('#vaRuleCadre'),
            rule_alternate_gender: checked('#vaRuleGender'),
            rule_odd_even: checked('#vaRuleOddEven'),
            seat_gap: $('#vaRuleGap').is(':checked') ? (parseInt($('#vaGapCount').val(), 10) || 1) : 0
        };
    }

    // A 422 goes back under the field it is about; anything else is a dialog.
    function mapFail(xhr, $rows) {
        var errors = (xhr.responseJSON || {}).errors;
        if (xhr.status !== 422 || !errors) { fail(xhr, 'The seating could not be saved.'); return; }
        var loose = [];
        Object.keys(errors).forEach(function (k) {
            var m = /^rows\.(\d+)\.(venue_id|ots|component_map_id)/.exec(k);
            var $r = m ? $rows[+m[1]] : null;
            var $f = k === 'drive_id' ? $drive
                : !$r ? null
                : m[2] === 'ots' ? $r.find('.va-ot-toggle')
                : m[2] === 'venue_id' ? $r.find('.va-row-room')
                : $r.closest('.va-comp').find('.va-row-component');
            if ($f && $f.length) { fieldError($f, errors[k][0]); } else { loose.push(errors[k][0]); }
        });
        var $first = $mapModal.find('.coe-field-error').first();
        if ($first.length) { $first[0].scrollIntoView({ block: 'center', behavior: 'smooth' }); }
        if (loose.length) { Swal.fire({ icon: 'error', title: 'Not saved', text: loose.join('\n') }); }
    }

    function esc(t) { return $('<div>').text(t).html(); }

    $('#vaMapForm').on('submit', function (e) {
        e.preventDefault();
        var $rows = touchedRows();
        if (!validateMap($rows)) { return; }
        var $btn = $('#vaMapSubmit').prop('disabled', true);
        $.ajax({ url: STORE_DRIVE_URL, method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, data: mapPayload($rows) })
            .done(function (r) {
                bootstrap.Modal.getOrCreateInstance($mapModal[0]).hide();
                var html = (r.results || []).map(function (x) {
                    return '<li class="mb-1"><strong>' + esc(x.component) + '</strong> — ' + esc(x.text) + '</li>';
                }).join('');
                Swal.fire({ icon: r.status === 'warning' ? 'warning' : 'success', title: 'Mapped',
                    html: html ? '<ul class="text-start ps-3 mb-0">' + html + '</ul>' : esc(r.message) })
                    .then(function () { location.reload(); });
            })
            .fail(function (xhr) { mapFail(xhr, $rows); })
            .always(function () { $btn.prop('disabled', false); refreshSubmit(); });
    });
});
</script>
@endpush
