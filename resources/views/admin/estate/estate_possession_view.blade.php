@extends('admin.layouts.master')

@section('title', 'Possession View - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Possession View"></x-breadcrum>
    <x-session_message />
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
                        <input type="hidden" id="house_no" name="house_no" value="{{ old('house_no', isset($record) ? $record->house_no : '') }}">
                        <div class="form-text">House No.</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="allotment_date" class="form-label">Allotment Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                type="date"
                                class="form-control"
                                id="allotment_date"
                                name="allotment_date"
                                required
                                value="{{ old('allotment_date', isset($record) && $record->allotment_date ? $record->allotment_date->format('Y-m-d') : '') }}"
                            >
                        </div>
                        <div class="form-text">Allotment Date</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="possession_date_oth" class="form-label">Possession Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                type="date"
                                class="form-control"
                                id="possession_date_oth"
                                name="possession_date_oth"
                                required
                                value="{{ old('possession_date_oth', isset($record) && $record->possession_date_oth ? $record->possession_date_oth->format('Y-m-d') : '') }}"
                            >
                        </div>
                        <div class="form-text">Possession Date</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6">
                                <label for="meter_one_display_oth" class="form-label">Electric Meter No. (I)</label>
                                <input
                                    type="text"
                                    class="form-control bg-body-secondary"
                                    id="meter_one_display_oth"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="meter_reading_oth_primary" class="form-label">Electric Meter Reading (I) <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="meter_reading_oth_primary"
                                    name="meter_reading_oth"
                                    inputmode="numeric"
                                    min="0"
                                    step="1"
                                    maxlength="10"
                                    value="{{ old('meter_reading_oth', isset($record) ? $record->meter_reading_oth : '') }}"
                                    placeholder="Primary (max 10 digits)"
                                    oninput="this.value=this.value.replace(/\\D/g,'').slice(0,10);"
                                >
                            </div>
                        </div>
                        <div class="row g-3 align-items-end mt-1" id="secondary-meter-wrapper-oth">
                            <div class="col-12 col-md-6">
                                <label for="meter_two_display_oth" class="form-label">Electric Meter No. (II)</label>
                                <input
                                    type="text"
                                    class="form-control bg-body-secondary"
                                    id="meter_two_display_oth"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="meter_reading_oth_secondary" class="form-label">Electric Meter Reading (II)</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="meter_reading_oth_secondary"
                                    name="meter_reading_oth1"
                                    inputmode="numeric"
                                    min="0"
                                    step="1"
                                    maxlength="10"
                                    value="{{ old('meter_reading_oth1', isset($record) ? $record->meter_reading_oth1 : '') }}"
                                    placeholder="Secondary (max 10 digits)"
                                    oninput="this.value=this.value.replace(/\\D/g,'').slice(0,10);"
                                >
                            </div>
                        </div>
                        <div class="form-text mt-1">
                            Electric Meter Reading (Primary / Secondary)
                        </div>
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

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    .select2-container--open { z-index: 1060; } /* sirf khula dropdown modal ke upar; closed widget normal flow me (modal ke peeche) */
    .select2-container--default .select2-selection--single { min-height: calc(1.5em + 0.75rem + 2px); display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0.25rem; }
</style>
@endpush

@push('scripts')
{{-- Select2 JS globally footer (admin.layouts.footer) se load hoti hai; yahan include ki zaroorat nahi. --}}
<script>
$(document).ready(function() {
    const blocksUrl = "{{ route('admin.estate.possession.blocks') }}";
    const unitSubTypesUrl = "{{ route('admin.estate.possession.unit-sub-types') }}";
    const housesUrl = "{{ route('admin.estate.possession.houses') }}";
    const unitTypesByCampus = @json($unitTypesByCampus ?? []);
    // When editing an existing possession, ensure the currently allotted house
    // is still returned from the "houses" API even though it is occupied.
    const includeHousePk = @json(isset($record) ? $record->estate_house_master_pk : null);
    // When editing, preserve secondary meter reading prefill even if house has no meter_two
    const hasSecondaryMeterReadingPrefill = @json(isset($record) && $record->meter_reading_oth1 !== null && (string)$record->meter_reading_oth1 !== '');

    // Preserve dependent dropdown state after validation errors (old input),
    // and also when editing an existing record.
    let initialSelections = {
        campus: @json(old('estate_campus_master_pk', isset($record) ? $record->estate_campus_master_pk : null)),
        unitType: @json(old('estate_unit_type_master_pk', isset($record) ? $record->estate_unit_type_master_pk : null)),
        block: @json(old('estate_block_master_pk', isset($record) ? $record->estate_block_master_pk : null)),
        unitSub: @json(old('estate_unit_sub_type_master_pk', isset($record) ? $record->estate_unit_sub_type_master_pk : null)),
        house: @json(old('estate_house_master_pk', isset($record) ? $record->estate_house_master_pk : null)),
    };
    let isInitializing = true;
    var houseDataCache = {}; // pk -> { house_no, meter_one, meter_two }

    // NOTE: ye module pehle TomSelect use karta tha; ab Select2 (baaki app ke saath consistent).
    // ts* variables sirf truthiness/state tracking ke liye rakhe hain (Select2 instance $(el).data('select2') se milta hai).
    var estatePlaceholder = '---select---';
    var tsRequester = null, tsCampus = null, tsUnitType = null, tsBlock = null, tsUnitSub = null, tsHouse = null;

    // Select2 initialized hai ya nahi.
    function isSelect2(el) {
        return !!(el && $(el).data('select2'));
    }
    // Safe destroy (agar Select2 laga ho to hi).
    function destroySelect2(el) {
        if (isSelect2(el)) { try { $(el).select2('destroy'); } catch (e) {} }
    }
    // Ek select ko Select2 me init karo. Ye page-level form hai (modal nahi) -> dropdownParent omit.
    function initEstateSelect2(el, placeholder) {
        if (!el || typeof $.fn.select2 === 'undefined') return null;
        destroySelect2(el);
        $(el).select2({
            placeholder: placeholder || estatePlaceholder,
            allowClear: false,
            width: '100%'
        });
        return $(el);
    }
    // Ek dependent select ko sirf empty placeholder option ke saath reset karo, fir Select2
    // ko silently refresh (change.select2 -> app cascade handlers nahi firte).
    function resetSelect(el) {
        if (!el) return;
        var $sel = $(el);
        $sel.empty().append($('<option>', { value: '', text: estatePlaceholder }));
        $sel.val('');
        $sel.trigger('change.select2');
    }

    // Native <select> value hamesha Select2 ke saath sync rehti hai, to plain val() kaafi hai.
    function getSelectVal(sel) {
        var v = sel ? $(sel).val() : '';
        return (v === null || v === undefined) ? '' : v;
    }

    function getSelectedOptionData(selId, dataAttr) {
        var el = document.getElementById(selId);
        if (!el) return '';
        var val = getSelectVal(el);
        var opt = $(el).find('option').filter(function() { return $(this).val() == val; }).first();
        return opt.attr(dataAttr) || '';
    }

    // Static dropdowns: init Select2 (prefilled value already in DOM)
    var elRequester = document.getElementById('estate_other_req_pk');
    var elCampus = document.getElementById('estate_campus_master_pk');
    if (elRequester) tsRequester = initEstateSelect2(elRequester, estatePlaceholder);
    if (elCampus) tsCampus = initEstateSelect2(elCampus, estatePlaceholder);

    // Dynamic dropdowns: init with placeholder; options native <option> se rebuild hote hain
    var elUnitType = document.getElementById('estate_unit_type_master_pk');
    var elBlock = document.getElementById('estate_block_master_pk');
    var elUnitSub = document.getElementById('estate_unit_sub_type_master_pk');
    var elHouse = document.getElementById('estate_house_master_pk');
    if (elUnitType) tsUnitType = initEstateSelect2(elUnitType, estatePlaceholder);
    if (elBlock) tsBlock = initEstateSelect2(elBlock, estatePlaceholder);
    if (elUnitSub) tsUnitSub = initEstateSelect2(elUnitSub, estatePlaceholder);
    if (elHouse) tsHouse = initEstateSelect2(elHouse, estatePlaceholder);

    // Requester change -> fill request_id and section (use data from option by value)
    function syncRequesterDisplay() {
        var reqNo = getSelectedOptionData('estate_other_req_pk', 'data-request-no');
        var section = getSelectedOptionData('estate_other_req_pk', 'data-section') || getSelectedOptionData('estate_other_req_pk', 'data-designation');
        $('#request_id_display').val(reqNo);
        $('#section_display').val(section);
    }
    $(document).on('change', '#estate_other_req_pk', syncRequesterDisplay);
    syncRequesterDisplay();

    // Hide secondary meter row by default; will be shown when a valid
    // second meter number exists for the selected house, or when editing with prefilled secondary reading.
    if (!hasSecondaryMeterReadingPrefill) {
        $('#secondary-meter-wrapper-oth').hide();
    } else {
        $('#secondary-meter-wrapper-oth').show();
    }

    function sanitizeOtherMeterInputs() {
        $('#meter_reading_oth_primary, #meter_reading_oth_secondary').each(function() {
            this.value = String(this.value || '').replace(/\D/g, '').slice(0, 10);
        });
    }
    $('#meter_reading_oth_primary, #meter_reading_oth_secondary').on('input change', function() {
        sanitizeOtherMeterInputs();
    });
    $('#meter_reading_oth_primary, #meter_reading_oth_secondary').on('keydown', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) {
            e.preventDefault();
        }
    });
    sanitizeOtherMeterInputs();

    function clearDynamicTs(clearUnitType, clearBlock, clearUnitSub, clearHouse) {
        // Select2 me options native <option> se rebuild hote hain; reset silently (change.select2)
        // -> cascade change handlers dobara fire nahi hote (jaise TomSelect ka silent setValue).
        if (clearUnitType && elUnitType) resetSelect(elUnitType);
        if (clearBlock && elBlock) resetSelect(elBlock);
        if (clearUnitSub && elUnitSub) resetSelect(elUnitSub);
        if (clearHouse && elHouse) resetSelect(elHouse);
    }

    // Campus change -> fill unit types from pre-loaded data, then blocks
    $(document).on('change', '#estate_campus_master_pk', function() {
        var campusId = getSelectVal(this);
        clearDynamicTs(true, true, true, true);
        if (!campusId) return;

        if (!isInitializing) {
            initialSelections.unitType = null;
            initialSelections.block = null;
            initialSelections.unitSub = null;
            initialSelections.house = null;
        }

        var list = unitTypesByCampus[campusId] || [];
        if (elUnitType) {
            var $ut = $('#estate_unit_type_master_pk');
            $ut.empty().append($('<option>', { value: '', text: estatePlaceholder }));
            $.each(list, function(i, ut) {
                $ut.append($('<option>', { value: String(ut.pk), text: ut.unit_type || '' }));
            });
            var toSet = initialSelections.unitType ? String(initialSelections.unitType) : (list.length === 1 ? String(list[0].pk) : '');
            $ut.val(toSet || '');
            // Silent refresh: prefill/auto-select karte waqt cascade change handler nahi chalna chahiye.
            $ut.trigger('change.select2');
        }
        if (list.length) loadBlocks();
    });

    // Unit Type change -> reload blocks
    $(document).on('change', '#estate_unit_type_master_pk', function() {
        clearDynamicTs(false, true, true, true);
        if (!isInitializing) {
            initialSelections.block = null;
            initialSelections.unitSub = null;
            initialSelections.house = null;
        }
        loadBlocks();
    });

    function loadBlocks() {
        var campusId = getSelectVal(document.getElementById('estate_campus_master_pk'));
        var unitTypeId = getSelectVal(document.getElementById('estate_unit_type_master_pk'));
        if (!campusId) return;
        $.get(blocksUrl, {
            campus_id: campusId,
            unit_type_id: unitTypeId || ''
        }, function(res) {
            if (res.status && res.data && elBlock) {
                var $blk = $('#estate_block_master_pk');
                $blk.empty().append($('<option>', { value: '', text: estatePlaceholder }));
                $.each(res.data, function(i, b) {
                    $blk.append($('<option>', { value: String(b.pk), text: b.block_name || '' }));
                });
                var toSet = initialSelections.block ? String(initialSelections.block) : '';
                $blk.val(toSet || '');
                $blk.trigger('change.select2');
                loadUnitSubTypes();
            }
        });
    }

    // Block change -> load unit sub types
    $(document).on('change', '#estate_block_master_pk', function() {
        clearDynamicTs(false, false, true, true);
        if (!isInitializing) {
            initialSelections.unitSub = null;
            initialSelections.house = null;
        }
        loadUnitSubTypes();
    });

    function loadUnitSubTypes() {
        var campusId = getSelectVal(document.getElementById('estate_campus_master_pk'));
        var blockId = getSelectVal(document.getElementById('estate_block_master_pk'));
        var unitTypeId = getSelectVal(document.getElementById('estate_unit_type_master_pk'));
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_type_id: unitTypeId
        }, function(res) {
            if (res.status && res.data && elUnitSub) {
                var $ust = $('#estate_unit_sub_type_master_pk');
                $ust.empty().append($('<option>', { value: '', text: estatePlaceholder }));
                $.each(res.data, function(i, u) {
                    $ust.append($('<option>', { value: String(u.pk), text: u.unit_sub_type || '' }));
                });
                var toSet = initialSelections.unitSub ? String(initialSelections.unitSub) : '';
                $ust.val(toSet || '');
                $ust.trigger('change.select2');
                loadHouses();
            }
        });
    }

    // Unit sub type change -> load houses
    $(document).on('change', '#estate_unit_sub_type_master_pk', function() {
        if (elHouse) resetSelect(elHouse);
        if (!isInitializing) initialSelections.house = null;
        loadHouses();
    });

    function loadHouses() {
        var campusId = getSelectVal(document.getElementById('estate_campus_master_pk'));
        var blockId = getSelectVal(document.getElementById('estate_block_master_pk'));
        var unitSubId = getSelectVal(document.getElementById('estate_unit_sub_type_master_pk'));
        var unitTypeId = getSelectVal(document.getElementById('estate_unit_type_master_pk'));
        if (!campusId || !blockId || !unitSubId) return;
        $.get(housesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_sub_type_id: unitSubId,
            unit_type_id: unitTypeId,
            include_house_pk: includeHousePk || ''
        }, function(res) {
            if (res.status && res.data && elHouse) {
                houseDataCache = {};
                var $h = $('#estate_house_master_pk');
                $h.empty().append($('<option>', { value: '', text: estatePlaceholder }));
                $.each(res.data, function(i, h) {
                    var pk = String(h.pk);
                    var houseNo = (h.house_no != null && h.house_no !== '') ? String(h.house_no) : '';
                    houseDataCache[pk] = {
                        house_no: houseNo,
                        meter_one: (h.meter_one != null && h.meter_one !== '') ? String(h.meter_one) : '',
                        meter_two: (h.meter_two != null && h.meter_two !== '') ? String(h.meter_two) : ''
                    };
                    $h.append($('<option>', { value: pk, text: houseNo }));
                });
                var toSet = initialSelections.house ? String(initialSelections.house) : '';
                $h.val(toSet || '');
                // Silent: setValue(toSet, true) equivalent -> house change handler abhi nahi.
                $h.trigger('change.select2');
                updateHouseNoDisplay();
                if (toSet) {
                    setTimeout(function() {
                        // Non-silent: setValue(toSet, false) equivalent -> house change handler fire ho (display update).
                        if (getSelectVal(elHouse) !== toSet) {
                            $h.val(toSet).trigger('change');
                        }
                        updateHouseNoDisplay();
                    }, 50);
                }
                isInitializing = false;
            }
        });
    }

    $(document).on('change', '#estate_house_master_pk', updateHouseNoDisplay);

    function updateHouseNoDisplay() {
        var el = document.getElementById('estate_house_master_pk');
        var val = el ? getSelectVal(el) : '';
        var houseNo = '', meterOne = '', meterTwo = '';
        if (val && houseDataCache[val]) {
            houseNo = houseDataCache[val].house_no || '';
            meterOne = houseDataCache[val].meter_one || '';
            meterTwo = houseDataCache[val].meter_two || '';
        } else if (el && val) {
            var opt = $(el).find('option').filter(function() { return $(this).val() == val; }).first();
            houseNo = opt.data('house-no') || opt.text() || '';
            meterOne = opt.attr('data-meter-one') || '';
            meterTwo = opt.attr('data-meter-two') || '';
        }
        $('#house_no').val(houseNo);
        $('#meter_one_display_oth').val(meterOne);
        $('#meter_two_display_oth').val(meterTwo);

        var hasValidMeterTwo = meterTwo && String(meterTwo).trim() !== '' && parseInt(meterTwo, 10) !== 0;
        if (hasValidMeterTwo) {
            $('#secondary-meter-wrapper-oth').show();
        } else if (hasSecondaryMeterReadingPrefill) {
            // Editing with prefilled secondary reading: keep wrapper visible and do not clear value
            $('#secondary-meter-wrapper-oth').show();
        } else {
            $('#secondary-meter-wrapper-oth').hide();
            $('#meter_two_display_oth').val('');
            $('#meter_reading_oth_secondary').val('');
        }
    }

    // Load initial data if editing
    if (initialSelections.campus) {
        $('#estate_campus_master_pk').trigger('change');
    }
});
</script>
@endpush
