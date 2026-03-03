@extends('admin.layouts.master')

@section('title', 'Possession View - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Possession View"></x-breadcrum>
    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h5 fw-semibold mb-1">Possession View</h2>
            <p class="text-muted small mb-4">Please add Request Details</p>
            <hr class="my-4">

            <form method="POST" action="{{ route('admin.estate.possession-view.store') }}" id="possessionForm" class="needs-validation" novalidate>
                @csrf
                @if(isset($record) && $record)
                    <input type="hidden" name="id" value="{{ $record->pk }}">
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="estate_other_req_pk" class="form-label">Requester Name <span class="text-danger">*</span></label>
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
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="request_id_display" class="form-label">Request ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-body-secondary" id="request_id_display" value="{{ isset($record) ? ($record->estateOtherRequest->request_no_oth ?? '') : '' }}" readonly>
                        <div class="form-text">Request ID</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="section_display" class="form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-body-secondary" id="section_display" value="{{ isset($record) ? ($record->estateOtherRequest->section ?? $record->estateOtherRequest->designation ?? '') : '' }}" readonly>
                        <div class="form-text">Section</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                            <option value="">---select---</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}" {{ (isset($record) && $record->estate_campus_master_pk == $c->pk) || old('estate_campus_master_pk') == $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Select Estate Name</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="estate_unit_type_master_pk" class="form-label">Unit type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="form-text">Select Unit type</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_block_master_pk" class="form-label">Building Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="form-text">Select Building Name</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="form-text">Select Unit Sub Type</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_house_master_pk" class="form-label">House No.</label>
                        <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <input type="hidden" id="house_no" name="house_no">
                        <div class="form-text">House No.</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="allotment_date" class="form-label">Allotment Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="allotment_date" name="allotment_date" value="{{ old('allotment_date', isset($record) && $record->allotment_date ? $record->allotment_date->format('Y-m-d') : '') }}">
                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        </div>
                        <div class="form-text">Allotment Date</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="possession_date_oth" class="form-label">Possession Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="possession_date_oth" name="possession_date_oth" value="{{ old('possession_date_oth', isset($record) && $record->possession_date_oth ? $record->possession_date_oth->format('Y-m-d') : '') }}">
                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        </div>
                        <div class="form-text">Possession Date</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Electric Meter Reading <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="meter_reading_oth" name="meter_reading_oth" value="{{ old('meter_reading_oth', isset($record) ? $record->meter_reading_oth : '') }}" min="0" placeholder="Primary">
                        <div class="form-text">Electric Meter Reading (Primary)</div>
                    </div>
                </div>

                <div class="alert alert-warning py-2 mb-4" role="alert">
                    <small><span class="text-danger">*</span> Required fields are mandatory</small>
                </div>
                <div class="d-flex flex-wrap justify-content-end gap-2">
                    <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-secondary d-inline-flex align-items-center gap-2">
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

    // Requester change -> fill request_id and section
    $('#estate_other_req_pk').change(function() {
        var opt = $(this).find('option:selected');
        $('#request_id_display').val(opt.attr('data-request-no') || '');
        $('#section_display').val(opt.attr('data-section') || opt.attr('data-designation') || '');
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
