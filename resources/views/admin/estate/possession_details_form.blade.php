@extends('admin.layouts.master')

@section('title', 'Add Possession Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Add Possession Details"></x-breadcrum>
    <x-estate-workflow-stepper current="possession-details" />

    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h5 fw-semibold mb-1">Add Possession Details</h2>
            <p class="text-muted small mb-4">Requester list contains only allotted users (from HAC Approved flow).</p>
            <hr class="my-4">

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('error') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.estate.possession-details.store') }}" id="possessionDetailsForm" class="needs-validation" novalidate>
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="estate_home_request_details_pk" class="form-label">Requester Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_home_request_details_pk" name="estate_home_request_details_pk" required>
                            <option value="">---select---</option>
                            @foreach($requesters as $r)
                                <option
                                    value="{{ $r->pk }}"
                                    data-request-id="{{ $r->req_id ?? '' }}"
                                    data-designation="{{ $r->emp_designation ?? '' }}"
                                    data-employee-pk="{{ $r->employee_pk ?? '' }}"
                                    data-employee-id="{{ $r->employee_id ?? '' }}"
                                    data-allotment-date="{{ $r->allotment_date ?? '' }}"
                                    data-possession-date="{{ $r->possession_date ?? '' }}"
                                    data-electric-meter-reading="{{ $r->electric_meter_reading ?? '' }}"
                                    data-campus-pk="{{ $r->estate_campus_master_pk ?? '' }}"
                                    data-unit-type-pk="{{ $r->estate_unit_type_master_pk ?? '' }}"
                                    data-block-pk="{{ $r->estate_block_master_pk ?? '' }}"
                                    data-unit-sub-type-pk="{{ $r->estate_unit_sub_type_master_pk ?? '' }}"
                                    data-house-pk="{{ $r->estate_house_master_pk ?? '' }}"
                                    {{ (string) old('estate_home_request_details_pk', $preselectedRequester) === (string) $r->pk ? 'selected' : '' }}
                                >
                                    {{ $r->emp_name }} ({{ $r->req_id }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">HAC-approved requester</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="request_id_display" class="form-label">Request ID</label>
                        <input type="text" class="form-control bg-body-secondary" id="request_id_display" readonly>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="designation_display" class="form-label">Designation</label>
                        <input type="text" class="form-control bg-body-secondary" id="designation_display" readonly>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                            <option value="">---select---</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}" {{ (string) old('estate_campus_master_pk') === (string) $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="estate_unit_type_master_pk" class="form-label">Unit Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_block_master_pk" class="form-label">Building Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_house_master_pk" class="form-label">House No. <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="allotment_date" class="form-label">Allotment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="allotment_date" name="allotment_date" value="{{ old('allotment_date') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="possession_date" class="form-label">Possession Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="possession_date" name="possession_date" value="{{ old('possession_date') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Electric Meter Reading <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                type="number"
                                class="form-control"
                                id="electric_meter_reading_primary"
                                name="electric_meter_reading_primary"
                                min="0"
                                value="{{ old('electric_meter_reading_primary', old('electric_meter_reading', 0)) }}"
                                placeholder="Primary"
                            >
                            <span class="input-group-text">/</span>
                            <input
                                type="number"
                                class="form-control"
                                id="electric_meter_reading_secondary"
                                name="electric_meter_reading_secondary"
                                min="0"
                                value="{{ old('electric_meter_reading_secondary') }}"
                                placeholder="Secondary"
                            >
                        </div>
                        <input type="hidden" id="electric_meter_reading" name="electric_meter_reading" value="{{ old('electric_meter_reading', 0) }}">
                        <div class="form-text">Electric Meter Reading (Primary / Secondary)</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2">
                    <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.possession-details') }}" class="btn btn-secondary d-inline-flex align-items-center gap-2">
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
    const housesUrl = "{{ route('admin.estate.change-request.vacant-houses') }}";
    const unitTypesByCampus = @json($unitTypesByCampus ?? []);

    const oldUnitType = @json(old('estate_unit_type_master_pk'));
    const oldBlock = @json(old('estate_block_master_pk'));
    const oldUnitSubType = @json(old('estate_unit_sub_type_master_pk'));
    const oldHouse = @json(old('estate_house_master_pk'));
    const oldCampus = @json(old('estate_campus_master_pk'));

    function selectedRequesterEmployeePk() {
        const opt = $('#estate_home_request_details_pk option:selected');
        return opt.attr('data-employee-pk') || '';
    }

    function syncElectricMeterReading() {
        const primary = $('#electric_meter_reading_primary').val();
        const secondary = $('#electric_meter_reading_secondary').val();
        const valueToStore = (primary !== '' && primary !== null) ? primary : ((secondary !== '' && secondary !== null) ? secondary : '');
        $('#electric_meter_reading').val(valueToStore);
    }

    function selectedRequesterPrefill() {
        const opt = $('#estate_home_request_details_pk option:selected');
        return {
            campusPk: opt.attr('data-campus-pk') || '',
            unitTypePk: opt.attr('data-unit-type-pk') || '',
            blockPk: opt.attr('data-block-pk') || '',
            unitSubTypePk: opt.attr('data-unit-sub-type-pk') || '',
            housePk: opt.attr('data-house-pk') || '',
            allotmentDate: opt.attr('data-allotment-date') || '',
            possessionDate: opt.attr('data-possession-date') || '',
            electricMeterReading: opt.attr('data-electric-meter-reading') || '',
        };
    }

    let preferred = {
        campusPk: oldCampus ? String(oldCampus) : '',
        unitTypePk: oldUnitType ? String(oldUnitType) : '',
        blockPk: oldBlock ? String(oldBlock) : '',
        unitSubTypePk: oldUnitSubType ? String(oldUnitSubType) : '',
        housePk: oldHouse ? String(oldHouse) : ''
    };

    $('#estate_home_request_details_pk').change(function() {
        const opt = $(this).find('option:selected');
        $('#request_id_display').val(opt.attr('data-request-id') || '');
        $('#designation_display').val(opt.attr('data-designation') || '');
        const prefill = selectedRequesterPrefill();
        if (prefill.allotmentDate) $('#allotment_date').val(prefill.allotmentDate);
        if (prefill.possessionDate) $('#possession_date').val(prefill.possessionDate);
        if (prefill.electricMeterReading !== '') {
            $('#electric_meter_reading_primary').val(prefill.electricMeterReading);
            $('#electric_meter_reading_secondary').val('');
        }
        syncElectricMeterReading();
        preferred = {
            campusPk: prefill.campusPk ? String(prefill.campusPk) : '',
            unitTypePk: prefill.unitTypePk ? String(prefill.unitTypePk) : '',
            blockPk: prefill.blockPk ? String(prefill.blockPk) : '',
            unitSubTypePk: prefill.unitSubTypePk ? String(prefill.unitSubTypePk) : '',
            housePk: prefill.housePk ? String(prefill.housePk) : ''
        };
        if (preferred.campusPk) {
            $('#estate_campus_master_pk').val(preferred.campusPk).trigger('change');
        } else {
            $('#estate_house_master_pk').html('<option value="">---select---</option>');
        }
    }).trigger('change');

    $('#electric_meter_reading_primary, #electric_meter_reading_secondary').on('input change', function() {
        syncElectricMeterReading();
    });
    syncElectricMeterReading();

    $('#estate_campus_master_pk').change(function() {
        const campusId = $(this).val();
        $('#estate_unit_type_master_pk').html('<option value="">---select---</option>');
        $('#estate_block_master_pk').html('<option value="">---select---</option>');
        $('#estate_unit_sub_type_master_pk').html('<option value="">---select---</option>');
        $('#estate_house_master_pk').html('<option value="">---select---</option>');
        if (!campusId) return;
        const list = unitTypesByCampus[campusId] || [];
        $.each(list, function(i, ut) {
            const sel = (preferred.unitTypePk && String(preferred.unitTypePk) === String(ut.pk)) ? 'selected' : '';
            $('#estate_unit_type_master_pk').append('<option value="' + ut.pk + '" ' + sel + '>' + ut.unit_type + '</option>');
        });
        if (list.length === 1 && !preferred.unitTypePk) {
            $('#estate_unit_type_master_pk').val(list[0].pk);
        }
        loadBlocks();
    });

    if (preferred.campusPk) {
        $('#estate_campus_master_pk').val(preferred.campusPk).trigger('change');
    } else {
        $('#estate_campus_master_pk').trigger('change');
    }

    $('#estate_unit_type_master_pk').change(function() {
        $('#estate_block_master_pk').html('<option value="">---select---</option>');
        $('#estate_unit_sub_type_master_pk').html('<option value="">---select---</option>');
        $('#estate_house_master_pk').html('<option value="">---select---</option>');
        loadBlocks();
    });

    function loadBlocks() {
        const campusId = $('#estate_campus_master_pk').val();
        const unitTypeId = $('#estate_unit_type_master_pk').val();
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId, unit_type_id: unitTypeId || '' }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, b) {
                    const sel = (preferred.blockPk && String(preferred.blockPk) === String(b.pk)) ? 'selected' : '';
                    $('#estate_block_master_pk').append('<option value="' + b.pk + '" ' + sel + '>' + b.block_name + '</option>');
                });
                loadUnitSubTypes();
            }
        });
    }

    $('#estate_block_master_pk').change(function() {
        $('#estate_unit_sub_type_master_pk').html('<option value="">---select---</option>');
        $('#estate_house_master_pk').html('<option value="">---select---</option>');
        loadUnitSubTypes();
    });

    function loadUnitSubTypes() {
        const campusId = $('#estate_campus_master_pk').val();
        const blockId = $('#estate_block_master_pk').val();
        const unitTypeId = $('#estate_unit_type_master_pk').val();
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_type_id: unitTypeId || ''
        }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    const sel = (preferred.unitSubTypePk && String(preferred.unitSubTypePk) === String(u.pk)) ? 'selected' : '';
                    $('#estate_unit_sub_type_master_pk').append('<option value="' + u.pk + '" ' + sel + '>' + u.unit_sub_type + '</option>');
                });
                loadHouses();
            }
        });
    }

    $('#estate_unit_sub_type_master_pk').change(function() {
        $('#estate_house_master_pk').html('<option value="">---select---</option>');
        loadHouses();
    });

    function loadHouses() {
        const campusId = $('#estate_campus_master_pk').val();
        const blockId = $('#estate_block_master_pk').val();
        const unitSubId = $('#estate_unit_sub_type_master_pk').val();
        const unitTypeId = $('#estate_unit_type_master_pk').val();
        const employeePk = selectedRequesterEmployeePk();
        const includeHousePk = selectedRequesterPrefill().housePk || '';
        if (!campusId || !blockId || !unitSubId) return;
        $.get(housesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_sub_type_id: unitSubId,
            unit_type_id: unitTypeId || '',
            employee_pk: employeePk || '',
            include_house_pk: includeHousePk || ''
        }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, h) {
                    const sel = (preferred.housePk && String(preferred.housePk) === String(h.pk)) ? 'selected' : '';
                    $('#estate_house_master_pk').append('<option value="' + h.pk + '" ' + sel + '>' + h.house_no + '</option>');
                });
            }
        });
    }
});
</script>
@endpush
