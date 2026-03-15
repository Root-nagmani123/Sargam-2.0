@extends('admin.layouts.master')

@section('title', ($isEdit ?? false) ? 'Edit Possession Details - Sargam' : 'Add Possession Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum :title="($isEdit ?? false) ? 'Edit Possession Details' : 'Add Possession Details'"></x-breadcrum>
    <x-estate-workflow-stepper current="possession-details" />
    <x-session_message />

    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h5 fw-semibold mb-1">{{ ($isEdit ?? false) ? 'Edit Possession Details' : 'Add Possession Details' }}</h2>
            <p class="text-muted small mb-4">Requester list contains only allotted users (from HAC Approved flow).</p>
            <hr class="my-4">

            <form method="POST" action="{{ route('admin.estate.possession-details.store') }}" id="possessionDetailsForm" class="needs-validation" novalidate data-ajax-submit="1">
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
                                    data-electric-meter-reading-secondary="{{ $r->electric_meter_reading_2 ?? '' }}"
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
                        <div class="text-danger small field-error" data-field="estate_home_request_details_pk" role="alert">@error('estate_home_request_details_pk'){{ $message }}@enderror</div>
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
                        <input type="hidden" id="estate_campus_master_pk_hidden" value="">
                        <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                            <option value="">---select---</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}" {{ (string) old('estate_campus_master_pk') === (string) $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <div class="text-danger small field-error" data-field="estate_campus_master_pk" role="alert">@error('estate_campus_master_pk'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="estate_unit_type_master_pk" class="form-label">Unit Type <span class="text-danger">*</span></label>
                        <input type="hidden" id="estate_unit_type_master_pk_hidden" value="">
                        <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="text-danger small field-error" data-field="estate_unit_type_master_pk" role="alert">@error('estate_unit_type_master_pk'){{ $message }}@enderror</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_block_master_pk" class="form-label">Building Name <span class="text-danger">*</span></label>
                        <input type="hidden" id="estate_block_master_pk_hidden" value="">
                        <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="text-danger small field-error" data-field="estate_block_master_pk" role="alert">@error('estate_block_master_pk'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <input type="hidden" id="estate_unit_sub_type_master_pk_hidden" value="">
                        <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="text-danger small field-error" data-field="estate_unit_sub_type_master_pk" role="alert">@error('estate_unit_sub_type_master_pk'){{ $message }}@enderror</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="estate_house_master_pk" class="form-label">House No. <span class="text-danger">*</span></label>
                        <input type="hidden" id="estate_house_master_pk_hidden" value="">
                        <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                            <option value="">---select---</option>
                        </select>
                        <div class="text-danger small field-error" data-field="estate_house_master_pk" role="alert">@error('estate_house_master_pk'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="allotment_date" class="form-label">Allotment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="allotment_date" name="allotment_date" value="{{ old('allotment_date') }}" required>
                        <div class="text-danger small field-error" data-field="allotment_date" role="alert">@error('allotment_date'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="possession_date" class="form-label">Possession Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="possession_date" name="possession_date" value="{{ old('possession_date') }}" required>
                        <div class="text-danger small field-error" data-field="possession_date" role="alert">@error('possession_date'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6">
                                <label for="meter_one_display" class="form-label">Electric Meter No. (I)</label>
                                <input
                                    type="text"
                                    class="form-control bg-body-secondary"
                                    id="meter_one_display"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="electric_meter_reading_primary" class="form-label">Electric Meter Reading (I) <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="electric_meter_reading_primary"
                                    name="electric_meter_reading_primary"
                                    inputmode="numeric"
                                    min="0"
                                    step="1"
                                    maxlength="10"
                                    value="{{ old('electric_meter_reading_primary', old('electric_meter_reading', '')) }}"
                                    oninput="this.value=this.value.replace(/\\D/g,'').slice(0,10);"
                                >
                                <div class="text-danger small field-error" data-field="electric_meter_reading_primary" role="alert">@error('electric_meter_reading_primary'){{ $message }}@enderror</div>
                            </div>
                        </div>
                        <div class="row g-3 align-items-end mt-1" id="secondary-meter-wrapper">
                            <div class="col-12 col-md-6">
                                <label for="meter_two_display" class="form-label">Electric Meter No. (II)</label>
                                <input
                                    type="text"
                                    class="form-control bg-body-secondary"
                                    id="meter_two_display"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="electric_meter_reading_secondary" class="form-label">Electric Meter Reading (II)</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="electric_meter_reading_secondary"
                                    name="electric_meter_reading_secondary"
                                    inputmode="numeric"
                                    min="0"
                                    step="1"
                                    maxlength="10"
                                    value="{{ old('electric_meter_reading_secondary') }}"
                                    oninput="this.value=this.value.replace(/\\D/g,'').slice(0,10);"
                                >
                                <div class="text-danger small field-error" data-field="electric_meter_reading_secondary" role="alert">@error('electric_meter_reading_secondary'){{ $message }}@enderror</div>
                            </div>
                        </div>
                        <input type="hidden" id="electric_meter_reading" name="electric_meter_reading" value="{{ old('electric_meter_reading', '') }}">
                    </div>
                </div>

                <div class="alert alert-warning py-2 mb-4" role="alert">
                    <small><span class="text-danger">*</span> Required fields are mandatory</small>
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

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>.ts-dropdown { z-index: 1060 !important; }</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
$(document).ready(function() {
    const blocksUrl = "{{ route('admin.estate.possession.blocks') }}";
    const unitSubTypesUrl = "{{ route('admin.estate.possession.unit-sub-types') }}";
    const housesUrl = "{{ route('admin.estate.change-request.vacant-houses') }}";
    const unitTypesByCampus = @json($unitTypesByCampus ?? []);

    if (typeof TomSelect !== 'undefined') {
        const requesterEl = document.getElementById('estate_home_request_details_pk');
        const campusEl = document.getElementById('estate_campus_master_pk');
        const commonConfig = {
            allowEmptyOption: true,
            create: false,
            dropdownParent: 'body',
            maxOptions: null,
            hideSelected: false,
            onInitialize: function () { this.activeOption = null; }
        };
        if (requesterEl && !requesterEl.tomselect) {
            new TomSelect(requesterEl, Object.assign({}, commonConfig, { placeholder: '---select---' }));
        }
        if (campusEl && !campusEl.tomselect) {
            new TomSelect(campusEl, Object.assign({}, commonConfig, { placeholder: '---select---' }));
        }
    }

    const oldUnitType = @json(old('estate_unit_type_master_pk'));
    const oldBlock = @json(old('estate_block_master_pk'));
    const oldUnitSubType = @json(old('estate_unit_sub_type_master_pk'));
    const oldHouse = @json(old('estate_house_master_pk'));
    const oldCampus = @json(old('estate_campus_master_pk'));

    function setCampusSelectValue(val) {
        const el = document.getElementById('estate_campus_master_pk');
        if (el && el.tomselect) {
            el.tomselect.setValue(val ? String(val) : '', true);
        } else {
            $('#estate_campus_master_pk').val(val || '').trigger('change');
        }
    }

    function selectedRequesterEmployeePk() {
        const opt = $('#estate_home_request_details_pk option:selected');
        return opt.attr('data-employee-pk') || '';
    }

    function syncElectricMeterReading() {
        const primary = $('#electric_meter_reading_primary').val();
        // estate_possession_details.electric_meter_reading should mirror the primary input (main meter).
        const valueToStore = (primary !== '' && primary !== null) ? primary : '';
        $('#electric_meter_reading').val(valueToStore);
    }

    function sanitizeMeterInputs() {
        $('#electric_meter_reading_primary, #electric_meter_reading_secondary').each(function() {
            this.value = String(this.value || '').replace(/\D/g, '').slice(0, 10);
        });
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
            electricMeterReadingSecondary: opt.attr('data-electric-meter-reading-secondary') || '',
        };
    }

    let preferred = {
        campusPk: oldCampus ? String(oldCampus) : '',
        unitTypePk: oldUnitType ? String(oldUnitType) : '',
        blockPk: oldBlock ? String(oldBlock) : '',
        unitSubTypePk: oldUnitSubType ? String(oldUnitSubType) : '',
        housePk: oldHouse ? String(oldHouse) : ''
    };

    function isRequesterSelected() {
        const v = $('#estate_home_request_details_pk').val();
        return v !== null && String(v).trim() !== '';
    }

    function syncLockedHiddenSelects() {
        $('#estate_campus_master_pk_hidden').val($('#estate_campus_master_pk').val() || '');
        $('#estate_unit_type_master_pk_hidden').val($('#estate_unit_type_master_pk').val() || '');
        $('#estate_block_master_pk_hidden').val($('#estate_block_master_pk').val() || '');
        $('#estate_unit_sub_type_master_pk_hidden').val($('#estate_unit_sub_type_master_pk').val() || '');
        $('#estate_house_master_pk_hidden').val($('#estate_house_master_pk').val() || '');
    }

    function setLockedFieldNames(locked) {
        // Disabled selects don't submit, so submit values via hidden inputs (only when locked).
        $('#estate_campus_master_pk_hidden').prop('name', locked ? 'estate_campus_master_pk' : null);
        $('#estate_unit_type_master_pk_hidden').prop('name', locked ? 'estate_unit_type_master_pk' : null);
        $('#estate_block_master_pk_hidden').prop('name', locked ? 'estate_block_master_pk' : null);
        $('#estate_unit_sub_type_master_pk_hidden').prop('name', locked ? 'estate_unit_sub_type_master_pk' : null);
        $('#estate_house_master_pk_hidden').prop('name', locked ? 'estate_house_master_pk' : null);
    }

    function lockPrefilledFields(locked) {
        // Lock estate/house fields that come from requester; requester dropdown and meter reading stay editable.
        $('#estate_campus_master_pk, #estate_unit_type_master_pk, #estate_block_master_pk, #estate_unit_sub_type_master_pk, #estate_house_master_pk')
            .prop('disabled', locked)
            .toggleClass('bg-body-secondary', locked);

        $('#allotment_date, #possession_date')
            .prop('readonly', locked)
            .toggleClass('bg-body-secondary', locked);

        setLockedFieldNames(locked);
        syncLockedHiddenSelects();
    }

    $('#estate_home_request_details_pk').change(function() {
        const opt = $(this).find('option:selected');
        $('#request_id_display').val(opt.attr('data-request-id') || '');
        $('#designation_display').val(opt.attr('data-designation') || '');
        const prefill = selectedRequesterPrefill();
        if (prefill.allotmentDate) $('#allotment_date').val(prefill.allotmentDate);
        if (prefill.possessionDate) $('#possession_date').val(prefill.possessionDate);
        if (prefill.electricMeterReading !== '' || prefill.electricMeterReadingSecondary !== '') {
            $('#electric_meter_reading_primary').val(prefill.electricMeterReading || '');
            $('#electric_meter_reading_secondary').val(prefill.electricMeterReadingSecondary || '');
        } else {
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
            setCampusSelectValue(preferred.campusPk);
            $('#estate_campus_master_pk').trigger('change');
        } else {
            setCampusSelectValue('');
            $('#estate_house_master_pk').html('<option value="">---select---</option>');
        }

        lockPrefilledFields(isRequesterSelected());
    }).trigger('change');

    $('#electric_meter_reading_primary, #electric_meter_reading_secondary').on('input change', function() {
        sanitizeMeterInputs();
        syncElectricMeterReading();
    });
    $('#electric_meter_reading_primary, #electric_meter_reading_secondary').on('keydown', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) {
            e.preventDefault();
        }
    });
    sanitizeMeterInputs();
    syncElectricMeterReading();

    // Initially hide secondary meter row until a house with meter_two is selected.
    $('#secondary-meter-wrapper').hide();

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
        syncLockedHiddenSelects();
    });

    if (preferred.campusPk) {
        setCampusSelectValue(preferred.campusPk);
        $('#estate_campus_master_pk').trigger('change');
    } else {
        $('#estate_campus_master_pk').trigger('change');
    }

    $('#estate_unit_type_master_pk').change(function() {
        $('#estate_block_master_pk').html('<option value="">---select---</option>');
        $('#estate_unit_sub_type_master_pk').html('<option value="">---select---</option>');
        $('#estate_house_master_pk').html('<option value="">---select---</option>');
        loadBlocks();
        syncLockedHiddenSelects();
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
        syncLockedHiddenSelects();
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
        syncLockedHiddenSelects();
    });

    $('#estate_house_master_pk').change(function() {
        syncLockedHiddenSelects();
        var opt = $(this).find('option:selected');
        var meterOne = opt.attr('data-meter-one') || '';
        var meterTwo = opt.attr('data-meter-two') || '';
        var hadPrimary = $('#electric_meter_reading_primary').val();
        var hadSecondary = $('#electric_meter_reading_secondary').val();

        // Always show meter numbers (readonly) next to input boxes.
        $('#meter_one_display').val(meterOne);
        $('#meter_two_display').val(meterTwo);

        // Show/hide secondary meter section based on presence of meterTwo (non-empty and non-zero).
        var hasValidMeterTwo = meterTwo && String(meterTwo).trim() !== '' && parseInt(meterTwo, 10) !== 0;
        if (hasValidMeterTwo) {
            $('#secondary-meter-wrapper').show();
        } else {
            $('#secondary-meter-wrapper').hide();
            $('#meter_two_display').val('');
            $('#electric_meter_reading_secondary').val('');
        }

        // Do not overwrite any existing readings typed/prefilled for requester.
        // If no readings are present yet, keep inputs empty so user can enter opening readings.
        if (!hadPrimary && !hadSecondary) {
            $('#electric_meter_reading_primary').val('');
            $('#electric_meter_reading_secondary').val('');
        }

        sanitizeMeterInputs();
        syncElectricMeterReading();
    });

    // AJAX submit: show validation errors below fields only, no page refresh
    $('#possessionDetailsForm').on('submit', function(e) {
        if ($(this).data('ajax-submit') !== 1) return;
        e.preventDefault();
        var form = this;
        var $form = $(form);
        var $btn = $form.find('button[type="submit"]');
        $form.find('.field-error').empty();
        $form.find('.is-invalid').removeClass('is-invalid');
        $btn.prop('disabled', true);
        var formData = new FormData(form);
        fetch($form.attr('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            redirect: 'manual',
            credentials: 'same-origin'
        }).then(function(res) {
            if (res.status === 422) {
                return res.json().then(function(data) {
                    var errors = data.errors || {};
                    Object.keys(errors).forEach(function(key) {
                        var msg = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                        var $err = $form.find('.field-error[data-field="' + key + '"]');
                        if ($err.length) $err.text(msg);
                        var $input = $form.find('[name="' + key + '"]');
                        if ($input.length) $input.addClass('is-invalid');
                    });
                    $('html, body').animate({ scrollTop: $form.find('.field-error:not(:empty)').first().offset().top - 80 }, 200);
                });
            } else if (res.ok) {
                return res.json().then(function(data) {
                    if (data.redirect) window.location.href = data.redirect;
                    else window.location.href = "{{ route('admin.estate.possession-details') }}";
                }).catch(function() {
                    window.location.href = "{{ route('admin.estate.possession-details') }}";
                });
            } else if (res.status === 302) {
                var loc = res.headers.get('Location');
                if (loc) window.location.href = loc;
            }
        }).catch(function() {
            $form.off('submit').trigger('submit');
        }).finally(function() {
            $btn.prop('disabled', false);
        });
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
                    const meterOne = (h.meter_one != null && h.meter_one !== '') ? String(h.meter_one).replace(/"/g, '&quot;') : '';
                    const meterTwo = (h.meter_two != null && h.meter_two !== '') ? String(h.meter_two).replace(/"/g, '&quot;') : '';
                    $('#estate_house_master_pk').append('<option value="' + h.pk + '" data-meter-one="' + meterOne + '" data-meter-two="' + meterTwo + '" ' + sel + '>' + (h.house_no || '') + '</option>');
                });
                $('#estate_house_master_pk').trigger('change');
            }
            syncLockedHiddenSelects();
        });
    }

    // Ensure proper initial lock state on load.
    lockPrefilledFields(isRequesterSelected());
});
</script>
@endpush
