{{-- Partial: Raise Change Request form — rendered both as a full page
     (admin/estate/raise_change_request.blade.php) and as the body of the
     Change Request modal on the Request For Estate listing. One source of
     markup so the two entry points can never drift apart. --}}
@php
    $inModal = $inModal ?? false;
    $formId = $inModal ? 'formRaiseChangeRequestModal' : 'formRaiseChangeRequest';
    $estateCampuses = $estateCampuses ?? collect();
    $unitTypes = $unitTypes ?? collect();
    $homeReqPk = (int) (optional($detail)->estate_home_req_details_pk ?? 0);
@endphp
<form method="POST" action="{{ $formAction ?? '#' }}" id="{{ $formId }}" class="rfe-change-form">
    @csrf
    <input type="hidden" name="estate_home_req_details_pk" value="{{ $homeReqPk }}">

    <div class="{{ $inModal ? 'modal-body' : '' }}">
        <div class="alert alert-danger d-none js-change-errors" role="alert"><span></span></div>

        {{-- Who and what is being changed — all server-derived, so all read-only. --}}
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_request_id">Request ID</label>
                <input type="text" class="form-control" id="{{ $formId }}_request_id"
                    value="{{ optional($detail)->request_id ?? '-' }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_request_date">Request Date</label>
                <input type="text" class="form-control" id="{{ $formId }}_request_date"
                    value="{{ optional($detail)->request_date ?? '-' }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_name">Employee Name</label>
                <input type="text" class="form-control" id="{{ $formId }}_name"
                    value="{{ optional($detail)->name ?? '-' }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_emp_id">Employee ID</label>
                <input type="text" class="form-control" id="{{ $formId }}_emp_id"
                    value="{{ optional($detail)->emp_id ?? '-' }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_designation">Designation</label>
                <input type="text" class="form-control" id="{{ $formId }}_designation"
                    value="{{ optional($detail)->designation ?? '-' }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_current_allotment">Current Allotment</label>
                <input type="text" class="form-control ds-field-highlight" id="{{ $formId }}_current_allotment"
                    value="{{ optional($detail)->current_allotment ?? '-' }}" readonly>
            </div>
        </div>

        <h6 class="ds-modal-section-title mt-4">Request</h6>

        <div class="row g-3 mt-0">
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_estate_name">Estate Name <span class="ds-req">*</span></label>
                <select name="estate_name" id="{{ $formId }}_estate_name" class="form-select js-change-estate" required>
                    <option value="">Select Estate</option>
                    @foreach($estateCampuses as $c)
                        <option value="{{ $c->pk }}" {{ (string) old('estate_name', optional($detail)->estate_campus_master_pk ?? '') === (string) $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                    @endforeach
                </select>
                <div class="text-danger small field-error" data-field="estate_name" role="alert"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_unit_type">Unit Type <span class="ds-req">*</span></label>
                <select name="unit_type" id="{{ $formId }}_unit_type" class="form-select js-change-unit-type" required>
                    <option value="">Select Unit Type</option>
                    @foreach($unitTypes as $u)
                        <option value="{{ $u->pk }}" {{ (string) old('unit_type', optional($detail)->estate_unit_type_master_pk ?? '') === (string) $u->pk ? 'selected' : '' }}>{{ $u->unit_type }}</option>
                    @endforeach
                </select>
                <div class="text-danger small field-error" data-field="unit_type" role="alert"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_building_name">Building Name <span class="ds-req">*</span></label>
                <select name="building_name" id="{{ $formId }}_building_name" class="form-select js-change-building" required>
                    <option value="">Select Building</option>
                </select>
                <div class="text-danger small field-error" data-field="building_name" role="alert"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_unit_sub_type">Unit Sub-Type <span class="ds-req">*</span></label>
                <select name="unit_sub_type" id="{{ $formId }}_unit_sub_type" class="form-select js-change-unit-sub" required>
                    <option value="">Select Sub-Type</option>
                </select>
                <div class="text-danger small field-error" data-field="unit_sub_type" role="alert"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_house_no">House Number Vacant <span class="ds-req">*</span></label>
                <select name="house_no" id="{{ $formId }}_house_no" class="form-select js-change-house" required>
                    <option value="">Select House Number</option>
                </select>
                <div class="form-text text-warning d-none js-change-no-houses">No vacant house in this building / unit sub-type. Try another combination.</div>
                <div class="text-danger small field-error" data-field="house_no" role="alert"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_remarks">Remarks</label>
                <textarea class="form-control" name="remarks" id="{{ $formId }}_remarks" rows="3" maxlength="500"
                    placeholder="e.g. Lorem Ipsum dolor sit amet">{{ old('remarks', optional($detail)->remarks ?? '') }}</textarea>
                <div class="text-danger small field-error" data-field="remarks" role="alert"></div>
            </div>
        </div>
    </div>

    <div class="{{ $inModal ? 'modal-footer' : 'd-flex justify-content-end gap-2 mt-4' }}">
        @if($inModal)
            <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        @else
            <a href="{{ route('admin.estate.request-details', ['id' => $homeReqPk]) }}" class="btn ds-btn-cancel">Cancel</a>
        @endif
        <button type="submit" class="btn ds-btn-submit js-change-submit">Request for Change</button>
    </div>
</form>

<script>
(function() {
    var blocksUrl = @json(route('admin.estate.possession.blocks'));
    var unitSubTypesUrl = @json(route('admin.estate.possession.unit-sub-types'));
    var vacantHousesUrl = @json(route('admin.estate.change-request.vacant-houses'));

    // Scoped to the form, so the page and the modal copies never wire each other up.
    function wire(form) {
        if (!form || form.dataset.changeCascadeBound === '1') return;
        form.dataset.changeCascadeBound = '1';

        var estate = form.querySelector('.js-change-estate');
        var unitType = form.querySelector('.js-change-unit-type');
        var building = form.querySelector('.js-change-building');
        var unitSub = form.querySelector('.js-change-unit-sub');
        var house = form.querySelector('.js-change-house');
        var noHouses = form.querySelector('.js-change-no-houses');
        if (!estate || !building || !unitSub || !house) return;

        function fill(select, items, placeholder, valueKey, labelKey) {
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            (items || []).forEach(function(item) {
                var opt = document.createElement('option');
                opt.value = item[valueKey] != null ? item[valueKey] : item[labelKey];
                opt.textContent = item[labelKey] != null ? item[labelKey] : item[valueKey];
                select.appendChild(opt);
            });
        }

        function resetDownstream() {
            fill(building, [], 'Select Building', 'pk', 'block_name');
            fill(unitSub, [], 'Select Sub-Type', 'pk', 'unit_sub_type');
            fill(house, [], 'Select House Number', 'house_no', 'house_no');
            if (noHouses) noHouses.classList.add('d-none');
        }

        function loadBlocks() {
            resetDownstream();
            if (!estate.value) return;
            var url = blocksUrl + '?campus_id=' + encodeURIComponent(estate.value)
                + '&unit_type_id=' + encodeURIComponent(unitType ? unitType.value : '');
            fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                fill(building, res.data, 'Select Building', 'pk', 'block_name');
            }).catch(function() { /* leave the placeholder in place */ });
        }

        function loadUnitSubTypes() {
            fill(unitSub, [], 'Select Sub-Type', 'pk', 'unit_sub_type');
            fill(house, [], 'Select House Number', 'house_no', 'house_no');
            if (noHouses) noHouses.classList.add('d-none');
            if (!estate.value || !building.value) return;
            var url = unitSubTypesUrl + '?campus_id=' + encodeURIComponent(estate.value)
                + '&block_id=' + encodeURIComponent(building.value)
                + '&unit_type_id=' + encodeURIComponent(unitType ? unitType.value : '');
            fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                fill(unitSub, res.data, 'Select Sub-Type', 'pk', 'unit_sub_type');
            }).catch(function() {});
        }

        function loadHouses() {
            if (noHouses) noHouses.classList.add('d-none');
            fill(house, [], 'Select House Number', 'house_no', 'house_no');
            if (!building.value || !unitSub.value) return;
            var url = vacantHousesUrl + '?campus_id=' + encodeURIComponent(estate.value)
                + '&block_id=' + encodeURIComponent(building.value)
                + '&unit_sub_type_id=' + encodeURIComponent(unitSub.value)
                + '&unit_type_id=' + encodeURIComponent(unitType ? unitType.value : '');
            fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                fill(house, res.data, 'Select House Number', 'house_no', 'house_no');
                if (noHouses && !(res.data && res.data.length)) noHouses.classList.remove('d-none');
            }).catch(function() {});
        }

        estate.addEventListener('change', loadBlocks);
        if (unitType) unitType.addEventListener('change', loadBlocks);
        building.addEventListener('change', loadUnitSubTypes);
        unitSub.addEventListener('change', loadHouses);

        if (estate.value) loadBlocks();
    }

    window.initRaiseChangeRequestCascade = function(root) {
        (root || document).querySelectorAll('form.rfe-change-form').forEach(wire);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { window.initRaiseChangeRequestCascade(); });
    } else {
        window.initRaiseChangeRequestCascade();
    }
})();
</script>
