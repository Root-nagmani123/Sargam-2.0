{{-- Partial: Change Request Details form (used in full page and in modal) --}}
@php
    $inModal = $inModal ?? false;
    $formId = $inModal ? 'formChangeRequestDetailsModal' : 'formChangeRequestDetails';
    $estateCampuses = $estateCampuses ?? collect();
    $unitTypes = $unitTypes ?? collect();
    $buildings = $buildings ?? collect();
    $unitSubTypes = $unitSubTypes ?? collect();
    $houseOptions = $houseOptions ?? collect();
@endphp
<form method="POST" action="{{ $formAction ?? '#' }}" id="{{ $formId }}" class="needs-validation" novalidate>
    @csrf

    @if(!optional($detail)->pk)
        <div class="alert alert-warning mb-4">No change request details found.</div>
    @endif

    {{-- Personal and Employment Details (read-only) --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Request ID <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->request_id ?? '—' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Request Date <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->request_date ?? '—' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->name ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Emp. ID <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->emp_id ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Designation <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->designation ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Current Pay scale <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->pay_scale ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Date of Joining in Current Pay Scale <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->doj_pay_scale ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Date of joining in Academy <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->doj_academy ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Date of joining in Service <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->doj_service ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Current Allotment <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->current_allotment ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label fw-semibold">Requested Change House <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-warning bg-opacity-10 border-secondary border-opacity-25" value="{{ optional($detail)->requested_change_house ?? '' }}" readonly>
        </div>
    </div>

    {{-- Change Unit Sub Type for House Details --}}
    <!-- <div class="mb-4">
        <h2 class="h6 fw-bold text-body mb-1">Change Unit Sub Type for House Details <span class="text-danger">*</span></h2>
        <p class="text-body-secondary small mb-3">Change Unit Sub Type for House Details</p>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th class="text-white fw-semibold text-uppercase small">Estate Name</th>
                        <th class="text-white fw-semibold text-uppercase small">Unit Type</th>
                        <th class="text-white fw-semibold text-uppercase small">Building Name</th>
                        <th class="text-white fw-semibold text-uppercase small">Unit Sub Type</th>
                        <th class="text-white fw-semibold text-uppercase small">House No.s</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="estate_name" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                @foreach($estateCampuses as $campus)
                                    <option value="{{ $campus->pk }}"
                                        {{ (string) old('estate_name', optional($detail)->estate_campus_master_pk) === (string) $campus->pk ? 'selected' : '' }}>
                                        {{ $campus->campus_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="unit_type" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                @foreach($unitTypes as $unitType)
                                    <option value="{{ $unitType->pk }}"
                                        {{ (string) old('unit_type', optional($detail)->estate_unit_type_master_pk) === (string) $unitType->pk ? 'selected' : '' }}>
                                        {{ $unitType->unit_type }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="building_name" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->pk }}"
                                        {{ (string) old('building_name', optional($detail)->estate_block_master_pk) === (string) $building->pk ? 'selected' : '' }}>
                                        {{ $building->block_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="unit_sub_type" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                @foreach($unitSubTypes as $subType)
                                    <option value="{{ $subType->pk }}"
                                        {{ (string) old('unit_sub_type', optional($detail)->estate_unit_sub_type_master_pk) === (string) $subType->pk ? 'selected' : '' }}>
                                        {{ $subType->unit_sub_type }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="house_no" class="form-select form-select-sm" required>
                                <option value="">— Select —</option>
                                @foreach($houseOptions as $house)
                                    <option value="{{ $house->house_no }}"
                                        {{ (string) old('house_no', optional($detail)->change_house_no) === (string) $house->house_no ? 'selected' : '' }}>
                                        {{ $house->label }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Remarks --}}
    <div class="mb-4">
        <label for="remarks_{{ $formId }}" class="form-label fw-semibold">Remarks</label>
        <textarea class="form-control" id="remarks_{{ $formId }}" name="remarks" rows="4" placeholder="Enter remarks...">{{ old('remarks', optional($detail)->remarks ?? '') }}</textarea>
    </div>

    {{-- Action buttons --}}
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-success px-4" {{ ($formAction ?? '#') === '#' ? 'disabled' : '' }}>
            <i class="bi bi-save me-2"></i>Save
        </button>
        @if($inModal)
            <button type="button" class="btn btn-danger px-4 btn-close-change-modal" data-bs-dismiss="modal" aria-label="Close">
                <i class="bi bi-x-lg me-2"></i>Cancel
            </button>
        @else
            <a href="{{ url()->previous() }}" class="btn btn-danger px-4">
                <i class="bi bi-x-lg me-2"></i>Cancel
            </a>
        @endif
    </div> -->
</form>

{{-- Cascade: Estate/Unit Type → Buildings, Building → Unit Sub Types, Unit Sub Type → Houses. For modal, parent must call initChangeRequestDetailsCascade(modalBody) after injecting HTML. --}}
<script>
(function() {
    var blocksUrl = '{{ route("admin.estate.possession.blocks") }}';
    var unitSubTypesUrl = '{{ route("admin.estate.possession.unit-sub-types") }}';
    var vacantHousesUrl = '{{ route("admin.estate.change-request.vacant-houses") }}';

    function runCascade(formRoot) {
        if (!formRoot) return;
        var $estate = formRoot.querySelector('select[name="estate_name"]');
        var $unitType = formRoot.querySelector('select[name="unit_type"]');
        var $building = formRoot.querySelector('select[name="building_name"]');
        var $unitSub = formRoot.querySelector('select[name="unit_sub_type"]');
        var $house = formRoot.querySelector('select[name="house_no"]');
        if (!$estate || !$building || !$unitSub || !$house) return;

        function getVal(el) { return el ? el.value : ''; }

        function loadBlocks() {
            var campusId = getVal($estate);
            var unitTypeId = getVal($unitType);
            $building.innerHTML = '<option value="">— Select —</option>';
            $unitSub.innerHTML = '<option value="">— Select —</option>';
            $house.innerHTML = '<option value="">— Select —</option>';
            if (!campusId) return;
            fetch(blocksUrl + '?campus_id=' + encodeURIComponent(campusId) + (unitTypeId ? '&unit_type_id=' + encodeURIComponent(unitTypeId) : ''))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.data) res.data.forEach(function(b) {
                        var opt = document.createElement('option');
                        opt.value = b.pk;
                        opt.textContent = b.block_name || b.pk;
                        $building.appendChild(opt);
                    });
                });
        }

        function loadUnitSubTypes() {
            var campusId = getVal($estate);
            var blockId = getVal($building);
            var unitTypeId = getVal($unitType);
            $unitSub.innerHTML = '<option value="">— Select —</option>';
            $house.innerHTML = '<option value="">— Select —</option>';
            if (!campusId || !blockId) return;
            var url = unitSubTypesUrl + '?campus_id=' + encodeURIComponent(campusId) + '&block_id=' + encodeURIComponent(blockId);
            if (unitTypeId) url += '&unit_type_id=' + encodeURIComponent(unitTypeId);
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.data) res.data.forEach(function(u) {
                        var opt = document.createElement('option');
                        opt.value = u.pk;
                        opt.textContent = u.unit_sub_type || u.pk;
                        $unitSub.appendChild(opt);
                    });
                });
        }

        function loadHouses() {
            var campusId = getVal($estate);
            var blockId = getVal($building);
            var unitSubId = getVal($unitSub);
            var unitTypeId = getVal($unitType);
            $house.innerHTML = '<option value="">— Loading —</option>';
            if (!blockId || !unitSubId) {
                $house.innerHTML = '<option value="">— Select Building & Unit Sub Type first —</option>';
                return;
            }
            var url = vacantHousesUrl + '?block_id=' + encodeURIComponent(blockId) + '&unit_sub_type_id=' + encodeURIComponent(unitSubId);
            if (campusId) url += '&campus_id=' + encodeURIComponent(campusId);
            if (unitTypeId) url += '&unit_type_id=' + encodeURIComponent(unitTypeId);
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    $house.innerHTML = '<option value="">— Select House —</option>';
                    if (res.data && res.data.length) res.data.forEach(function(h) {
                        var opt = document.createElement('option');
                        opt.value = h.house_no || h.pk;
                        opt.textContent = h.house_no || h.pk;
                        $house.appendChild(opt);
                    });
                });
        }

        $estate.addEventListener('change', loadBlocks);
        $unitType.addEventListener('change', loadBlocks);
        $building.addEventListener('change', loadUnitSubTypes);
        $unitSub.addEventListener('change', loadHouses);
    }

    window.initChangeRequestDetailsCascade = function(root) {
        root = root || document;
        var form = root.querySelector('#formChangeRequestDetailsModal') || root.querySelector('#formChangeRequestDetails');
        runCascade(form ? form.closest('form') || form : root);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { window.initChangeRequestDetailsCascade(); });
    } else {
        window.initChangeRequestDetailsCascade();
    }
})();
</script>
