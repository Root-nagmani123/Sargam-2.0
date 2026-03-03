@extends('admin.layouts.master')

@section('title', 'Estate Possession View - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
   <x-breadcrum title="Estate Possession View"></x-breadcrum>
    <div class="card p-4">
        <div class="card-body rounded-3" style="border: 1px solid #D1D5DC;">
            <p class="mb-4" style="color: #4D4D4D;font-weight: 500;font-size: 16px;">Please add Request Details</p>
            <form method="POST" action="{{ route('admin.estate.possession-view.store') }}" id="possessionForm">
                @csrf
                @if(isset($record) && $record)
                    <input type="hidden" name="id" value="{{ $record->pk }}">
                @endif

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="estate_other_req_pk" class="form-label">Requester Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_other_req_pk" name="estate_other_req_pk" required>
                            <option value="">Select</option>
                            @foreach($requesters as $r)
                                <option value="{{ $r->pk }}" data-request-no="{{ $r->request_no_oth }}" data-section="{{ $r->section ?? '' }}"
                                    {{ (isset($record) && $record->estate_other_req_pk == $r->pk) || old('estate_other_req_pk') == $r->pk || (isset($preselectedRequester) && $preselectedRequester == $r->pk) ? 'selected' : '' }}>
                                    {{ $r->emp_name }} ({{ $r->request_no_oth }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select Requester Name</small>
                    </div>
                    <div class="col-md-4">
                        <label for="request_id_display" class="form-label">Request ID</label>
                        <input type="text" class="form-control" id="request_id_display" value="{{ isset($record) ? ($record->estateOtherRequest->request_no_oth ?? '') : '' }}" readonly>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Auto-filled from requester</small>
                    </div>
                    <div class="col-md-4">
                        <label for="section_display" class="form-label">Section</label>
                        <input type="text" class="form-control" id="section_display" value="{{ isset($record) ? ($record->estateOtherRequest->section ?? '') : '' }}" readonly>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Auto-filled from requester</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                            <option value="">Select</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}" {{ (isset($record) && $record->estate_campus_master_pk == $c->pk) || old('estate_campus_master_pk') == $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select Estate Name</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="estate_unit_type_master_pk" class="form-label">Unit Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                            <option value="">Select</option>
                            @foreach($unitTypes as $ut)
                                <option value="{{ $ut->pk }}" {{ (isset($record) && $record->estate_unit_type_master_pk == $ut->pk) || old('estate_unit_type_master_pk') == $ut->pk ? 'selected' : '' }}>{{ $ut->unit_type }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select Unit Type</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="estate_block_master_pk" class="form-label">Building Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select Building Name</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select Unit Sub Type</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="estate_house_master_pk" class="form-label">House No. <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select House No.</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="allotment_date" class="form-label">Allotment Date</label>
                        <input type="date" class="form-control" id="allotment_date" name="allotment_date" value="{{ old('allotment_date', isset($record) && $record->allotment_date ? $record->allotment_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="possession_date_oth" class="form-label">Possession Date</label>
                        <input type="date" class="form-control" id="possession_date_oth" name="possession_date_oth" value="{{ old('possession_date_oth', isset($record) && $record->possession_date_oth ? $record->possession_date_oth->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="meter_reading_oth" class="form-label">Electric Meter Reading</label>
                        <input type="number" class="form-control" id="meter_reading_oth" name="meter_reading_oth" value="{{ old('meter_reading_oth', isset($record) ? $record->meter_reading_oth : '') }}" min="0">
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Electric Meter Reading</small>
                    </div>

                </div>
                <div class="alert alert-danger mb-4">
                    <small>*Required Fields: All marked fields are mandatory</small>
                </div>
                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-outline-primary">Cancel</a>
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

    const recordBlock = @json(isset($record) ? $record->estate_block_master_pk : null);
    const recordUnitSub = @json(isset($record) ? $record->estate_unit_sub_type_master_pk : null);
    const recordHouse = @json(isset($record) ? $record->estate_house_master_pk : null);

    // Requester change -> fill request_id, section
    $('#estate_other_req_pk').change(function() {
        var opt = $(this).find('option:selected');
        $('#request_id_display').val(opt.data('request-no') || '');
        $('#section_display').val(opt.data('section') || '');
    }).trigger('change');

    // Campus change -> load blocks
    $('#estate_campus_master_pk').change(function() {
        var campusId = $(this).val();
        $('#estate_block_master_pk').html('<option value="">Select</option>');
        $('#estate_unit_sub_type_master_pk').html('<option value="">Select</option>');
        $('#estate_house_master_pk').html('<option value="">Select</option>');
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, b) {
                    var sel = (recordBlock && recordBlock == b.pk) ? 'selected' : '';
                    $('#estate_block_master_pk').append('<option value="'+b.pk+'" '+sel+'>'+b.block_name+'</option>');
                });
                loadUnitSubTypes();
            }
        });
    });

    // Block change -> load unit sub types
    $('#estate_block_master_pk').change(function() {
        $('#estate_unit_sub_type_master_pk').html('<option value="">Select</option>');
        $('#estate_house_master_pk').html('<option value="">Select</option>');
        loadUnitSubTypes();
    });

    function loadUnitSubTypes() {
        var campusId = $('#estate_campus_master_pk').val();
        var blockId = $('#estate_block_master_pk').val();
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId }, function(res) {
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
        if (!campusId || !blockId || !unitSubId) return;
        $.get(housesUrl, { campus_id: campusId, block_id: blockId, unit_sub_type_id: unitSubId }, function(res) {
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
