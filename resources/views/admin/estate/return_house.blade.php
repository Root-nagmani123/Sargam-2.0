@extends('admin.layouts.master')

@section('title', 'Return House - Sargam')

@section('setup_content')
<style>
    .form-check-label-border { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 0.5rem; cursor: pointer; transition: border-color .15s, background-color .15s; }
    .form-check-label-border:hover { border-color: var(--bs-primary); }
    .form-check-label-border:has(.form-check-input:checked) { border-color: var(--bs-primary); background-color: rgba(var(--bs-primary-rgb), 0.08); }
    .form-check-label-border .form-check-input { margin: 0; }
    .form-check-label-border .form-check-label-text { font-weight: 500; }
    .prefill-locked { background-color: var(--bs-secondary-bg, #f8f9fa); cursor: not-allowed; pointer-events: none; }
    .noc-file-wrap { position: relative; }
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
<div class="container-fluid py-2">
    <!-- Breadcrumb -->
    <x-breadcrum :title="'Return House'" :items="['Home', 'Estate Management', 'Return House']" />

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 fw-semibold text-body mb-1">Return House</h2>
            <p class="text-body-secondary small mb-0">Manage house returns and request new allotments</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestHouseModal">
            <i class="bi bi-plus-circle me-2"></i>Return House
        </button>
    </div>

    <div id="return-house-alerts">
        <x-session_message />
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

    <!-- Request House Modal - Add Request Details (dynamic dropdowns from DB) -->
    <div class="modal fade" id="requestHouseModal" tabindex="-1" aria-labelledby="requestHouseModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div class="modal-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" id="requestHouseModalLabel">Add Return House Details</h5>
                        <p class="text-body-secondary small mb-0 mt-1">Please add Return House Details</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @php
                        // Only Admin / Estate / Super Admin can work with "Other Employee" in Return House.
                        $canManageOtherEmployees = hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin');
                    @endphp
                    <form id="requestHouseForm" method="POST" action="{{ route('admin.estate.possession-view.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="redirect_to" value="return-house">
                        <!-- Employee Type -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">Employee Type <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-2 pt-1">
                                <label class="form-check form-check-inline form-check-label-border m-0">
                                    <input class="form-check-input" type="radio" name="employee_type" id="empTypeLbsnaa" value="LBSNAA" {{ $canManageOtherEmployees ? '' : 'checked' }}>
                                    <span class="form-check-label-text">LBSNAA</span>
                                </label>
                                @if($canManageOtherEmployees)
                                <label class="form-check form-check-inline form-check-label-border m-0">
                                    <input class="form-check-input" type="radio" name="employee_type" id="empTypeOther" value="Other Employee" checked>
                                    <span class="form-check-label-text">Other Employee</span>
                                </label>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_employee_name" class="form-label fw-medium">Employee Name <span class="text-danger">*</span></label>
                                <div class="position-relative d-flex align-items-center gap-2">
                                    <select class="form-select flex-grow-1" id="request_employee_name" name="estate_other_req_pk" required>
                                        <option value="">--Select Employee Type then Name--</option>
                                        @if($canManageOtherEmployees)
                                            @foreach($requesters ?? [] as $r)
                                                <option value="{{ $r->pk }}" data-type="Other Employee" data-request-no="{{ $r->request_no_oth }}" data-section="{{ $r->section ?? '' }}">{{ $r->emp_name }} ({{ $r->request_no_oth }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <span id="request_employee_loading" class="text-secondary flex-shrink-0" style="display:none;" aria-hidden="true">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                        <span class="visually-hidden">Loading...</span>
                                    </span>
                                </div>
                                <div class="form-text">Select Name - all fields will auto-fill from mapping</div>
                            </div>
                            <div class="col-md-6">
                                <label for="request_section_name" class="form-label fw-medium">Section Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="request_section_name" name="section_name_display" placeholder="Section Name" readonly>
                            </div>
                        </div>
                        <div id="request_details_loading" class="row g-3 mb-2 d-none">
                            <div class="col-12">
                                <span class="text-secondary d-inline-flex align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                    <span>Loading details...</span>
                                </span>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_estate_name" class="form-label fw-medium">Estate Name <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_estate_name" name="estate_campus_master_pk" required>
                                    <option value="">--Select--</option>
                                    @foreach($campuses ?? [] as $c)
                                        <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="request_unit_name" class="form-label fw-medium">Unit Name <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_unit_name" name="estate_unit_type_master_pk" required>
                                    <option value="">--Select Estate first--</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_building_name" class="form-label fw-medium">Building Name <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_building_name" name="estate_block_master_pk" required>
                                    <option value="">--Select--</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="request_house_no" class="form-label fw-medium">House No. <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_house_no" name="estate_house_master_pk" required>
                                    <option value="">--Select--</option>
                                </select>
                                <input type="hidden" name="house_no" id="request_house_no_display" value="">
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_unit_sub_type" class="form-label fw-medium">Unit Sub Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_unit_sub_type" name="estate_unit_sub_type_master_pk" required>
                                    <option value="">--Select--</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="request_date_allotment" class="form-label fw-medium">Date Of Allotment <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="request_date_allotment" name="allotment_date" required readonly>
                                <div class="form-text">Pre-filled from mapping (read-only)</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_date_possession" class="form-label fw-medium">Date Of Possession <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="request_date_possession" name="possession_date_oth" required readonly>
                                <div class="form-text">Pre-filled from mapping (read-only)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="request_returning_date" class="form-label fw-medium">Returning Date</label>
                                <input type="date" class="form-control" id="request_returning_date" name="returning_date">
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_noc_document" class="form-label fw-medium">Upload NOC Document</label>
                                <div class="noc-file-wrap">
                                    <input type="file" class="form-control pe-4" id="request_noc_document" name="noc_document">
                                    <button type="button" class="btn btn-sm btn-danger noc-clear-btn d-none" id="clear_request_noc_document" aria-label="Remove selected file" title="Remove file">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <div class="form-text">Any file type allowed (max 5 MB)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="request_remarks" class="form-label fw-medium">Remarks</label>
                                <textarea class="form-control" id="request_remarks" name="remarks" rows="3" placeholder="Optional remarks"></textarea>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success px-4 rounded-pill">
                                <i class="bi bi-check-lg me-2"></i>Save
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg me-2"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card (Other Employee) -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4">
            <h5 class="card-title fw-semibold mb-0">Return House List</h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive return-house-table-wrap">
                {!! $dataTable->table(['class' => 'table text-nowrap align-middle mb-0', 'aria-describedby' => 'return-house-caption']) !!}
            </div>
            <div id="return-house-caption" class="visually-hidden">Return House list</div>
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
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>.ts-dropdown { z-index: 1060 !important; }</style>
@endpush

@push('scripts')
{!! $dataTable->scripts() !!}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function() {
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

    var tsEmployee = null, tsEstate = null, tsUnit = null, tsBuilding = null, tsUnitSub = null;
    var commonCfg = {
        allowEmptyOption: true,
        create: false,
        dropdownParent: 'body',
        maxOptions: null,
        hideSelected: false,
        onInitialize: function () { this.activeOption = null; }
    };
    function initReturnHouseTs(el, placeholder) {
        if (!el || typeof TomSelect === 'undefined') return null;
        if (el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
        return new TomSelect(el, Object.assign({}, commonCfg, { placeholder: placeholder || '--Select--' }));
    }
    function getSelectVal(el) {
        return (el && el.tomselect) ? el.tomselect.getValue() : $(el).val();
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
            var el = $select[0];
            if (!$select.find('option[value="' + v + '"]').length && label) {
                $select.append('<option value="' + v + '">' + label + '</option>');
                if (el && el.tomselect) el.tomselect.addOption({ value: v, text: label });
            }
            if (el && el.tomselect) {
                el.tomselect.setValue(v, true);
                $select.trigger('change');
                return;
            }
            $select.val(v);
            $select.find('option[value="' + v + '"]').prop('selected', true);
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
            if (empEl && empEl.tomselect) { try { empEl.tomselect.destroy(); } catch (e) {} tsEmployee = null; }
            $('#request_employee_name').html('<option value="">--Select--</option>');
            var seq = ++employeeListRequestSeq;
            $.get(urlEmployees, { employee_type: type }, function(res) {
                $('#request_employee_loading').hide();
                if (seq !== employeeListRequestSeq) return;
                if ($('input[name="employee_type"]:checked').val() !== type) return;
                var $sel = $('#request_employee_name');
                $sel.html('<option value="">--Select--</option>');
                if (res.status && res.data && res.data.length) {
                    res.data.forEach(function(o) {
                        var section = (o.section !== undefined) ? (o.section || '') : '';
                        $sel.append('<option value="' + o.id + '" data-section="' + section + '">' + (o.name || '') + (o.request_no ? ' (' + o.request_no + ')' : '') + '</option>');
                    });
                }
                if (empEl && typeof TomSelect !== 'undefined') tsEmployee = initReturnHouseTs(empEl, '--Select--');
                $('#request_section_name').val('');
                clearRequestDetailsFields();

                // Auto-select requester for self-service flow when request_id is present in URL.
                if (type === 'LBSNAA' && preselectRequestId) {
                    var targetVal = String(preselectRequestId);
                    var selEl = document.getElementById('request_employee_name');
                    if (selEl && selEl.tomselect) {
                        if (selEl.tomselect.options[targetVal]) {
                            // false = NOT silent → change events fire, so details prefill.
                            selEl.tomselect.setValue(targetVal, false);
                        }
                    } else if ($sel.find('option[value="' + targetVal.replace(/"/g, '\\"') + '"]').length) {
                        $sel.val(targetVal).trigger('change');
                    }
                }
            }).always(function() {
                $('#request_employee_loading').hide();
            });
        });

        function initModalDropdowns() {
            if (typeof TomSelect === 'undefined') return;
            var estateEl = document.getElementById('request_estate_name');
            var unitEl = document.getElementById('request_unit_name');
            var buildingEl = document.getElementById('request_building_name');
            var unitSubEl = document.getElementById('request_unit_sub_type');
            if (estateEl && !estateEl.tomselect) tsEstate = initReturnHouseTs(estateEl, '--Select--');
            if (unitEl && !unitEl.tomselect) tsUnit = initReturnHouseTs(unitEl, '--Select Estate first--');
            if (buildingEl && !buildingEl.tomselect) tsBuilding = initReturnHouseTs(buildingEl, '--Select--');
            if (unitSubEl && !unitSubEl.tomselect) tsUnitSub = initReturnHouseTs(unitSubEl, '--Select--');
        }

        function setHouseSelectOnly(html, selectedValue) {
            var el = document.getElementById('request_house_no');
            if (el && el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
            var $h = $('#request_house_no');
            $h.html(html || '<option value="">--Select--</option>');
            var val = (selectedValue !== undefined && selectedValue !== null) ? String(selectedValue) : '';
            $h.val(val);
            if (el) el.value = val;
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
                    if (tsEstate) { try { document.getElementById('request_estate_name').tomselect.destroy(); } catch (e) {} tsEstate = null; }
                    $('#request_estate_name').val('');
                    destroyTsAndHtml('request_unit_name', '<option value="">--Select Estate first--</option>'); if (document.getElementById('request_unit_name')) tsUnit = initReturnHouseTs(document.getElementById('request_unit_name'), '--Select Estate first--');
                    destroyTsAndHtml('request_building_name', '<option value="">--Select--</option>'); if (document.getElementById('request_building_name')) tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), '--Select--');
                    destroyTsAndHtml('request_unit_sub_type', '<option value="">--Select--</option>'); if (document.getElementById('request_unit_sub_type')) tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), '--Select--');
                    setHouseSelectOnly('<option value="">--Select--</option>', '');
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
                destroyTsAndHtml('request_unit_name', '<option value="">--Select--</option>');
                types.forEach(function(t) {
                    var v = String(t.pk);
                    $unit.append('<option value="' + v + '">' + (t.unit_type || '') + '</option>');
                });
                if (unitPk && d.unit_type_name && !$unit.find('option[value="' + unitPk + '"]').length) {
                    $unit.append('<option value="' + unitPk + '">' + (d.unit_type_name || '') + '</option>');
                }
                tsUnit = initReturnHouseTs(document.getElementById('request_unit_name'), '--Select--');
                if (unitPk) setSelectValue($unit, unitPk, d.unit_type_name || ('Unit ' + unitPk));
                var campusId = d.estate_campus_master_pk;
                var unitTypeId = d.estate_unit_type_master_pk;
                var blockId = d.estate_block_master_pk;
                var unitSubTypeId = d.estate_unit_sub_type_master_pk;
                $.get(urlBlocks, { campus_id: campusId, unit_type_id: unitTypeId }, function(resB) {
                    var $blk = $('#request_building_name');
                    destroyTsAndHtml('request_building_name', '<option value="">--Select--</option>');
                    if (resB.status && resB.data) resB.data.forEach(function(b) {
                        $blk.append('<option value="' + String(b.pk) + '">' + (b.block_name || '') + '</option>');
                    });
                    tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), '--Select--');
                    if (blockId) setSelectValue($blk, blockId);
                    $.get(urlUnitSubTypes, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId }, function(resU) {
                        var $ust = $('#request_unit_sub_type');
                        destroyTsAndHtml('request_unit_sub_type', '<option value="">--Select--</option>');
                        if (resU.status && resU.data) resU.data.forEach(function(u) {
                            $ust.append('<option value="' + String(u.pk) + '">' + (u.unit_sub_type || '') + '</option>');
                        });
                        tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), '--Select--');
                        if (unitSubTypeId) setSelectValue($ust, unitSubTypeId);

                        var housePk = d.estate_house_master_pk ? String(d.estate_house_master_pk) : '';
                        var houseNoDisplay = (d.house_no != null && d.house_no !== '') ? String(d.house_no) : (housePk || '');
                        var houseOptionsHtml = '<option value="">--Select--</option>';
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
                                var disp = document.getElementById('request_house_no_display');
                                if (disp) disp.value = houseNoDisplay;
                            }
                            isFillingFromRequest = false;
                        }, 150);
                    });
                });
            }).always(function() {
                $('#request_details_loading').addClass('d-none');
                setTimeout(function() { isFillingFromRequest = false; }, 200);
            });
        });

        function destroyTsAndHtml(id, html) {
            var el = document.getElementById(id);
            if (el && el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
            if (id === 'request_unit_name') tsUnit = null; else if (id === 'request_building_name') tsBuilding = null; else if (id === 'request_unit_sub_type') tsUnitSub = null;
            $('#' + id).html(html || '<option value="">--Select--</option>');
        }

        function clearRequestDetailsFields() {
            setPrefilledFieldsLocked(false);
            var estateEl = document.getElementById('request_estate_name');
            if (estateEl && estateEl.tomselect) estateEl.tomselect.setValue('', true);
            else $('#request_estate_name').val('');
            destroyTsAndHtml('request_unit_name', '<option value="">--Select Estate first--</option>');
            tsUnit = initReturnHouseTs(document.getElementById('request_unit_name'), '--Select Estate first--');
            destroyTsAndHtml('request_building_name', '<option value="">--Select--</option>');
            tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), '--Select--');
            destroyTsAndHtml('request_unit_sub_type', '<option value="">--Select--</option>');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), '--Select--');
            setHouseSelectOnly('<option value="">--Select--</option>', '');
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
            destroyTsAndHtml('request_unit_name', '<option value="">--Select--</option>');
            destroyTsAndHtml('request_building_name', '<option value="">--Select--</option>');
            destroyTsAndHtml('request_unit_sub_type', '<option value="">--Select--</option>');
            setHouseSelectOnly('<option value="">--Select--</option>', '');
            var unitEl = document.getElementById('request_unit_name');
            tsUnit = initReturnHouseTs(unitEl, '--Select--');
            tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), '--Select--');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), '--Select--');
            $('#request_house_no_display').val('');
            if (!campusPk) return;
            var types = unitTypesByCampus[campusPk] || [];
            if (unitEl && unitEl.tomselect) {
                unitEl.tomselect.clearOptions();
                unitEl.tomselect.addOption({ value: '', text: '--Select--' });
                types.forEach(function(t) { unitEl.tomselect.addOption({ value: String(t.pk), text: t.unit_type || '' }); });
            } else {
                types.forEach(function(t) { $('#request_unit_name').append('<option value="' + t.pk + '">' + (t.unit_type || '') + '</option>'); });
            }
        });

        $(document).on('change', '#request_unit_name', function() {
            if (isFillingFromRequest) return;
            var campusId = getSelectVal(document.getElementById('request_estate_name'));
            var unitTypeId = getSelectVal(this);
            destroyTsAndHtml('request_building_name', '<option value="">--Select--</option>');
            destroyTsAndHtml('request_unit_sub_type', '<option value="">--Select--</option>');
            setHouseSelectOnly('<option value="">--Select--</option>', '');
            tsBuilding = initReturnHouseTs(document.getElementById('request_building_name'), '--Select--');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), '--Select--');
            if (!campusId) return;
            if (!unitTypeId) return;
            $.get(urlBlocks, { campus_id: campusId, unit_type_id: unitTypeId }, function(res) {
                if (res.status && res.data) {
                    var blkEl = document.getElementById('request_building_name');
                    if (blkEl && blkEl.tomselect) {
                        blkEl.tomselect.clearOptions();
                        blkEl.tomselect.addOption({ value: '', text: '--Select--' });
                        res.data.forEach(function(b) { blkEl.tomselect.addOption({ value: String(b.pk), text: b.block_name || '' }); });
                    } else {
                        res.data.forEach(function(b) { $('#request_building_name').append('<option value="' + b.pk + '">' + b.block_name + '</option>'); });
                    }
                }
            });
        });

        $(document).on('change', '#request_building_name', function() {
            if (isFillingFromRequest) return;
            var campusId = getSelectVal(document.getElementById('request_estate_name'));
            var blockId = getSelectVal(this);
            var unitTypeId = getSelectVal(document.getElementById('request_unit_name'));
            destroyTsAndHtml('request_unit_sub_type', '<option value="">--Select--</option>');
            setHouseSelectOnly('<option value="">--Select--</option>', '');
            tsUnitSub = initReturnHouseTs(document.getElementById('request_unit_sub_type'), '--Select--');
            if (!campusId || !blockId) return;
            $.get(urlUnitSubTypes, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId }, function(res) {
                if (res.status && res.data) {
                    var ustEl = document.getElementById('request_unit_sub_type');
                    if (ustEl && ustEl.tomselect) {
                        ustEl.tomselect.clearOptions();
                        ustEl.tomselect.addOption({ value: '', text: '--Select--' });
                        res.data.forEach(function(u) { ustEl.tomselect.addOption({ value: String(u.pk), text: u.unit_sub_type || '' }); });
                    } else {
                        res.data.forEach(function(u) { $('#request_unit_sub_type').append('<option value="' + u.pk + '">' + u.unit_sub_type + '</option>'); });
                    }
                }
            });
        });

        $(document).on('change', '#request_unit_sub_type', function() {
            if (isFillingFromRequest) return;
            var campusId = getSelectVal(document.getElementById('request_estate_name'));
            var blockId = getSelectVal(document.getElementById('request_building_name'));
            var unitSubTypeId = getSelectVal(this);
            var unitTypeId = getSelectVal(document.getElementById('request_unit_name'));
            setHouseSelectOnly('<option value="">--Select--</option>', '');
            if (!campusId || !blockId || !unitSubTypeId) return;
            $.get(urlHouses, { campus_id: campusId, block_id: blockId, unit_sub_type_id: unitSubTypeId, unit_type_id: unitTypeId }, function(res) {
                var houseHtml = '<option value="">--Select--</option>';
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
