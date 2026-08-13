@extends('admin.layouts.master')

@section('title', 'Return House')

@section('setup_content')
<style>
    /* Return House — page-scoped remainder. The toolbar, panel, footer, form
       labels and buttons are design-system components; only what is specific to
       this page lives here, in --ds-* tokens (design.md usage rule 2). */
    .rh-page .rh-secondary-actions .btn i {
        font-size: 1rem;
        line-height: 1;
    }

    .rh-page .rh-search-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--ds-control-h, 2.5rem);
        height: var(--ds-control-h, 2.5rem);
        padding: 0;
        color: var(--ds-primary, #004a93);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius, 4px);
    }

    .rh-page .rh-search-toggle[aria-expanded="true"],
    .rh-page .rh-search-toggle:hover {
        border-color: var(--ds-primary, #004a93);
        background: #f2f7fc;
    }

    .rh-page .programme-dt-footer:empty {
        display: none;
    }

    /* The modal's 3-column form, per the design. */
    .rh-form-modal .modal-content {
        border: 0;
        border-radius: var(--ds-radius-2);
        box-shadow: var(--ds-shadow-lg);
    }

    .rh-form-modal .modal-header {
        align-items: center;
        padding: var(--ds-space-3) var(--ds-space-4);
        border-bottom: 1px solid var(--ds-line);
    }

    .rh-form-modal .modal-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--ds-ink);
    }

    .rh-form-modal .modal-body {
        padding: var(--ds-space-4);
    }

    .rh-form-modal .modal-footer {
        justify-content: flex-end;
        gap: var(--ds-space-2);
        padding: var(--ds-space-3) var(--ds-space-4);
        border-top: 1px solid var(--ds-line);
    }

    .rh-form-modal .modal-footer > * {
        margin: 0;
    }

    .rh-form-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: var(--ds-space-3) var(--ds-space-4);
    }

    .rh-form-grid > .rh-form-grid-wide {
        grid-column: span 2;
    }

    @media (max-width: 991.98px) {
        .rh-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .rh-form-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .rh-form-grid > .rh-form-grid-wide {
            grid-column: auto;
        }
    }

    .rh-form-modal .form-control,
    .rh-form-modal .form-select {
        min-height: var(--ds-control-h);
        border-color: var(--ds-line);
        border-radius: var(--ds-radius-1);
        color: var(--ds-ink);
        font-size: 0.875rem;
    }

    .rh-form-modal .form-control:focus,
    .rh-form-modal .form-select:focus {
        border-color: var(--ds-primary);
        box-shadow: var(--ds-focus-ring);
    }

    /* Prefilled fields are locked by the script; keep that legible. Select2
       hides the native <select>, so the lock has to reach the widget too. */
    .prefill-locked {
        background-color: var(--ds-surface-2);
        cursor: not-allowed;
        pointer-events: none;
    }

    select.prefill-locked + .select2-container {
        pointer-events: none;
        opacity: 0.9;
    }

    select.prefill-locked + .select2-container .select2-selection {
        background-color: var(--ds-surface-2);
        cursor: not-allowed;
    }

    .noc-file-wrap {
        position: relative;
    }

    .noc-clear-btn {
        position: absolute;
        top: 50%;
        right: 0.5rem;
        transform: translateY(-50%);
        width: 1.25rem;
        height: 1.25rem;
        border: 0;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        line-height: 1;
        z-index: 2;
    }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4 rh-page">
    {{-- Breadcrumb carries the page title and the primary action; the button
         opens the modal (wired in JS, since the component renders an <a>). --}}
    <x-breadcrum :title="'Return House'" :showBack="false" button-text="Return House" button-id="btnReturnHouse"
        button-icon="add" button-class="btn btn-primary d-inline-flex align-items-center gap-2"
        :items="['Home', 'Estate Management', 'Return House']" />

    <div id="return-house-alerts">
        <x-session_message />
    </div>

    {{-- No status pills on this grid, so the export row sits alone on the right
         (new-design-index-page.md §1). --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 rh-secondary-actions no-print">
        <a href="{{ route('admin.estate.return-house.download') }}"
            class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
        <a href="{{ route('admin.estate.return-house.print') }}" target="_blank" rel="noopener"
            class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </a>
    </div>

    {{-- User-friendly flow: Change Request vs Return House --}}
    <!-- <div class="alert alert-info border-0 rounded-3 shadow-sm mb-4 d-flex align-items-start" role="alert">
        <i class="bi bi-info-circle-fill me-2 flex-shrink-0 mt-1"></i>
        <div>
            <strong>Change Request and Return House</strong>
            <ul class="mb-0 mt-1 small">
                <li>If you have a <strong>pending Change Request</strong> (request for change of house), your name will not appear in the Return House list. Please wait for the request to be <strong>approved or disapproved</strong> before you can return the house.</li>
                <li>If you <strong>return the house first</strong>, you cannot raise a Change Request later for that request (Change Request is only when you currently have a house allotted).</li>
            </ul>
        </div>
    </div> -->

    {{-- Return House — the design's 3-column form. Every id / name / hook from
         the previous markup is preserved: the dependent-dropdown script, the
         prefill lock and the NOC clear button all bind to these exact ids
         (new-design-index-page.md §3d, "inventory the hooks first"). --}}
    <div class="modal fade rh-form-modal" id="requestHouseModal" tabindex="-1" aria-labelledby="requestHouseModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestHouseModalLabel">Return House</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php
                        // Only Admin / Estate / Super Admin can work with "Other Employee" in Return House.
                        $canManageOtherEmployees = isEstateAuthority();
                    @endphp
                    <form id="requestHouseForm" method="POST" action="{{ route('admin.estate.possession-view.store') }}"
                        enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="redirect_to" value="return-house">

                        <div class="mb-3">
                            <label class="ds-form-label">Employee Type<span class="ds-req">*</span></label>
                            <div class="d-flex flex-wrap gap-4 pt-1">
                                <label class="form-check form-check-inline m-0 d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0" type="radio" name="employee_type"
                                        id="empTypeLbsnaa" value="LBSNAA" {{ $canManageOtherEmployees ? '' : 'checked' }}>
                                    <span class="form-check-label-text">LBSNAA</span>
                                </label>
                                @if($canManageOtherEmployees)
                                <label class="form-check form-check-inline m-0 d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0" type="radio" name="employee_type"
                                        id="empTypeOther" value="Other Employee" checked>
                                    <span class="form-check-label-text">Other</span>
                                </label>
                                @endif
                            </div>
                        </div>

                        <div class="rh-form-grid">
                            <div>
                                <label for="request_employee_name" class="ds-form-label">Employee Name<span
                                        class="ds-req">*</span></label>
                                <div class="position-relative d-flex align-items-center gap-2">
                                    <select class="form-select flex-grow-1" id="request_employee_name"
                                        name="estate_other_req_pk" required>
                                        <option value="">Select Employee</option>
                                        @if($canManageOtherEmployees)
                                            @foreach($requesters ?? [] as $r)
                                                <option value="{{ $r->pk }}" data-type="Other Employee" data-request-no="{{ $r->request_no_oth }}" data-section="{{ $r->section ?? '' }}">{{ $r->emp_name }} ({{ $r->request_no_oth }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <span id="request_employee_loading" class="text-secondary flex-shrink-0"
                                        style="display:none;" aria-hidden="true">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                        <span class="visually-hidden">Loading...</span>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label for="request_section_name" class="ds-form-label">Section Name<span
                                        class="ds-req">*</span></label>
                                <input type="text" class="form-control" id="request_section_name"
                                    name="section_name_display" placeholder="Section Name" readonly>
                            </div>

                            <div>
                                <label for="request_estate_name" class="ds-form-label">Estate Name<span
                                        class="ds-req">*</span></label>
                                <select class="form-select" id="request_estate_name" name="estate_campus_master_pk"
                                    required>
                                    <option value="">Select Estate</option>
                                    @foreach($campuses ?? [] as $c)
                                        <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="request_unit_name" class="ds-form-label">Unit Name<span
                                        class="ds-req">*</span></label>
                                <select class="form-select" id="request_unit_name" name="estate_unit_type_master_pk"
                                    required>
                                    <option value="">Select Unit</option>
                                </select>
                            </div>

                            <div>
                                <label for="request_building_name" class="ds-form-label">Building Name<span
                                        class="ds-req">*</span></label>
                                <select class="form-select" id="request_building_name" name="estate_block_master_pk"
                                    required>
                                    <option value="">Select Building</option>
                                </select>
                            </div>

                            <div>
                                <label for="request_house_no" class="ds-form-label">House Number<span
                                        class="ds-req">*</span></label>
                                <select class="form-select" id="request_house_no" name="estate_house_master_pk"
                                    required>
                                    <option value="">Select House</option>
                                </select>
                                <input type="hidden" name="house_no" id="request_house_no_display" value="">
                            </div>

                            <div>
                                <label for="request_unit_sub_type" class="ds-form-label">Unit Sub-type<span
                                        class="ds-req">*</span></label>
                                <select class="form-select" id="request_unit_sub_type"
                                    name="estate_unit_sub_type_master_pk" required>
                                    <option value="">Select Sub-type</option>
                                </select>
                            </div>

                            <div>
                                <label for="request_date_allotment" class="ds-form-label">Date of Allotment<span
                                        class="ds-req">*</span></label>
                                <input type="date" class="form-control" id="request_date_allotment"
                                    name="allotment_date" required readonly>
                            </div>

                            <div>
                                <label for="request_date_possession" class="ds-form-label">Date of Possession<span
                                        class="ds-req">*</span></label>
                                <input type="date" class="form-control" id="request_date_possession"
                                    name="possession_date_oth" required readonly>
                            </div>

                            <div>
                                <label for="request_returning_date" class="ds-form-label">Date of Return<span
                                        class="ds-req">*</span></label>
                                <input type="date" class="form-control" id="request_returning_date"
                                    name="returning_date">
                            </div>

                            <div class="rh-form-grid-wide">
                                <label for="request_noc_document" class="ds-form-label">Upload NOC Document<span
                                        class="ds-req">*</span></label>
                                <div class="noc-file-wrap">
                                    <input type="file" class="form-control pe-4" id="request_noc_document"
                                        name="noc_document">
                                    <button type="button" class="btn btn-sm btn-danger noc-clear-btn d-none"
                                        id="clear_request_noc_document" aria-label="Remove selected file"
                                        title="Remove file">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- The design has no Remarks field, but the store action still
                             accepts one; keep it in the payload so nothing downstream
                             changes shape. --}}
                        <input type="hidden" id="request_remarks" name="remarks" value="">

                        <div id="request_details_loading" class="mt-3 d-none">
                            <span class="text-secondary d-inline-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span>Loading details...</span>
                            </span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="requestHouseForm" class="btn ds-btn-primary">Return House</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar: nothing to filter by on this grid, so Columns + search
                 sit alone on the right (§2). --}}
            <div
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar no-print">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#rhColumnModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <button type="button" class="btn rh-search-toggle" id="rhSearchToggle" aria-expanded="false"
                        aria-controls="rhDtSearch" title="Search returns">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    <div id="rhDtSearch" class="programme-dt-search d-none" data-dt-search-for="returnHouseTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive return-house-table-wrap">
                    {!! $dataTable->table(['class' => 'table table-hover text-nowrap align-middle mb-0 w-100 programme-dt-table', 'aria-describedby' => 'return-house-caption']) !!}
                </div>
            </div>
            <div id="return-house-caption" class="visually-hidden">Return House list</div>

            {{-- DataTables paginates, so the footer is an empty slot the global
                 UI script fills (§4A). --}}
            <div id="rhDtFooter"
                class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 no-print"
                data-dt-footer-for="returnHouseTable"></div>
        </div>
    </div>

    {{-- Column Visibility (column-visibility.md — colvis-item card grid) --}}
    <div class="modal fade" id="rhColumnModal" tabindex="-1" aria-labelledby="rhColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="rhColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="rhColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Return House Modal -->
<div class="modal fade" id="confirmReturnHouseModal" tabindex="-1" aria-labelledby="confirmReturnHouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="confirmReturnHouseModalLabel">Confirm Return House</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0">Are you sure you want to mark this house as returned? This will update the possession record.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-2 d-inline-flex align-items-center gap-2" id="confirmReturnHouseBtn">
                    <i class="bi bi-house-door"></i> Return House
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    /* Select2 dropdown ko modal backdrop ke upar rakho */
    .select2-container--open { z-index: 1060; } /* sirf khula dropdown modal ke upar; closed widget normal flow me (modal ke peeche) */
    /* Height/border ko Bootstrap form-select ke saath align rakho */
    .select2-container--default .select2-selection--single { min-height: calc(1.5em + 0.75rem + 2px); display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0.25rem; }
</style>
@endpush

@push('scripts')
{!! $dataTable->scripts() !!}
{{-- Select2 JS globally footer se load hota hai (admin.layouts.footer). Yahan alag se include ki zaroorat nahi. --}}
<script>
(function() {
    // The breadcrumb's primary action is a plain <a>, so it opens the modal here.
    $(document).on('click', '#btnReturnHouse', function (e) {
        e.preventDefault();
        $('#requestHouseModal').modal('show');
    });

    // Reveal the DataTables search that the global UI relocates into the slot.
    $(document).on('click', '#rhSearchToggle', function () {
        var $wrap = $('#rhDtSearch');
        var open = $wrap.hasClass('d-none');
        $wrap.toggleClass('d-none', !open);
        $(this).attr('aria-expanded', open ? 'true' : 'false');
        if (open) $wrap.find('input').trigger('focus');
    });

    // Column visibility, remembered by LABEL never index (column-visibility.md 3).
    var RH_COLVIS_KEY = 'sargam.returnHouse.hiddenCols.' + @json(auth()->id() ?? 'guest');
    var rhDt = null;

    function rhBuildToggles() {
        if (!rhDt) return;
        var $grid = $('#rhColumnToggleGrid').empty();
        rhDt.columns().every(function (i) {
            var col = this;
            var label = $(col.header()).text().trim();
            if (!label) return;
            var id = 'rhColVis_' + i;
            var $cb = $('<input type="checkbox" class="form-check-input m-0 rh-col-toggle">')
                .attr({ id: id, 'data-column': i, 'data-label': label })
                .prop('checked', col.visible());
            $cb.on('change', function () {
                rhDt.column(i).visible(this.checked);
                var hidden = [];
                $('#rhColumnToggleGrid .rh-col-toggle').each(function () {
                    if (!this.checked) hidden.push($(this).data('label'));
                });
                try { window.localStorage.setItem(RH_COLVIS_KEY, JSON.stringify(hidden)); } catch (e) { /* private mode */ }
            });
            $grid.append(
                $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                    $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                        .attr('for', id).append($cb).append($('<span></span>').text(label))
                )
            );
        });
    }

    $(document).on('init.dt', function (e, settings) {
        if (!settings.nTable || settings.nTable.id !== 'returnHouseTable') return;
        rhDt = new $.fn.dataTable.Api(settings);
        rhBuildToggles();
        var hidden = [];
        try { hidden = JSON.parse(window.localStorage.getItem(RH_COLVIS_KEY) || '[]') || []; } catch (e) { hidden = []; }
        if (hidden.length) {
            rhDt.columns().every(function () {
                var label = $(this.header()).text().trim();
                if (label && hidden.indexOf(label) !== -1) this.visible(false, false);
            });
            rhDt.columns.adjust().draw(false);
            rhBuildToggles();
        }
    });

    var unitTypesByCampus = @json($unitTypesByCampus ?? []);
    var urlBlocks = '{{ route("admin.estate.possession.blocks") }}';
    var urlUnitSubTypes = '{{ route("admin.estate.possession.unit-sub-types") }}';
    var urlHouses = '{{ route("admin.estate.possession.houses") }}';

    var urlEmployees = '{{ route("admin.estate.return-house.employees") }}';
    var urlRequestDetails = '{{ route("admin.estate.return-house.request-details") }}';
    var preselectRequestId = (function() {
        try {
            var params = new URLSearchParams(window.location.search || '');
            return params.get('request_id') || '';
        } catch (e) { return ''; }
    })();
    var campusesList = @json($campuses ?? []);

    // NOTE: ye module pehle TomSelect use karta tha; ab Select2 (baaki app ke saath consistent).
    // ts* variables sirf truthiness/state tracking ke liye rakhe hain (Select2 me instance $(el).data('select2') se milta hai).
    var tsEmployee = null, tsEstate = null, tsUnit = null, tsBuilding = null, tsUnitSub = null;

    // Select2 initialized hai ya nahi.
    function isSelect2(el) {
        return !!(el && $(el).data('select2'));
    }
    // Safe destroy (agar Select2 laga ho to hi).
    function destroySelect2(el) {
        if (isSelect2(el)) { try { $(el).select2('destroy'); } catch (e) {} }
    }
    // Ek select ko Select2 me init karo. Modal ke andar hai isliye dropdownParent modal set karte hain
    // (warna dropdown backdrop ke peeche chala jata hai aur search box focus nahi hota).
    function initReturnHouseTs(el, placeholder) {
        if (!el || typeof $.fn.select2 === 'undefined') return null;
        destroySelect2(el);
        var $modal = $('#requestHouseModal');
        $(el).select2({
            placeholder: placeholder || 'Select',
            allowClear: false,
            width: '100%',
            dropdownParent: $modal.length ? $modal : $(document.body)
        });
        return $(el);
    }
    // Native <select> value hamesha Select2 ke saath sync rehti hai, to plain val() kaafi hai.
    function getSelectVal(el) {
        var v = el ? $(el).val() : '';
        return (v === null || v === undefined) ? '' : v;
    }

    $(document).ready(function() {
        var prefilledFieldsLocked = false;
        var lockedPrefillSelector = '#request_section_name, #request_estate_name, #request_unit_name, #request_building_name, #request_house_no, #request_unit_sub_type, #request_date_allotment, #request_date_possession';
        var employeeListRequestSeq = 0;
        var requestDetailsSeq = 0;
        var isFillingFromRequest = false;

        function setSelectValue($select, value, label) {
            if (value === undefined || value === null || value === '') return;
            var v = String(value);
            // Agar option maujood nahi to label ke saath add karo (Select2 native <option> ko live padhta hai).
            if (!$select.find('option[value="' + v.replace(/"/g, '\\"') + '"]').length && label) {
                $select.append($('<option>', { value: v, text: label }));
            }
            $select.val(v);
            // 'change' -> cascade handlers + Select2 UI dono update. (Cascade guard isFillingFromRequest se handle hota hai.)
            $select.trigger('change');
        }

        function setPrefilledFieldsLocked(locked) {
            prefilledFieldsLocked = !!locked;
            $(lockedPrefillSelector)
                .toggleClass('prefill-locked', prefilledFieldsLocked)
                .attr('aria-readonly', prefilledFieldsLocked ? 'true' : 'false');
        }

        function blurLockedFocus() {
            if (!prefilledFieldsLocked) return;
            $(this).blur();
        }

        function syncReturningDateMin(allotmentDate) {
            var minDate = (allotmentDate || '').trim();
            var $returnDate = $('#request_returning_date');
            if (minDate) {
                $returnDate.attr('min', minDate);
                var current = ($returnDate.val() || '').trim();
                if (current && current < minDate) {
                    $returnDate.val('');
                }
            } else {
                $returnDate.removeAttr('min');
            }
        }

        function syncNocClearButton() {
            var hasFile = !!($('#request_noc_document')[0] && $('#request_noc_document')[0].files && $('#request_noc_document')[0].files.length);
            $('#clear_request_noc_document').toggleClass('d-none', !hasFile);
        }

        $(document).on('focus', '#request_estate_name, #request_unit_name, #request_building_name, #request_house_no, #request_unit_sub_type, #request_date_allotment, #request_date_possession', blurLockedFocus);
        $(document).on('change', '#request_noc_document', syncNocClearButton);
        $(document).on('click', '#clear_request_noc_document', function() {
            $('#request_noc_document').val('');
            syncNocClearButton();
        });

        // --- Employee Type change: load employee list (LBSNAA / Other) ---
        $('input[name="employee_type"]').on('change', function() {
            setPrefilledFieldsLocked(false);
            var type = $(this).val();
            var isOther = (type === 'Other Employee');
            var empEl = document.getElementById('request_employee_name');
            $('#request_employee_name').attr('name', isOther ? 'estate_other_req_pk' : 'employee_select_id');
            $('#request_employee_loading').show();
            destroySelect2(empEl); tsEmployee = null;
            $('#request_employee_name').html('<option value="">Select Employee</option>');
            var seq = ++employeeListRequestSeq;
            $.get(urlEmployees, { employee_type: type }, function(res) {
                $('#request_employee_loading').hide();
                if (seq !== employeeListRequestSeq) return;
                if ($('input[name="employee_type"]:checked').val() !== type) return;
                var $sel = $('#request_employee_name');
                $sel.html('<option value="">Select Employee</option>');
                if (res.status && res.data && res.data.length) {
                    res.data.forEach(function(o) {
                        var section = (o.section !== undefined) ? (o.section || '') : '';
                        $sel.append('<option value="' + o.id + '" data-section="' + section + '">' + (o.name || '') + (o.request_no ? ' (' + o.request_no + ')' : '') + '</option>');
                    });
                }
                tsEmployee = initReturnHouseTs(empEl, 'Select Employee');
                $('#request_section_name').val('');
                clearRequestDetailsFields();

                // Auto-select requester for self-service flow when request_id is present in URL.
                if (type === 'LBSNAA' && preselectRequestId) {
                    var targetVal = String(preselectRequestId);
                    if ($sel.find('option[value="' + targetVal.replace(/"/g, '\\"') + '"]').length) {
                        // change fire hota hai -> details prefill trigger hoti hai.
                        $sel.val(targetVal).trigger('change');
                    }
                }
            }).always(function() {
                $('#request_employee_loading').hide();
            });
        });

        function initModalDropdowns() {
            if (typeof $.fn.select2 === 'undefined') return;
            var estateEl = document.getElementById('request_estate_name');
            var unitEl = document.getElementById('request_unit_name');
            var buildingEl = document.getElementById('request_building_name');
            var unitSubEl = document.getElementById('request_unit_sub_type');
            if (estateEl && !isSelect2(estateEl)) tsEstate = initReturnHouseTs(estateEl, 'Select Estate');
            if (unitEl && !isSelect2(unitEl)) tsUnit = initReturnHouseTs(unitEl, 'Select Unit');
            if (buildingEl && !isSelect2(buildingEl)) tsBuilding = initReturnHouseTs(buildingEl, 'Select Building');
            if (unitSubEl && !isSelect2(unitSubEl)) tsUnitSub = initReturnHouseTs(unitSubEl, 'Select Sub-type');
        }

        function setHouseSelectOnly(html, selectedValue) {
            var el = document.getElementById('request_house_no');
            destroySelect2(el);
            var $h = $('#request_house_no');
            $h.html(html || '<option value="">Select House</option>');
            var val = (selectedValue !== undefined && selectedValue !== null) ? String(selectedValue) : '';
            $h.val(val);
            if (el) el.value = val;
            // Select2 re-init karke selected value reflect karao (native val pehle set ki hai).
            initReturnHouseTs(el, 'Select House');
            $h.trigger('change.select2');
        }

        $('#requestHouseModal').on('shown.bs.modal', function() {
            initModalDropdowns();
            $('input[name="employee_type"]:checked').trigger('change');
        });

        // --- Employee Name change: fetch full mapping and fill all fields ---
        $(document).on('change', '#request_employee_name', function() {
            setPrefilledFieldsLocked(false);
            var id = getSelectVal(this);
            var type = $('input[name="employee_type"]:checked').val();
            if (!id || !type) {
                $('#request_section_name').val('');
                clearRequestDetailsFields();
                $('#request_details_loading').addClass('d-none');
                return;
            }
            $('#request_details_loading').removeClass('d-none');
            var seq = ++requestDetailsSeq;
            $.get(urlRequestDetails, { employee_type: type, id: id }, function(res) {
                $('#request_details_loading').addClass('d-none');
                if (seq !== requestDetailsSeq) return;
                if (getSelectVal(document.getElementById('request_employee_name')) !== String(id)) return;
                if ($('input[name="employee_type"]:checked').val() !== type) return;
                if (!res.status || !res.data) {
                    $('#request_section_name').val('');
                    clearRequestDetailsFields();
                    $('#request_details_loading').addClass('d-none');
                    isFillingFromRequest = false;
                    return;
                }
                isFillingFromRequest = true;
                var d = res.data;
                $('#request_section_name').val(d.section || '');
                $('#request_date_allotment').val(d.allotment_date || '');
                $('#request_date_possession').val(d.possession_date_oth || '');
                syncReturningDateMin(d.allotment_date || '');
                if (!d.estate_campus_master_pk) {
                    var estateElReset = document.getElementById('request_estate_name');
                    destroySelect2(estateElReset); tsEstate = null;
                    $('#request_estate_name').val('');
                    if (estateElReset) tsEstate = initReturnHouseTs(estateElReset, 'Select Estate');
                    destroyTsAndHtml('request_unit_name', '<option value="">Select Unit</option>'); if (document.getElementById('request_unit_name')) tsUnit = initReturnHouseTs(document.getElementById('request_unit_name'), 'Select Unit');
                    destroyTsAndHtml('request_building_name', '<option value="">Select Building</option>'); if (document.getElementById('request_building_name')) tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), 'Select Building');
                    destroyTsAndHtml('request_unit_sub_type', '<option value="">Select Sub-type</option>'); if (document.getElementById('request_unit_sub_type')) tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), 'Select Sub-type');
                    setHouseSelectOnly('<option value="">Select House</option>', '');
                    $('#request_house_no_display').val('');
                    isFillingFromRequest = false;
                    return;
                }
                var campusPk = String(d.estate_campus_master_pk);
                var unitPk = d.estate_unit_type_master_pk ? String(d.estate_unit_type_master_pk) : '';
                var $estate = $('#request_estate_name');
                setSelectValue($estate, campusPk, d.campus_name || ('Campus ' + campusPk));
                var types = unitTypesByCampus[campusPk] || unitTypesByCampus[d.estate_campus_master_pk] || [];
                var $unit = $('#request_unit_name');
                destroyTsAndHtml('request_unit_name', '<option value="">Select Unit</option>');
                types.forEach(function(t) {
                    var v = String(t.pk);
                    $unit.append('<option value="' + v + '">' + (t.unit_type || '') + '</option>');
                });
                if (unitPk && d.unit_type_name && !$unit.find('option[value="' + unitPk + '"]').length) {
                    $unit.append('<option value="' + unitPk + '">' + (d.unit_type_name || '') + '</option>');
                }
                tsUnit = initReturnHouseTs(document.getElementById('request_unit_name'), 'Select Unit');
                if (unitPk) setSelectValue($unit, unitPk, d.unit_type_name || ('Unit ' + unitPk));
                var campusId = d.estate_campus_master_pk;
                var unitTypeId = d.estate_unit_type_master_pk;
                var blockId = d.estate_block_master_pk;
                var unitSubTypeId = d.estate_unit_sub_type_master_pk;
                $.get(urlBlocks, { campus_id: campusId, unit_type_id: unitTypeId }, function(resB) {
                    var $blk = $('#request_building_name');
                    destroyTsAndHtml('request_building_name', '<option value="">Select Building</option>');
                    if (resB.status && resB.data) resB.data.forEach(function(b) {
                        $blk.append('<option value="' + String(b.pk) + '">' + (b.block_name || '') + '</option>');
                    });
                    tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), 'Select Building');
                    if (blockId) setSelectValue($blk, blockId);
                    $.get(urlUnitSubTypes, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId }, function(resU) {
                        var $ust = $('#request_unit_sub_type');
                        destroyTsAndHtml('request_unit_sub_type', '<option value="">Select Sub-type</option>');
                        if (resU.status && resU.data) resU.data.forEach(function(u) {
                            $ust.append('<option value="' + String(u.pk) + '">' + (u.unit_sub_type || '') + '</option>');
                        });
                        tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), 'Select Sub-type');
                        if (unitSubTypeId) setSelectValue($ust, unitSubTypeId);

                        var housePk = d.estate_house_master_pk ? String(d.estate_house_master_pk) : '';
                        var houseNoDisplay = (d.house_no != null && d.house_no !== '') ? String(d.house_no) : (housePk || '');
                        var houseOptionsHtml = '<option value="">Select House</option>';
                        if (housePk) {
                            houseOptionsHtml += '<option value="' + housePk + '" data-house-no="' + (d.house_no || '') + '">' + houseNoDisplay + '</option>';
                        }
                        setHouseSelectOnly(houseOptionsHtml, housePk);
                        $('#request_house_no_display').val(houseNoDisplay);
                        setPrefilledFieldsLocked(true);
                        setTimeout(function() {
                            var houseEl = document.getElementById('request_house_no');
                            if (houseEl && housePk) {
                                houseEl.value = housePk;
                                // Select2 widget ko final value pe sync karo (UI reflect ho).
                                if (isSelect2(houseEl)) $(houseEl).trigger('change.select2');
                                var disp = document.getElementById('request_house_no_display');
                                if (disp) disp.value = houseNoDisplay;
                            }
                            isFillingFromRequest = false;
                        }, 150);
                    }).fail(function() { isFillingFromRequest = false; });
                }).fail(function() { isFillingFromRequest = false; });
            }).fail(function() {
                isFillingFromRequest = false;
            }).always(function() {
                $('#request_details_loading').addClass('d-none');
            });
        });

        function destroyTsAndHtml(id, html) {
            var el = document.getElementById(id);
            destroySelect2(el);
            if (id === 'request_unit_name') tsUnit = null; else if (id === 'request_building_name') tsBuilding = null; else if (id === 'request_unit_sub_type') tsUnitSub = null;
            $('#' + id).html(html || '<option value="">Select</option>');
        }

        function clearRequestDetailsFields() {
            setPrefilledFieldsLocked(false);
            var estateEl = document.getElementById('request_estate_name');
            // Estate ko silently empty karo (change.select2 -> sirf widget update, cascade nahi).
            $('#request_estate_name').val('');
            if (isSelect2(estateEl)) $(estateEl).trigger('change.select2');
            destroyTsAndHtml('request_unit_name', '<option value="">Select Unit</option>');
            tsUnit = initReturnHouseTs(document.getElementById('request_unit_name'), 'Select Unit');
            destroyTsAndHtml('request_building_name', '<option value="">Select Building</option>');
            tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), 'Select Building');
            destroyTsAndHtml('request_unit_sub_type', '<option value="">Select Sub-type</option>');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), 'Select Sub-type');
            setHouseSelectOnly('<option value="">Select House</option>', '');
            $('#request_house_no_display').val('');
            $('#request_date_allotment, #request_date_possession').val('');
            syncReturningDateMin('');
        }

        // On load: Other is default, so select name is estate_other_req_pk
        $('#request_employee_name').attr('name', 'estate_other_req_pk');
        $('input[name="employee_type"]:checked').trigger('change');
        syncNocClearButton();

        // If request_id query param present (self-service Return from Request For Estate),
        // open modal directly and let employee dropdown auto-select via preselectRequestId logic above.
        (function autoOpenReturnModalFromRequestId() {
            if (!preselectRequestId) return;

            var modalEl = document.getElementById('requestHouseModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            // Force LBSNAA employee type (main flow) and trigger employees load
            $('input[name="employee_type"][value="LBSNAA"]').prop('checked', true).trigger('change');
        })();

        $(document).on('change', '#request_estate_name', function() {
            if (isFillingFromRequest) return;
            var campusPk = getSelectVal(this);
            destroyTsAndHtml('request_unit_name', '<option value="">Select Unit</option>');
            destroyTsAndHtml('request_building_name', '<option value="">Select Building</option>');
            destroyTsAndHtml('request_unit_sub_type', '<option value="">Select Sub-type</option>');
            setHouseSelectOnly('<option value="">Select House</option>', '');
            var unitEl = document.getElementById('request_unit_name');
            tsUnit = initReturnHouseTs(unitEl, 'Select Unit');
            tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), 'Select Building');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), 'Select Sub-type');
            $('#request_house_no_display').val('');
            if (!campusPk) return;
            var types = unitTypesByCampus[campusPk] || [];
            var $unitSel = $('#request_unit_name');
            types.forEach(function(t) { $unitSel.append($('<option>', { value: String(t.pk), text: t.unit_type || '' })); });
            $unitSel.trigger('change.select2');
        });

        $(document).on('change', '#request_unit_name', function() {
            if (isFillingFromRequest) return;
            var campusId = getSelectVal(document.getElementById('request_estate_name'));
            var unitTypeId = getSelectVal(this);
            destroyTsAndHtml('request_building_name', '<option value="">Select Building</option>');
            destroyTsAndHtml('request_unit_sub_type', '<option value="">Select Sub-type</option>');
            setHouseSelectOnly('<option value="">Select House</option>', '');
            tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), 'Select Building');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), 'Select Sub-type');
            if (!campusId) return;
            if (!unitTypeId) return;
            $.get(urlBlocks, { campus_id: campusId, unit_type_id: unitTypeId }, function(res) {
                if (res.status && res.data) {
                    var $blkSel = $('#request_building_name');
                    res.data.forEach(function(b) { $blkSel.append($('<option>', { value: String(b.pk), text: b.block_name || '' })); });
                    $blkSel.trigger('change.select2');
                }
            });
        });

        $(document).on('change', '#request_building_name', function() {
            if (isFillingFromRequest) return;
            var campusId = getSelectVal(document.getElementById('request_estate_name'));
            var blockId = getSelectVal(this);
            var unitTypeId = getSelectVal(document.getElementById('request_unit_name'));
            destroyTsAndHtml('request_unit_sub_type', '<option value="">Select Sub-type</option>');
            setHouseSelectOnly('<option value="">Select House</option>', '');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), 'Select Sub-type');
            if (!campusId || !blockId) return;
            $.get(urlUnitSubTypes, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId }, function(res) {
                if (res.status && res.data) {
                    var $ustSel = $('#request_unit_sub_type');
                    res.data.forEach(function(u) { $ustSel.append($('<option>', { value: String(u.pk), text: u.unit_sub_type || '' })); });
                    $ustSel.trigger('change.select2');
                }
            });
        });

        $(document).on('change', '#request_unit_sub_type', function() {
            if (isFillingFromRequest) return;
            var campusId = getSelectVal(document.getElementById('request_estate_name'));
            var blockId = getSelectVal(document.getElementById('request_building_name'));
            var unitSubTypeId = getSelectVal(this);
            var unitTypeId = getSelectVal(document.getElementById('request_unit_name'));
            setHouseSelectOnly('<option value="">Select House</option>', '');
            if (!campusId || !blockId || !unitSubTypeId) return;
            $.get(urlHouses, { campus_id: campusId, block_id: blockId, unit_sub_type_id: unitSubTypeId, unit_type_id: unitTypeId }, function(res) {
                var houseHtml = '<option value="">Select House</option>';
                if (res.status && res.data) {
                    res.data.forEach(function(h) {
                        houseHtml += '<option value="' + h.pk + '" data-house-no="' + (h.house_no || '') + '">' + (h.house_no || h.pk) + '</option>';
                    });
                }
                setHouseSelectOnly(houseHtml, '');
            });
        });

        function getRequestHouseNoDisplay() {
            var el = document.getElementById('request_house_no');
            var val = getSelectVal(el);
            if (!val) return '';
            var opt = $(el).find('option').filter(function() { return $(this).val() == val; }).first();
            return opt.data('house-no') || opt.text() || '';
        }

        $(document).on('change', '#request_house_no', function() {
            $('#request_house_no_display').val(getRequestHouseNoDisplay());
        });

        $('#requestHouseForm').on('submit', function(e) {
            var allotmentDate = ($('#request_date_allotment').val() || '').trim();
            var returningDate = ($('#request_returning_date').val() || '').trim();
            if (allotmentDate && returningDate && returningDate < allotmentDate) {
                e.preventDefault();
                alert('Returning Date cannot be before Date Of Allotment.');
                return;
            }
            if (this.checkValidity()) {
                $('#request_house_no_display').val(getRequestHouseNoDisplay());
            }
        });

        // --- Return House action (Other Employee) ---
        var returnHouseUrl = null;
        $(document).on('click', '.btn-return-house', function() {
            returnHouseUrl = $(this).data('url');
            $('#confirmReturnHouseModal').modal('show');
        });

        $('#confirmReturnHouseBtn').on('click', function() {
            if (!returnHouseUrl) return;
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: returnHouseUrl,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    $('#confirmReturnHouseModal').modal('hide');
                    if (res.success) {
                        if ($.fn.DataTable && $('#returnHouseTable').length && $('#returnHouseTable').DataTable()) {
                            $('#returnHouseTable').DataTable().ajax.reload(null, false);
                        }
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + (res.message || 'House marked as returned.') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#return-house-alerts').html(alertHtml);
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><span class="flex-grow-1">' + msg + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#return-house-alerts').html(alertHtml);
                },
                complete: function() { $btn.prop('disabled', false); }
            });
            returnHouseUrl = null;
        });
    });
})();
</script>
@endpush
