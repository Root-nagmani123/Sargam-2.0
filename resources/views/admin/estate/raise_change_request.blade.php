@extends('admin.layouts.master')

@section('title', 'Raise Change Request - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Raise Change Request" />
    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-info bg-opacity-10 border-0 py-3">
            <h1 class="h4 fw-bold mb-0 text-body">
                <i class="bi bi-arrow-left-right me-2"></i> Raise Change Request
            </h1>
            <p class="text-body-secondary small mb-0 mt-1">Employee must already have a house allotted. Select the new (vacant) house and submit.</p>
        </div>
        <div class="card-body p-4 p-lg-5">
            <form method="POST" action="{{ $formAction ?? '#' }}" id="formRaiseChangeRequest" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="estate_home_req_details_pk" value="{{ (int) ($detail->estate_home_req_details_pk ?? 0) }}">

                {{-- Read-only: Request & Employee --}}
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Request ID</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10" value="{{ $detail->request_id ?? '—' }}" readonly>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Request Date</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10" value="{{ $detail->request_date ?? '—' }}" readonly>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10" value="{{ $detail->name ?? '—' }}" readonly>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Emp. ID</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10" value="{{ $detail->emp_id ?? '—' }}" readonly>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Designation</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10" value="{{ $detail->designation ?? '—' }}" readonly>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Current Allotment <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-warning bg-opacity-15 fw-semibold" value="{{ $detail->current_allotment ?? '—' }}" readonly>
                    </div>
                </div>

                {{-- New house selection --}}
                <h2 class="h6 fw-bold text-body mb-3">Requested New House (select vacant house)</h2>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="raise_estate_name" class="form-label fw-semibold">Estate Name <span class="text-danger">*</span></label>
                        <select name="estate_name" id="raise_estate_name" class="form-select" required>
                            <option value="">— Select —</option>
                            @foreach($estateCampuses ?? [] as $c)
                                <option value="{{ $c->pk }}" {{ (string) old('estate_name', $detail->estate_campus_master_pk ?? '') === (string) $c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="raise_unit_type" class="form-label fw-semibold">Unit Type <span class="text-danger">*</span></label>
                        <select name="unit_type" id="raise_unit_type" class="form-select" required>
                            <option value="">— Select —</option>
                            @foreach($unitTypes ?? [] as $u)
                                <option value="{{ $u->pk }}" {{ (string) old('unit_type', $detail->estate_unit_type_master_pk ?? '') === (string) $u->pk ? 'selected' : '' }}>{{ $u->unit_type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="raise_building_name" class="form-label fw-semibold">Building Name <span class="text-danger">*</span></label>
                        <select name="building_name" id="raise_building_name" class="form-select" required>
                            <option value="">— Select —</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="raise_unit_sub_type" class="form-label fw-semibold">Unit Sub Type <span class="text-danger">*</span></label>
                        <select name="unit_sub_type" id="raise_unit_sub_type" class="form-select" required>
                            <option value="">— Select —</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="raise_house_no" class="form-label fw-semibold">House No. (vacant) <span class="text-danger">*</span></label>
                        <select name="house_no" id="raise_house_no" class="form-select" required>
                            <option value="">— Select Estate, Block & Unit Sub Type first —</option>
                        </select>
                        <div id="raiseNoHouses" class="form-text text-warning d-none">No vacant house in this block/unit. Select another block or unit sub type.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="raise_remarks" class="form-label fw-semibold">Remarks</label>
                    <textarea class="form-control" id="raise_remarks" name="remarks" rows="3" placeholder="Optional reason for change...">{{ old('remarks', $detail->remarks ?? '') }}</textarea>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-lg me-2"></i>Raise Change Request
                    </button>
                    <a href="{{ route('admin.estate.request-details', ['id' => $detail->estate_home_req_details_pk ?? 0]) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-lg me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var blocksUrl = '{{ route("admin.estate.possession.blocks") }}';
    var unitSubTypesUrl = '{{ route("admin.estate.possession.unit-sub-types") }}';
    var vacantHousesUrl = '{{ route("admin.estate.change-request.vacant-houses") }}';

    function resetBuildingUnitHouse() {
        $('#raise_building_name').html('<option value="">— Select —</option>');
        $('#raise_unit_sub_type').html('<option value="">— Select —</option>');
        $('#raise_house_no').html('<option value="">— Select Estate, Block & Unit Sub Type first —</option>');
        $('#raiseNoHouses').addClass('d-none');
    }

    function loadBlocks() {
        var campusId = $('#raise_estate_name').val();
        var unitTypeId = $('#raise_unit_type').val();
        resetBuildingUnitHouse();
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId, unit_type_id: unitTypeId || '' }, function(res) {
            if (res.status && res.data) {
                res.data.forEach(function(b) {
                    $('#raise_building_name').append('<option value="' + b.pk + '">' + (b.block_name || b.pk) + '</option>');
                });
            }
        });
    }

    function loadUnitSubTypes() {
        var campusId = $('#raise_estate_name').val();
        var blockId = $('#raise_building_name').val();
        var unitTypeId = $('#raise_unit_type').val();
        $('#raise_unit_sub_type').html('<option value="">— Select —</option>');
        $('#raise_house_no').html('<option value="">— Select Block & Unit Sub Type first —</option>');
        $('#raiseNoHouses').addClass('d-none');
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId || '' }, function(res) {
            if (res.status && res.data) {
                res.data.forEach(function(u) {
                    $('#raise_unit_sub_type').append('<option value="' + u.pk + '">' + (u.unit_sub_type || u.pk) + '</option>');
                });
            }
        });
    }

    function loadVacantHouses() {
        var campusId = $('#raise_estate_name').val();
        var blockId = $('#raise_building_name').val();
        var unitSubId = $('#raise_unit_sub_type').val();
        var unitTypeId = $('#raise_unit_type').val();
        $('#raise_house_no').html('<option value="">— Loading —</option>');
        $('#raiseNoHouses').addClass('d-none');
        if (!blockId || !unitSubId) {
            $('#raise_house_no').html('<option value="">— Select Block & Unit Sub Type first —</option>');
            return;
        }
        $.get(vacantHousesUrl, {
            campus_id: campusId,
            block_id: blockId,
            unit_sub_type_id: unitSubId,
            unit_type_id: unitTypeId || ''
        }, function(res) {
            var $sel = $('#raise_house_no');
            $sel.html('<option value="">— Select House —</option>');
            if (res.status && res.data && res.data.length) {
                res.data.forEach(function(h) {
                    $sel.append('<option value="' + (h.house_no || h.pk) + '">' + (h.house_no || h.pk) + '</option>');
                });
            } else {
                $('#raiseNoHouses').removeClass('d-none');
            }
        });
    }

    $(document).ready(function() {
        $('#raise_estate_name, #raise_unit_type').on('change', loadBlocks);
        $('#raise_building_name').on('change', loadUnitSubTypes);
        $('#raise_unit_sub_type').on('change', loadVacantHouses);

        // Initial load if pre-selected
        if ($('#raise_estate_name').val()) loadBlocks();
    });
})();
</script>
@endpush
