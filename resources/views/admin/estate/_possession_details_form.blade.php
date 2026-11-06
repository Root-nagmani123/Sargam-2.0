{{-- Partial: Add / Edit Possession Details.

     Rendered both as a full page (possession_details_form.blade.php) and as the
     body of the Add Possession modal on the Possession Details listing, so the
     two entry points share one set of fields, one cascade and one submit path.

     Every id / name / data-* attribute is deliberately unchanged from the
     original page — the cascade and the store endpoint depend on them. --}}
@php
    $inModal = $inModal ?? false;
    $isEdit = $isEdit ?? false;
    $estateSelfQuery = $estateSelfQuery ?? [];
    // Only estate authority may hand-edit allotment / possession dates.
    $canEditDates = isEstateAuthority();
    $showMeterFields = isEstateAuthority();
    $submitLabel = $isEdit ? 'Update Possession Request' : 'Add Possession Request';
@endphp

<form method="POST" action="{{ route('admin.estate.possession-details.store') }}" id="possessionDetailsForm"
    class="pd-form" novalidate data-ajax-submit="1" data-in-modal="{{ $inModal ? '1' : '0' }}">
    @csrf

    <div class="{{ $inModal ? 'modal-body' : '' }}">
        <div class="alert alert-danger d-none js-pd-form-error" role="alert"></div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="estate_home_request_details_pk" class="form-label">Requester Name <span class="ds-req">*</span></label>
                <select class="form-select" id="estate_home_request_details_pk" name="estate_home_request_details_pk" required>
                    <option value="">Select Requester</option>
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
                <div class="text-danger small field-error" data-field="estate_home_request_details_pk" role="alert">@error('estate_home_request_details_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="request_id_display" class="form-label">Request ID <span class="ds-req">*</span></label>
                <input type="text" class="form-control" id="request_id_display" placeholder="Request ID" readonly>
            </div>

            <div class="col-md-6">
                <label for="designation_display" class="form-label">Designation <span class="ds-req">*</span></label>
                <input type="text" class="form-control" id="designation_display" placeholder="Designation" readonly>
            </div>

            <div class="col-md-6">
                <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="ds-req">*</span></label>
                <input type="hidden" id="estate_campus_master_pk_hidden" value="">
                <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                    <option value="">Select Estate</option>
                    @foreach($campuses as $c)
                        <option value="{{ $c->pk }}" {{ (string) old('estate_campus_master_pk') === (string) $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                    @endforeach
                </select>
                <div class="text-danger small field-error" data-field="estate_campus_master_pk" role="alert">@error('estate_campus_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_unit_type_master_pk" class="form-label">Unit Type <span class="ds-req">*</span></label>
                <input type="hidden" id="estate_unit_type_master_pk_hidden" value="">
                <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                    <option value="">Select Unit</option>
                </select>
                <div class="text-danger small field-error" data-field="estate_unit_type_master_pk" role="alert">@error('estate_unit_type_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_block_master_pk" class="form-label">Building Name <span class="ds-req">*</span></label>
                <input type="hidden" id="estate_block_master_pk_hidden" value="">
                <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                    <option value="">Select Building</option>
                </select>
                <div class="text-danger small field-error" data-field="estate_block_master_pk" role="alert">@error('estate_block_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub-type <span class="ds-req">*</span></label>
                <input type="hidden" id="estate_unit_sub_type_master_pk_hidden" value="">
                <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                    <option value="">Select Sub-type</option>
                </select>
                <div class="text-danger small field-error" data-field="estate_unit_sub_type_master_pk" role="alert">@error('estate_unit_sub_type_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_house_master_pk" class="form-label">House Number <span class="ds-req">*</span></label>
                <input type="hidden" id="estate_house_master_pk_hidden" value="">
                <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                    <option value="">Select House</option>
                </select>
                <div class="text-danger small field-error" data-field="estate_house_master_pk" role="alert">@error('estate_house_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="allotment_date" class="form-label">Allotment Date <span class="ds-req">*</span></label>
                <input type="date" class="form-control" id="allotment_date" name="allotment_date" value="{{ old('allotment_date') }}" required>
                <div class="text-danger small field-error" data-field="allotment_date" role="alert">@error('allotment_date'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="possession_date" class="form-label">Possession Date <span class="ds-req">*</span></label>
                <input type="date" class="form-control" id="possession_date" name="possession_date" value="{{ old('possession_date') }}" required>
                <div class="text-danger small field-error" data-field="possession_date" role="alert">@error('possession_date'){{ $message }}@enderror</div>
            </div>

            @if($showMeterFields)
                <div class="col-md-6">
                    <label for="meter_one_display" class="form-label">Electric Meter Number I</label>
                    <input type="text" class="form-control" id="meter_one_display" placeholder="Electric Meter Number" readonly>
                </div>
                <div class="col-md-6">
                    <label for="electric_meter_reading_primary" class="form-label">Electric Meter Reading I <span class="ds-req">*</span></label>
                    <input type="text" class="form-control" id="electric_meter_reading_primary" name="electric_meter_reading_primary"
                        inputmode="numeric" maxlength="10" placeholder="Electric Meter Reading"
                        value="{{ old('electric_meter_reading_primary', old('electric_meter_reading', '')) }}">
                    <div class="text-danger small field-error" data-field="electric_meter_reading_primary" role="alert">@error('electric_meter_reading_primary'){{ $message }}@enderror</div>
                </div>

                {{-- Second meter only exists on some houses; revealed by the house
                     selection, not by the user. --}}
                <div class="col-md-6 d-none" id="secondary-meter-number-wrapper">
                    <label for="meter_two_display" class="form-label">Electric Meter Number II</label>
                    <input type="text" class="form-control" id="meter_two_display" placeholder="Electric Meter Number" readonly>
                </div>
                <div class="col-md-6 d-none" id="secondary-meter-wrapper">
                    <label for="electric_meter_reading_secondary" class="form-label">Electric Meter Reading II</label>
                    <input type="text" class="form-control" id="electric_meter_reading_secondary" name="electric_meter_reading_secondary"
                        inputmode="numeric" maxlength="10" placeholder="Electric Meter Reading"
                        value="{{ old('electric_meter_reading_secondary') }}">
                    <div class="text-danger small field-error" data-field="electric_meter_reading_secondary" role="alert">@error('electric_meter_reading_secondary'){{ $message }}@enderror</div>
                </div>

                <input type="hidden" id="electric_meter_reading" name="electric_meter_reading" value="{{ old('electric_meter_reading', '') }}">
            @endif
        </div>
    </div>

    <div class="{{ $inModal ? 'modal-footer' : 'd-flex justify-content-end gap-2 mt-4' }}">
        @if($inModal)
            <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        @else
            <a href="{{ route('admin.estate.possession-details', $estateSelfQuery) }}" class="btn ds-btn-cancel">Cancel</a>
        @endif
        <button type="submit" class="btn ds-btn-submit js-pd-submit">{{ $submitLabel }}</button>
    </div>
</form>

<script>
(function() {
    // As a full page this markup renders in the body, BEFORE the layout's footer
    // loads jQuery — so defer until the document is parsed. Injected into the
    // modal the document is already complete, so run straight away.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

function init() {
    var form = document.getElementById('possessionDetailsForm');
    if (!form || form.dataset.pdBound === '1') return;
    form.dataset.pdBound = '1';

    var $form = $(form);
    var inModal = form.dataset.inModal === '1';
    var canEditDates = @json($canEditDates);
    var blocksUrl = @json(route('admin.estate.possession.blocks'));
    var unitSubTypesUrl = @json(route('admin.estate.possession.unit-sub-types'));
    var housesUrl = @json(route('admin.estate.change-request.vacant-houses'));
    var unitTypesByCampus = @json($unitTypesByCampus ?? []);
    var listUrl = @json(route('admin.estate.possession-details', $estateSelfQuery));

    var oldUnitType = @json(old('estate_unit_type_master_pk'));
    var oldBlock = @json(old('estate_block_master_pk'));
    var oldUnitSubType = @json(old('estate_unit_sub_type_master_pk'));
    var oldHouse = @json(old('estate_house_master_pk'));
    var oldCampus = @json(old('estate_campus_master_pk'));

    var preferred = {
        campusPk: oldCampus ? String(oldCampus) : '',
        unitTypePk: oldUnitType ? String(oldUnitType) : '',
        blockPk: oldBlock ? String(oldBlock) : '',
        unitSubTypePk: oldUnitSubType ? String(oldUnitSubType) : '',
        housePk: oldHouse ? String(oldHouse) : ''
    };

    function $f(id) { return $form.find('#' + id); }

    function setCampusSelectValue(val) {
        $f('estate_campus_master_pk').val(val ? String(val) : '');
    }

    function selectedRequesterEmployeePk() {
        return $f('estate_home_request_details_pk').find('option:selected').attr('data-employee-pk') || '';
    }

    function syncElectricMeterReading() {
        var primary = $f('electric_meter_reading_primary').val();
        // estate_possession_details.electric_meter_reading mirrors the primary (main) meter.
        $f('electric_meter_reading').val((primary !== '' && primary !== null) ? primary : '');
    }

    function sanitizeMeterInputs() {
        $form.find('#electric_meter_reading_primary, #electric_meter_reading_secondary').each(function() {
            this.value = String(this.value || '').replace(/\D/g, '').slice(0, 10);
        });
    }

    function selectedRequesterPrefill() {
        var opt = $f('estate_home_request_details_pk').find('option:selected');
        return {
            requestId: opt.attr('data-request-id') || '',
            designation: opt.attr('data-designation') || '',
            campusPk: opt.attr('data-campus-pk') || '',
            unitTypePk: opt.attr('data-unit-type-pk') || '',
            blockPk: opt.attr('data-block-pk') || '',
            unitSubTypePk: opt.attr('data-unit-sub-type-pk') || '',
            housePk: opt.attr('data-house-pk') || '',
            allotmentDate: opt.attr('data-allotment-date') || '',
            possessionDate: opt.attr('data-possession-date') || '',
            electricMeterReading: (opt.attr('data-electric-meter-reading') || '').toString().trim(),
            electricMeterReadingSecondary: (opt.attr('data-electric-meter-reading-secondary') || '').toString().trim()
        };
    }

    function isRequesterSelected() {
        var v = $f('estate_home_request_details_pk').val();
        return v !== null && String(v).trim() !== '';
    }

    function syncLockedHiddenSelects() {
        ['estate_campus_master_pk', 'estate_unit_type_master_pk', 'estate_block_master_pk',
         'estate_unit_sub_type_master_pk', 'estate_house_master_pk'].forEach(function(id) {
            $f(id + '_hidden').val($f(id).val() || '');
        });
    }

    function setLockedFieldNames(locked) {
        // Disabled selects don't submit, so locked values go via hidden inputs.
        ['estate_campus_master_pk', 'estate_unit_type_master_pk', 'estate_block_master_pk',
         'estate_unit_sub_type_master_pk', 'estate_house_master_pk'].forEach(function(id) {
            $f(id + '_hidden').prop('name', locked ? id : null);
        });
    }

    function lockPrefilledFields(locked) {
        // The estate/house chain comes from the requester's allotment; the requester
        // dropdown, dates and meter readings stay editable.
        $form.find('#estate_campus_master_pk, #estate_unit_type_master_pk, #estate_block_master_pk, #estate_unit_sub_type_master_pk, #estate_house_master_pk')
            .prop('disabled', locked);

        // Dates: estate authority always; self-service only while still blank.
        var hasExistingDates = String($f('allotment_date').val() || '').trim() !== ''
            || String($f('possession_date').val() || '').trim() !== '';
        var shouldReadonlyDates = locked && !canEditDates && hasExistingDates;
        $form.find('#allotment_date, #possession_date').prop('readonly', shouldReadonlyDates);

        setLockedFieldNames(locked);
        syncLockedHiddenSelects();
    }

    function loadBlocks() {
        var campusId = $f('estate_campus_master_pk').val();
        var unitTypeId = $f('estate_unit_type_master_pk').val();
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId, unit_type_id: unitTypeId || '' }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, b) {
                    var sel = (preferred.blockPk && String(preferred.blockPk) === String(b.pk)) ? 'selected' : '';
                    $f('estate_block_master_pk').append('<option value="' + b.pk + '" ' + sel + '>' + b.block_name + '</option>');
                });
                loadUnitSubTypes();
            }
        });
    }

    function loadUnitSubTypes() {
        var campusId = $f('estate_campus_master_pk').val();
        var blockId = $f('estate_block_master_pk').val();
        var unitTypeId = $f('estate_unit_type_master_pk').val();
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId || '' }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    var sel = (preferred.unitSubTypePk && String(preferred.unitSubTypePk) === String(u.pk)) ? 'selected' : '';
                    $f('estate_unit_sub_type_master_pk').append('<option value="' + u.pk + '" ' + sel + '>' + u.unit_sub_type + '</option>');
                });
                loadHouses();
            }
        });
    }

    function loadHouses() {
        var campusId = $f('estate_campus_master_pk').val();
        var blockId = $f('estate_block_master_pk').val();
        var unitSubId = $f('estate_unit_sub_type_master_pk').val();
        var unitTypeId = $f('estate_unit_type_master_pk').val();
        if (!campusId || !blockId || !unitSubId) return;
        $.get(housesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_sub_type_id: unitSubId,
            unit_type_id: unitTypeId || '',
            employee_pk: selectedRequesterEmployeePk() || '',
            include_house_pk: selectedRequesterPrefill().housePk || ''
        }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, h) {
                    var sel = (preferred.housePk && String(preferred.housePk) === String(h.pk)) ? 'selected' : '';
                    var meterOne = (h.meter_one != null && h.meter_one !== '') ? String(h.meter_one).replace(/"/g, '&quot;') : '';
                    var meterTwo = (h.meter_two != null && h.meter_two !== '') ? String(h.meter_two).replace(/"/g, '&quot;') : '';
                    $f('estate_house_master_pk').append('<option value="' + h.pk + '" data-meter-one="' + meterOne + '" data-meter-two="' + meterTwo + '" ' + sel + '>' + (h.house_no || '') + '</option>');
                });
                $f('estate_house_master_pk').trigger('change');
            }
            syncLockedHiddenSelects();
        });
    }

    $f('estate_home_request_details_pk').on('change', function() {
        var prefill = selectedRequesterPrefill();
        $f('request_id_display').val(prefill.requestId);
        $f('designation_display').val(prefill.designation);
        if (prefill.allotmentDate) $f('allotment_date').val(prefill.allotmentDate);
        if (prefill.possessionDate) $f('possession_date').val(prefill.possessionDate);
        $f('electric_meter_reading_primary').val(prefill.electricMeterReading);
        $f('electric_meter_reading_secondary').val(prefill.electricMeterReadingSecondary);
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
            $f('estate_campus_master_pk').trigger('change');
        } else {
            setCampusSelectValue('');
            $f('estate_house_master_pk').html('<option value="">Select House</option>');
        }

        lockPrefilledFields(isRequesterSelected());
    });

    $f('estate_campus_master_pk').on('change', function() {
        var campusId = $(this).val();
        $f('estate_unit_type_master_pk').html('<option value="">Select Unit</option>');
        $f('estate_block_master_pk').html('<option value="">Select Building</option>');
        $f('estate_unit_sub_type_master_pk').html('<option value="">Select Sub-type</option>');
        $f('estate_house_master_pk').html('<option value="">Select House</option>');
        if (!campusId) return;
        var list = unitTypesByCampus[campusId] || [];
        $.each(list, function(i, ut) {
            var sel = (preferred.unitTypePk && String(preferred.unitTypePk) === String(ut.pk)) ? 'selected' : '';
            $f('estate_unit_type_master_pk').append('<option value="' + ut.pk + '" ' + sel + '>' + ut.unit_type + '</option>');
        });
        if (list.length === 1 && !preferred.unitTypePk) {
            $f('estate_unit_type_master_pk').val(list[0].pk);
        }
        loadBlocks();
        syncLockedHiddenSelects();
    });

    $f('estate_unit_type_master_pk').on('change', function() {
        $f('estate_block_master_pk').html('<option value="">Select Building</option>');
        $f('estate_unit_sub_type_master_pk').html('<option value="">Select Sub-type</option>');
        $f('estate_house_master_pk').html('<option value="">Select House</option>');
        loadBlocks();
        syncLockedHiddenSelects();
    });

    $f('estate_block_master_pk').on('change', function() {
        $f('estate_unit_sub_type_master_pk').html('<option value="">Select Sub-type</option>');
        $f('estate_house_master_pk').html('<option value="">Select House</option>');
        loadUnitSubTypes();
        syncLockedHiddenSelects();
    });

    $f('estate_unit_sub_type_master_pk').on('change', function() {
        $f('estate_house_master_pk').html('<option value="">Select House</option>');
        loadHouses();
        syncLockedHiddenSelects();
    });

    $f('estate_house_master_pk').on('change', function() {
        syncLockedHiddenSelects();
        var opt = $(this).find('option:selected');
        var meterOne = opt.attr('data-meter-one') || '';
        var meterTwo = opt.attr('data-meter-two') || '';
        var hadPrimary = $f('electric_meter_reading_primary').val();
        var hadSecondary = $f('electric_meter_reading_secondary').val();

        $f('meter_one_display').val(meterOne);
        $f('meter_two_display').val(meterTwo);

        // Reveal the second meter only when this house actually has one.
        var hasValidMeterTwo = meterTwo && String(meterTwo).trim() !== '' && parseInt(meterTwo, 10) !== 0;
        $form.find('#secondary-meter-wrapper, #secondary-meter-number-wrapper').toggleClass('d-none', !hasValidMeterTwo);
        if (!hasValidMeterTwo) {
            $f('meter_two_display').val('');
            $f('electric_meter_reading_secondary').val('');
        }

        // Never overwrite readings already typed or prefilled for this requester.
        if (!hadPrimary && !hadSecondary) {
            $f('electric_meter_reading_primary').val('');
            $f('electric_meter_reading_secondary').val('');
        }

        sanitizeMeterInputs();
        syncElectricMeterReading();
    });

    $form.find('#electric_meter_reading_primary, #electric_meter_reading_secondary')
        .on('input change', function() { sanitizeMeterInputs(); syncElectricMeterReading(); })
        .on('keydown', function(e) { if (['e', 'E', '+', '-'].includes(e.key)) e.preventDefault(); });

    /* ---------- Submit ---------- */
    $form.on('submit', function(e) {
        e.preventDefault();
        var $btn = $form.find('.js-pd-submit');
        var $error = $form.find('.js-pd-form-error');
        var label = $btn.text();

        $form.find('.field-error').empty();
        $form.find('.is-invalid').removeClass('is-invalid');
        $error.addClass('d-none').text('');
        $btn.prop('disabled', true).text('Saving…');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(res) {
                if (inModal) {
                    // The listing decides what to refresh.
                    document.dispatchEvent(new CustomEvent('pd:possession-saved', {
                        detail: { message: (res && res.message) || 'Possession details saved.' }
                    }));
                    return;
                }
                window.location.href = (res && res.redirect) ? res.redirect : listUrl;
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, msgs) {
                        var msg = Array.isArray(msgs) ? msgs[0] : msgs;
                        var $err = $form.find('.field-error[data-field="' + key + '"]');
                        if ($err.length) $err.text(msg);
                        $form.find('[name="' + key + '"]').addClass('is-invalid');
                    });
                    var $first = $form.find('.field-error:not(:empty)').first();
                    if ($first.length) $first[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    return;
                }
                $error.removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong. Please try again.');
            },
            complete: function() { $btn.prop('disabled', false).text(label); }
        });
    });

    /* ---------- Initial state ---------- */
    sanitizeMeterInputs();
    syncElectricMeterReading();
    $f('estate_home_request_details_pk').trigger('change');
    if (preferred.campusPk) {
        setCampusSelectValue(preferred.campusPk);
    }
    $f('estate_campus_master_pk').trigger('change');
    lockPrefilledFields(isRequesterSelected());
}
})();
</script>
