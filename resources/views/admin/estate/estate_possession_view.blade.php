@extends('admin.layouts.master')

@section('title', 'Possession View - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Possession View"></x-breadcrum>
    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h5 fw-semibold mb-1">Possession View</h2>
            <p class="text-muted small mb-4">Please add Request Details</p>
            <form method="POST" action="{{ route('admin.estate.possession-view.store') }}" id="possessionForm" class="needs-validation" novalidate>
                @csrf
                @if(isset($record) && $record)
                    <input type="hidden" name="id" value="{{ $record->pk }}">
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="estate_other_req_pk" class="form-label fw-medium">Requester Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_other_req_pk" name="estate_other_req_pk" required>
                            <option value="">---select---</option>
                            @foreach($requesters as $r)
                                <option value="{{ $r->pk }}" data-request-no="{{ $r->request_no_oth }}" data-section="{{ $r->section ?? '' }}" data-designation="{{ $r->designation ?? '' }}"
                                    {{ (isset($record) && $record->estate_other_req_pk == $r->pk) || old('estate_other_req_pk') == $r->pk || (isset($preselectedRequester) && $preselectedRequester == $r->pk) ? 'selected' : '' }}>
                                    {{ $r->emp_name }} ({{ $r->request_no_oth }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Requester Name</div>
                    </div>
                    <div class="col-md-4">
                        <label for="request_id_display" class="form-label fw-medium">Request ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-body-secondary" id="request_id_display" value="{{ isset($record) ? ($record->estateOtherRequest->request_no_oth ?? '') : '' }}" readonly>
                        <div class="form-text">Auto-filled from requester</div>
                    </div>
                    <div class="col-md-4">
                        <label for="designation_display" class="form-label fw-medium">Designation <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-body-secondary" id="designation_display" value="{{ isset($record) ? ($record->estateOtherRequest->designation ?? '') : '' }}" readonly>
                        <div class="form-text">Auto-filled from requester</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="estate_campus_master_pk" class="form-label fw-medium">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                            <option value="">---select---</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}" {{ (isset($record) && $record->estate_campus_master_pk == $c->pk) || old('estate_campus_master_pk') == $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Select Estate Name</div>
                    </div>
                    <div class="col-md-6">
                        <label for="estate_unit_type_master_pk" class="form-label fw-medium">Unit Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="form-text">Select Estate first, then Unit Type</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="estate_block_master_pk" class="form-label fw-medium">Building Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="form-text">Select Building Name</div>
                    </div>
                    <div class="col-md-6">
                        <label for="estate_unit_sub_type_master_pk" class="form-label fw-medium">Unit Sub type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="form-text">Select Unit Sub Type</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="estate_house_master_pk" class="form-label fw-medium">House No.</label>
                        <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <input type="hidden" id="house_no" name="house_no">
                        <div class="form-text">Select House No.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="allotment_date" class="form-label fw-medium">Allotment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="allotment_date" name="allotment_date" value="{{ old('allotment_date', isset($record) && $record->allotment_date ? $record->allotment_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="possession_date_oth" class="form-label fw-medium">Possession Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="possession_date_oth" name="possession_date_oth" value="{{ old('possession_date_oth', isset($record) && $record->possession_date_oth ? $record->possession_date_oth->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Electric Meter Reading <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="meter_reading_oth" name="meter_reading_oth" value="{{ old('meter_reading_oth', isset($record) ? $record->meter_reading_oth : '') }}" min="0" placeholder="Primary">
                            <span class="input-group-text">/</span>
                            <input type="number" class="form-control" id="meter_reading_oth1" name="meter_reading_oth1" value="{{ old('meter_reading_oth1', isset($record) ? $record->meter_reading_oth1 : '') }}" min="0" placeholder="Secondary">
                        </div>
                        <div class="form-text">Primary / Secondary reading</div>
                    </div>
                </div>

                <div class="alert alert-warning py-2 mb-4" role="alert">
                    <small><span class="text-danger">*</span> Required Fields: All marked fields are mandatory</small>
                </div>
                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-danger d-inline-flex align-items-center gap-2">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const blocksUrl = "{{ route('admin.estate.possession.blocks') }}";
    const unitSubTypesUrl = "{{ route('admin.estate.possession.unit-sub-types') }}";
    const housesUrl = "{{ route('admin.estate.possession.houses') }}";
    const unitTypesByCampus = @json($unitTypesByCampus ?? []);

    const recordBlock = @json(isset($record) ? $record->estate_block_master_pk : null);
    const recordUnitType = @json(isset($record) ? $record->estate_unit_type_master_pk : null);
    const recordUnitSub = @json(isset($record) ? $record->estate_unit_sub_type_master_pk : null);
    const recordHouse = @json(isset($record) ? $record->estate_house_master_pk : null);

    // Requester change -> fill request_id, designation
    $('#estate_other_req_pk').change(function() {
        var opt = $(this).find('option:selected');
        $('#request_id_display').val(opt.data('request-no') || '');
        $('#designation_display').val(opt.data('designation') || '');
    }).trigger('change');

    // Campus change -> fill unit types from pre-loaded data (campus + house_master + unit_type_master join), then blocks
    $('#estate_campus_master_pk').change(function() {
        var campusId = $(this).val();
        $('#estate_unit_type_master_pk').html('<option value="">Select</option>');
        $('#estate_block_master_pk').html('<option value="">Select</option>');
        $('#estate_unit_sub_type_master_pk').html('<option value="">Select</option>');
        $('#estate_house_master_pk').html('<option value="">Select</option>');
        if (!campusId) return;
        var list = unitTypesByCampus[campusId] || [];
        $.each(list, function(i, ut) {
            var sel = (recordUnitType && recordUnitType == ut.pk) ? 'selected' : '';
            $('#estate_unit_type_master_pk').append('<option value="'+ut.pk+'" '+sel+'>'+ut.unit_type+'</option>');
        });
        if (list.length && !recordUnitType && list.length === 1) {
            $('#estate_unit_type_master_pk').val(list[0].pk);
        }
        if (list.length) loadBlocks();
    });

    // Unit Type change -> reload blocks for current campus
    $('#estate_unit_type_master_pk').change(function() {
        $('#estate_block_master_pk').html('<option value="">Select</option>');
        $('#estate_unit_sub_type_master_pk').html('<option value="">Select</option>');
        $('#estate_house_master_pk').html('<option value="">Select</option>');
        loadBlocks();
    });

    function loadBlocks() {
        var campusId = $('#estate_campus_master_pk').val();
        var unitTypeId = $('#estate_unit_type_master_pk').val();
        if (!campusId) return;
        $.get(blocksUrl, {
            campus_id: campusId,
            unit_type_id: unitTypeId || ''
        }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, b) {
                    var sel = (recordBlock && recordBlock == b.pk) ? 'selected' : '';
                    $('#estate_block_master_pk').append('<option value="'+b.pk+'" '+sel+'>'+b.block_name+'</option>');
                });
                loadUnitSubTypes();
            }
        });
    }

    // Block change -> load unit sub types
    $('#estate_block_master_pk').change(function() {
        $('#estate_unit_sub_type_master_pk').html('<option value="">Select</option>');
        $('#estate_house_master_pk').html('<option value="">Select</option>');
        loadUnitSubTypes();
    });

    function loadUnitSubTypes() {
        var campusId = $('#estate_campus_master_pk').val();
        var blockId = $('#estate_block_master_pk').val();
        var unitTypeId = $('#estate_unit_type_master_pk').val();
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_type_id: unitTypeId
        }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    var sel = (recordUnitSub && recordUnitSub == u.pk) ? 'selected' : '';
                    $('#estate_unit_sub_type_master_pk').append('<option value="'+u.pk+'" '+sel+'>'+u.unit_sub_type+'</option>');
                });
                loadHouses();
            }
        });
    }

    // Unit sub type change -> load houses
    $('#estate_unit_sub_type_master_pk').change(function() {
        $('#estate_house_master_pk').html('<option value="">Select</option>');
        loadHouses();
    });

    function loadHouses() {
        var campusId = $('#estate_campus_master_pk').val();
        var blockId = $('#estate_block_master_pk').val();
        var unitSubId = $('#estate_unit_sub_type_master_pk').val();
        var unitTypeId = $('#estate_unit_type_master_pk').val();
        if (!campusId || !blockId || !unitSubId) return;
        $.get(housesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_sub_type_id: unitSubId,
            unit_type_id: unitTypeId
        }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, h) {
                    var sel = (recordHouse && recordHouse == h.pk) ? 'selected' : '';
                    $('#estate_house_master_pk').append('<option value="'+h.pk+'" data-house-no="'+h.house_no+'" '+sel+'>'+h.house_no+'</option>');
                });
                updateHouseNoDisplay();
            }
        });
    }

    $('#estate_house_master_pk').change(function() {
        updateHouseNoDisplay();
    });

    function updateHouseNoDisplay() {
        var opt = $('#estate_house_master_pk option:selected');
        $('#house_no').val(opt.data('house-no') || opt.text() || '');
    }

    // Load initial data if editing
    @if(isset($record) && $record)
        $('#estate_campus_master_pk').trigger('change');
    @endif
});
</script>
@endpush
