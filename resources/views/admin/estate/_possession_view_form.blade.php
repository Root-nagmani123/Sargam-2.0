{{-- Partial: Add / Edit Possession Request (Others).

     Rendered both as a full page (estate_possession_view.blade.php) and as the
     body of the Add Possession modal on the Estate Possession for Others
     listing, so the two entry points share one set of fields, one cascade and
     one submit path.

     Every id / name / data-* attribute is deliberately unchanged from the
     original page — the cascade and the store endpoint depend on them. --}}
@php
    $inModal = $inModal ?? false;
    $isEdit = isset($record) && $record;
    $submitLabel = $isEdit ? 'Update Possession Request' : 'Add Possession Request';
@endphp

<form method="POST" action="{{ route('admin.estate.possession-view.store') }}" id="possessionForm"
    class="epo-form" novalidate data-in-modal="{{ $inModal ? '1' : '0' }}">
    @csrf
    @if($isEdit)
        <input type="hidden" name="id" value="{{ $record->pk }}">
    @endif

    <div class="{{ $inModal ? 'modal-body' : '' }}">
        <div class="alert alert-danger d-none js-epo-form-error" role="alert"></div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="estate_other_req_pk" class="form-label">Requester Name <span class="ds-req">*</span></label>
                <select class="form-select" id="estate_other_req_pk" name="estate_other_req_pk" required>
                    <option value="">Select Requester</option>
                    @foreach($requesters as $r)
                        <option value="{{ $r->pk }}"
                            data-request-no="{{ $r->request_no_oth }}"
                            data-section="{{ $r->section ?? '' }}"
                            data-designation="{{ $r->designation ?? '' }}"
                            {{ ($isEdit && $record->estate_other_req_pk == $r->pk) || old('estate_other_req_pk') == $r->pk || (isset($preselectedRequester) && $preselectedRequester == $r->pk) ? 'selected' : '' }}>
                            {{ $r->emp_name }} ({{ $r->request_no_oth }})
                        </option>
                    @endforeach
                </select>
                <div class="text-danger small field-error" data-field="estate_other_req_pk" role="alert">@error('estate_other_req_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="request_id_display" class="form-label">Request ID <span class="ds-req">*</span></label>
                <input type="text" class="form-control" id="request_id_display" placeholder="Request ID" readonly
                    value="{{ $isEdit ? ($record->estateOtherRequest->request_no_oth ?? '') : '' }}">
            </div>

            <div class="col-md-6">
                <label for="section_display" class="form-label">Section <span class="ds-req">*</span></label>
                <input type="text" class="form-control" id="section_display" placeholder="Section" readonly
                    value="{{ $isEdit ? ($record->estateOtherRequest->section ?? $record->estateOtherRequest->designation ?? '') : '' }}">
            </div>

            <div class="col-md-6">
                <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="ds-req">*</span></label>
                <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                    <option value="">Select Estate</option>
                    @foreach($campuses as $c)
                        <option value="{{ $c->pk }}" {{ ($isEdit && $record->estate_campus_master_pk == $c->pk) || old('estate_campus_master_pk') == $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                    @endforeach
                </select>
                <div class="text-danger small field-error" data-field="estate_campus_master_pk" role="alert">@error('estate_campus_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_unit_type_master_pk" class="form-label">Unit Name <span class="ds-req">*</span></label>
                <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                    <option value="">Select Unit</option>
                </select>
                <div class="text-danger small field-error" data-field="estate_unit_type_master_pk" role="alert">@error('estate_unit_type_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_block_master_pk" class="form-label">Building Name <span class="ds-req">*</span></label>
                <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                    <option value="">Select Building</option>
                </select>
                <div class="text-danger small field-error" data-field="estate_block_master_pk" role="alert">@error('estate_block_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub-type <span class="ds-req">*</span></label>
                <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                    <option value="">Select Sub-type</option>
                </select>
                <div class="text-danger small field-error" data-field="estate_unit_sub_type_master_pk" role="alert">@error('estate_unit_sub_type_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="estate_house_master_pk" class="form-label">House Number <span class="ds-req">*</span></label>
                <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                    <option value="">Select House</option>
                </select>
                <input type="hidden" id="house_no" name="house_no" value="{{ old('house_no', $isEdit ? $record->house_no : '') }}">
                <div class="text-danger small field-error" data-field="estate_house_master_pk" role="alert">@error('estate_house_master_pk'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="allotment_date" class="form-label">Allotment Date <span class="ds-req">*</span></label>
                <input type="date" class="form-control" id="allotment_date" name="allotment_date" required
                    value="{{ old('allotment_date', $isEdit && $record->allotment_date ? $record->allotment_date->format('Y-m-d') : '') }}">
                <div class="text-danger small field-error" data-field="allotment_date" role="alert">@error('allotment_date'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="possession_date_oth" class="form-label">Possession Date <span class="ds-req">*</span></label>
                <input type="date" class="form-control" id="possession_date_oth" name="possession_date_oth" required
                    value="{{ old('possession_date_oth', $isEdit && $record->possession_date_oth ? $record->possession_date_oth->format('Y-m-d') : '') }}">
                <div class="text-danger small field-error" data-field="possession_date_oth" role="alert">@error('possession_date_oth'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label for="meter_one_display_oth" class="form-label">Electric Meter Number I</label>
                <input type="text" class="form-control" id="meter_one_display_oth" placeholder="Electric Meter Number" readonly>
            </div>

            <div class="col-md-6">
                <label for="meter_reading_oth_primary" class="form-label">Electric Meter Reading I <span class="ds-req">*</span></label>
                <input type="text" class="form-control" id="meter_reading_oth_primary" name="meter_reading_oth"
                    inputmode="numeric" maxlength="10" placeholder="Electric Meter Reading"
                    value="{{ old('meter_reading_oth', $isEdit ? $record->meter_reading_oth : '') }}">
                <div class="text-danger small field-error" data-field="meter_reading_oth" role="alert">@error('meter_reading_oth'){{ $message }}@enderror</div>
            </div>

            {{-- A second meter only exists on some houses; both fields are revealed
                 by the house selection, not by the user. --}}
            <div class="col-md-6 d-none" id="secondary-meter-number-wrapper-oth">
                <label for="meter_two_display_oth" class="form-label">Electric Meter Number II</label>
                <input type="text" class="form-control" id="meter_two_display_oth" placeholder="Electric Meter Number" readonly>
            </div>

            <div class="col-md-6 d-none" id="secondary-meter-wrapper-oth">
                <label for="meter_reading_oth_secondary" class="form-label">Electric Meter Reading II</label>
                <input type="text" class="form-control" id="meter_reading_oth_secondary" name="meter_reading_oth1"
                    inputmode="numeric" maxlength="10" placeholder="Electric Meter Reading"
                    value="{{ old('meter_reading_oth1', $isEdit ? $record->meter_reading_oth1 : '') }}">
                <div class="text-danger small field-error" data-field="meter_reading_oth1" role="alert">@error('meter_reading_oth1'){{ $message }}@enderror</div>
            </div>
        </div>
    </div>

    <div class="{{ $inModal ? 'modal-footer' : 'd-flex justify-content-end gap-2 mt-4' }}">
        @if($inModal)
            <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        @else
            <a href="{{ route('admin.estate.possession-for-others') }}" class="btn ds-btn-cancel">Cancel</a>
        @endif
        <button type="submit" class="btn ds-btn-submit js-epo-submit">{{ $submitLabel }}</button>
    </div>
</form>

<script>
(function() {
    // As a full page this markup renders BEFORE the layout's footer loads jQuery,
    // so defer until the document is parsed. Injected into the modal the document
    // is already complete, so run straight away.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

function init() {
    var form = document.getElementById('possessionForm');
    if (!form || form.dataset.epoBound === '1') return;
    form.dataset.epoBound = '1';

    var $form = $(form);
    var inModal = form.dataset.inModal === '1';
    var blocksUrl = @json(route('admin.estate.possession.blocks'));
    var unitSubTypesUrl = @json(route('admin.estate.possession.unit-sub-types'));
    var housesUrl = @json(route('admin.estate.possession.houses'));
    var unitTypesByCampus = @json($unitTypesByCampus ?? []);
    var listUrl = @json(route('admin.estate.possession-for-others'));
    // Editing: the currently allotted house must still come back from the houses
    // API even though it is occupied.
    var includeHousePk = @json($isEdit ? $record->estate_house_master_pk : null);
    // Editing: keep a saved secondary reading visible even if the house lost its
    // second meter number.
    var hasSecondaryMeterReadingPrefill = @json($isEdit && $record->meter_reading_oth1 !== null && (string) $record->meter_reading_oth1 !== '');

    // Selections to restore after a validation bounce, or when editing.
    var initialSelections = {
        campus: @json(old('estate_campus_master_pk', $isEdit ? $record->estate_campus_master_pk : null)),
        unitType: @json(old('estate_unit_type_master_pk', $isEdit ? $record->estate_unit_type_master_pk : null)),
        block: @json(old('estate_block_master_pk', $isEdit ? $record->estate_block_master_pk : null)),
        unitSub: @json(old('estate_unit_sub_type_master_pk', $isEdit ? $record->estate_unit_sub_type_master_pk : null)),
        house: @json(old('estate_house_master_pk', $isEdit ? $record->estate_house_master_pk : null))
    };
    var isInitializing = true;
    var houseDataCache = {}; // pk -> { house_no, meter_one, meter_two }

    function $f(id) { return $form.find('#' + id); }
    function val(id) { var v = $f(id).val(); return (v === null || v === undefined) ? '' : v; }

    function resetSelect(id, placeholder) {
        $f(id).empty().append($('<option>', { value: '', text: placeholder })).val('');
    }

    /* ---------- Requester -> Request ID + Section ---------- */
    function syncRequesterDisplay() {
        var $opt = $f('estate_other_req_pk').find('option:selected');
        $f('request_id_display').val($opt.attr('data-request-no') || '');
        $f('section_display').val($opt.attr('data-section') || $opt.attr('data-designation') || '');
    }
    $form.on('change', '#estate_other_req_pk', syncRequesterDisplay);
    syncRequesterDisplay();

    /* ---------- Meter reading inputs are digits only ---------- */
    function sanitizeMeterInputs() {
        $form.find('#meter_reading_oth_primary, #meter_reading_oth_secondary').each(function() {
            this.value = String(this.value || '').replace(/\D/g, '').slice(0, 10);
        });
    }
    $form.on('input change', '#meter_reading_oth_primary, #meter_reading_oth_secondary', sanitizeMeterInputs);
    sanitizeMeterInputs();

    /* ---------- Cascade: campus -> unit type -> block -> sub type -> house ---------- */
    $form.on('change', '#estate_campus_master_pk', function() {
        var campusId = val('estate_campus_master_pk');
        resetSelect('estate_unit_type_master_pk', 'Select Unit');
        resetSelect('estate_block_master_pk', 'Select Building');
        resetSelect('estate_unit_sub_type_master_pk', 'Select Sub-type');
        resetSelect('estate_house_master_pk', 'Select House');
        if (!campusId) return;

        if (!isInitializing) {
            initialSelections.unitType = null;
            initialSelections.block = null;
            initialSelections.unitSub = null;
            initialSelections.house = null;
        }

        var list = unitTypesByCampus[campusId] || [];
        var $ut = $f('estate_unit_type_master_pk');
        $.each(list, function(i, ut) {
            $ut.append($('<option>', { value: String(ut.pk), text: ut.unit_type || '' }));
        });
        // A campus with a single unit type picks it for the user.
        $ut.val(initialSelections.unitType ? String(initialSelections.unitType) : (list.length === 1 ? String(list[0].pk) : ''));
        if (list.length) loadBlocks();
    });

    $form.on('change', '#estate_unit_type_master_pk', function() {
        resetSelect('estate_block_master_pk', 'Select Building');
        resetSelect('estate_unit_sub_type_master_pk', 'Select Sub-type');
        resetSelect('estate_house_master_pk', 'Select House');
        if (!isInitializing) {
            initialSelections.block = null;
            initialSelections.unitSub = null;
            initialSelections.house = null;
        }
        loadBlocks();
    });

    function loadBlocks() {
        var campusId = val('estate_campus_master_pk');
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId, unit_type_id: val('estate_unit_type_master_pk') }, function(res) {
            if (!res.status || !res.data) return;
            var $blk = $f('estate_block_master_pk');
            $blk.empty().append($('<option>', { value: '', text: 'Select Building' }));
            $.each(res.data, function(i, b) {
                $blk.append($('<option>', { value: String(b.pk), text: b.block_name || '' }));
            });
            $blk.val(initialSelections.block ? String(initialSelections.block) : '');
            loadUnitSubTypes();
        });
    }

    $form.on('change', '#estate_block_master_pk', function() {
        resetSelect('estate_unit_sub_type_master_pk', 'Select Sub-type');
        resetSelect('estate_house_master_pk', 'Select House');
        if (!isInitializing) {
            initialSelections.unitSub = null;
            initialSelections.house = null;
        }
        loadUnitSubTypes();
    });

    function loadUnitSubTypes() {
        var campusId = val('estate_campus_master_pk');
        var blockId = val('estate_block_master_pk');
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_type_id: val('estate_unit_type_master_pk')
        }, function(res) {
            if (!res.status || !res.data) return;
            var $ust = $f('estate_unit_sub_type_master_pk');
            $ust.empty().append($('<option>', { value: '', text: 'Select Sub-type' }));
            $.each(res.data, function(i, u) {
                $ust.append($('<option>', { value: String(u.pk), text: u.unit_sub_type || '' }));
            });
            $ust.val(initialSelections.unitSub ? String(initialSelections.unitSub) : '');
            loadHouses();
        });
    }

    $form.on('change', '#estate_unit_sub_type_master_pk', function() {
        resetSelect('estate_house_master_pk', 'Select House');
        if (!isInitializing) initialSelections.house = null;
        loadHouses();
    });

    function loadHouses() {
        var campusId = val('estate_campus_master_pk');
        var blockId = val('estate_block_master_pk');
        var unitSubId = val('estate_unit_sub_type_master_pk');
        if (!campusId || !blockId || !unitSubId) return;
        $.get(housesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_sub_type_id: unitSubId,
            unit_type_id: val('estate_unit_type_master_pk'),
            include_house_pk: includeHousePk || ''
        }, function(res) {
            if (!res.status || !res.data) return;
            houseDataCache = {};
            var $h = $f('estate_house_master_pk');
            $h.empty().append($('<option>', { value: '', text: 'Select House' }));
            $.each(res.data, function(i, h) {
                var pk = String(h.pk);
                houseDataCache[pk] = {
                    house_no: (h.house_no != null && h.house_no !== '') ? String(h.house_no) : '',
                    meter_one: (h.meter_one != null && h.meter_one !== '') ? String(h.meter_one) : '',
                    meter_two: (h.meter_two != null && h.meter_two !== '') ? String(h.meter_two) : ''
                };
                $h.append($('<option>', { value: pk, text: houseDataCache[pk].house_no }));
            });
            $h.val(initialSelections.house ? String(initialSelections.house) : '');
            updateHouseNoDisplay();
            isInitializing = false;
        });
    }

    $form.on('change', '#estate_house_master_pk', updateHouseNoDisplay);

    function updateHouseNoDisplay() {
        var pk = val('estate_house_master_pk');
        var data = (pk && houseDataCache[pk]) ? houseDataCache[pk] : { house_no: '', meter_one: '', meter_two: '' };

        $f('house_no').val(data.house_no);
        $f('meter_one_display_oth').val(data.meter_one);
        $f('meter_two_display_oth').val(data.meter_two);

        var hasValidMeterTwo = data.meter_two && String(data.meter_two).trim() !== '' && parseInt(data.meter_two, 10) !== 0;
        var show = hasValidMeterTwo || hasSecondaryMeterReadingPrefill;
        $f('secondary-meter-number-wrapper-oth').toggleClass('d-none', !show);
        $f('secondary-meter-wrapper-oth').toggleClass('d-none', !show);
        if (!show) {
            $f('meter_two_display_oth').val('');
            $f('meter_reading_oth_secondary').val('');
        }
    }

    /* ---------- Submit ---------- */
    function clearErrors() {
        $form.find('.field-error').empty();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.js-epo-form-error').addClass('d-none').text('');
    }

    $form.on('submit', function(e) {
        // Outside the modal this is a plain POST — the page redirects as before.
        if (!inModal) return;

        e.preventDefault();
        var $btn = $form.find('.js-epo-submit');
        clearErrors();
        $btn.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(res) {
                document.dispatchEvent(new CustomEvent('epo:possession-saved', {
                    detail: { message: (res && res.message) || 'Possession saved successfully.' }
                }));
            },
            error: function(xhr) {
                var payload = xhr.responseJSON || {};
                if (xhr.status === 422 && payload.errors) {
                    $.each(payload.errors, function(field, msgs) {
                        var msg = Array.isArray(msgs) ? msgs[0] : msgs;
                        var $slot = $form.find('.field-error[data-field="' + field + '"]');
                        if ($slot.length) {
                            $slot.text(msg);
                        } else {
                            $form.find('.js-epo-form-error').removeClass('d-none').text(msg);
                        }
                        $form.find('[name="' + field + '"]').addClass('is-invalid');
                    });
                    var $first = $form.find('.field-error:not(:empty)').first();
                    if ($first.length) $first[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                if (xhr.status !== 422 || !payload.errors) {
                    $form.find('.js-epo-form-error').removeClass('d-none')
                        .text(payload.message || 'Something went wrong. Please try again.');
                }
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    // Editing / bounced input: replay the cascade from the campus down.
    if (initialSelections.campus) {
        $f('estate_campus_master_pk').val(String(initialSelections.campus)).trigger('change');
    } else {
        isInitializing = false;
    }
}
})();
</script>
