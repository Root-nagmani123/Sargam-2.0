@extends('admin.layouts.master')

@section('title', 'Return House - Sargam')

@section('setup_content')
<div class="container-fluid py-2">
    <!-- Breadcrumb -->
    <x-breadcrum :title="'Return House'" :items="['Home', 'Estate Management', 'Return House']" />

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 fw-semibold text-body mb-1">Return House</h2>
            <p class="text-body-secondary small mb-0">Manage house returns and request new allotments</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestHouseModal">
            <i class="bi bi-plus-circle me-2"></i>Request House
        </button>
    </div>

    <div id="return-house-alerts">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i>
                <span class="flex-grow-1">{{ session('success') }}</span>
                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i>
                <span class="flex-grow-1">{{ session('error') }}</span>
                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <!-- Request House Modal - Add Request Details (dynamic dropdowns from DB) -->
    <div class="modal fade" id="requestHouseModal" tabindex="-1" aria-labelledby="requestHouseModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div class="modal-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" id="requestHouseModalLabel">Add Request Details</h5>
                        <p class="text-body-secondary small mb-0 mt-1">Please add Request Details</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="requestHouseForm" method="POST" action="#" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <!-- Employee Type -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">Employee Type <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3 pt-1">
                                <div class="form-check form-check-inline border rounded-2 px-3 py-2 bg-body-secondary bg-opacity-10">
                                    <input class="form-check-input mt-1" type="radio" name="employee_type" id="empTypeLbsnaa" value="LBSNAA" checked>
                                    <label class="form-check-label fw-medium" for="empTypeLbsnaa">LBSNAA</label>
                                </div>
                                <div class="form-check form-check-inline border rounded-2 px-3 py-2 bg-body-secondary bg-opacity-10">
                                    <input class="form-check-input mt-1" type="radio" name="employee_type" id="empTypeOther" value="Other Employee">
                                    <label class="form-check-label fw-medium" for="empTypeOther">Other Employee</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_employee_name" class="form-label fw-medium">Employee Name <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_employee_name" name="employee_name" required>
                                    <option value="">--Select--</option>
                                </select>
                                <div class="form-text">Select Name</div>
                            </div>
                            <div class="col-md-6">
                                <label for="request_section_name" class="form-label fw-medium">Section Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="request_section_name" name="section_name" placeholder="Section Name" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_estate_name" class="form-label fw-medium">Estate Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="request_estate_name" name="estate_name" placeholder="Estate Name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="request_unit_name" class="form-label fw-medium">Unit Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="request_unit_name" name="unit_name" placeholder="Unit Name" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_building_name" class="form-label fw-medium">Building Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="request_building_name" name="building_name" placeholder="Building Name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="request_house_no" class="form-label fw-medium">House No. <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="request_house_no" name="house_no" placeholder="House No." required>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_unit_sub_type" class="form-label fw-medium">Unit Sub Type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="request_unit_sub_type" name="unit_sub_type" placeholder="Unit Sub Type" required>
                            </div>
                            <div class="col-md-6">
                                <label for="request_date_allotment" class="form-label fw-medium">Date Of Allotment <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="request_date_allotment" name="date_of_allotment" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_date_possession" class="form-label fw-medium">Date Of Possession <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="request_date_possession" name="date_of_possession" required>
                            </div>
                            <div class="col-md-6">
                                <label for="request_returning_date" class="form-label fw-medium">Returning Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="request_returning_date" name="returning_date" required>
                                    <span class="input-group-text bg-body-secondary bg-opacity-25">
                                        <i class="bi bi-calendar-event text-danger"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="request_noc_document" class="form-label fw-medium">Upload NOC Document <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="request_noc_document" name="noc_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <div class="form-text">PDF, DOC, or image files</div>
                            </div>
                            <div class="col-md-6">
                                <label for="request_remarks" class="form-label fw-medium">Remarks</label>
                                <textarea class="form-control" id="request_remarks" name="remarks" rows="3" placeholder="Optional remarks"></textarea>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success px-4 rounded-pill">
                                <i class="bi bi-check-lg me-2"></i>Save
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg me-2"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title fw-semibold mb-0">Return House List</h5>
            <button type="button" class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#requestHouseModal">
                <i class="bi bi-plus-circle me-1"></i>Request House
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="returnHouseTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">S.No.</th>
                            <th class="text-nowrap">Name</th>
                            <th class="text-nowrap">Employee Type</th>
                            <th class="text-nowrap">Section</th>
                            <th class="text-nowrap">Estate Name</th>
                            <th class="text-nowrap">House No.</th>
                            <th class="text-nowrap">Unit Name</th>
                            <th class="text-nowrap">Building Name</th>
                            <th class="text-nowrap">Unit Subtype</th>
                            <th class="text-nowrap">Date of Allotment</th>
                            <th class="text-nowrap">Date of Possession</th>
                            <th class="text-nowrap">Returning Date</th>
                            <th class="text-nowrap">Upload Document</th>
                            <th class="text-nowrap">Remarks</th>
                            <th class="text-nowrap text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Bhumeshwari devi</td>
                            <td>LBSNAA</td>
                            <td>Estate</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-danger" title="Return House">
                                        <i class="bi bi-house-door"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
<script>
(function() {
    var unitTypesByCampus = @json($unitTypesByCampus ?? []);
    var urlBlocks = '{{ route("admin.estate.possession.blocks") }}';
    var urlUnitSubTypes = '{{ route("admin.estate.possession.unit-sub-types") }}';
    var urlHouses = '{{ route("admin.estate.possession.houses") }}';

    var urlEmployees = '{{ route("admin.estate.return-house.employees") }}';
    var urlRequestDetails = '{{ route("admin.estate.return-house.request-details") }}';
    var campusesList = @json($campuses ?? []);

    $(document).ready(function() {
        // --- Employee Type change: load employee list (LBSNAA / Other), never hide Employee Name ---
        $('input[name="employee_type"]').on('change', function() {
            var type = $(this).val();
            var isOther = (type === 'Other Employee');
            $('#request_employee_name').attr('name', isOther ? 'estate_other_req_pk' : 'employee_select_id');
            $('#request_employee_name').html('<option value="">--Loading--</option>');
            $.get(urlEmployees, { employee_type: type }, function(res) {
                var $sel = $('#request_employee_name');
                $sel.html('<option value="">--Select--</option>');
                if (res.status && res.data && res.data.length) {
                    res.data.forEach(function(o) {
                        var section = (o.section !== undefined) ? (o.section || '') : '';
                        $sel.append('<option value="' + o.id + '" data-section="' + section + '">' + (o.name || '') + (o.request_no ? ' (' + o.request_no + ')' : '') + '</option>');
                    });
                }
                $('#request_section_name').val('');
                clearRequestDetailsFields();
            });
        });

        // --- Employee Name change: fetch full mapping and fill all fields (live behaviour) ---
        $('#request_employee_name').on('change', function() {
            var id = $(this).val();
            var type = $('input[name="employee_type"]:checked').val();
            if (!id || !type) {
                $('#request_section_name').val('');
                clearRequestDetailsFields();
                return;
            }
            $.get(urlRequestDetails, { employee_type: type, id: id }, function(res) {
                if (!res.status || !res.data) {
                    $('#request_section_name').val('');
                    clearRequestDetailsFields();
                    return;
                }
                var d = res.data;
                $('#request_section_name').val(d.section || '');
                $('#request_date_allotment').val(d.allotment_date || '');
                $('#request_date_possession').val(d.possession_date_oth || '');
                if (!d.estate_campus_master_pk) {
                    $('#request_estate_name').val('');
                    $('#request_unit_name').html('<option value="">--Select Estate first--</option>');
                    $('#request_building_name, #request_unit_sub_type, #request_house_no').html('<option value="">--Select--</option>');
                    $('#request_house_no_display').val('');
                    return;
                }
                var campusPk = String(d.estate_campus_master_pk);
                var unitPk = d.estate_unit_type_master_pk ? String(d.estate_unit_type_master_pk) : '';
                var $estate = $('#request_estate_name');
                $estate.val(campusPk);
                $('#request_estate_name option[value="' + campusPk + '"]').prop('selected', true);
                var types = unitTypesByCampus[campusPk] || unitTypesByCampus[d.estate_campus_master_pk] || [];
                var $unit = $('#request_unit_name');
                $unit.html('<option value="">--Select--</option>');
                types.forEach(function(t) {
                    var v = String(t.pk);
                    $unit.append('<option value="' + v + '">' + (t.unit_type || '') + '</option>');
                });
                if (unitPk && d.unit_type_name && !$unit.find('option[value="' + unitPk + '"]').length) {
                    $unit.append('<option value="' + unitPk + '">' + (d.unit_type_name || '') + '</option>');
                }
                if (unitPk) {
                    $unit.val(unitPk);
                    $('#request_unit_name option[value="' + unitPk + '"]').prop('selected', true);
                }
                var campusId = d.estate_campus_master_pk;
                var unitTypeId = d.estate_unit_type_master_pk;
                var blockId = d.estate_block_master_pk;
                var unitSubTypeId = d.estate_unit_sub_type_master_pk;
                $.get(urlBlocks, { campus_id: campusId, unit_type_id: unitTypeId }, function(resB) {
                    var $blk = $('#request_building_name');
                    $blk.html('<option value="">--Select--</option>');
                    if (resB.status && resB.data) resB.data.forEach(function(b) {
                        $blk.append('<option value="' + String(b.pk) + '">' + (b.block_name || '') + '</option>');
                    });
                    if (blockId) $blk.val(String(blockId));
                    $.get(urlUnitSubTypes, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId }, function(resU) {
                        var $ust = $('#request_unit_sub_type');
                        $ust.html('<option value="">--Select--</option>');
                        if (resU.status && resU.data) resU.data.forEach(function(u) {
                            $ust.append('<option value="' + String(u.pk) + '">' + (u.unit_sub_type || '') + '</option>');
                        });
                        if (unitSubTypeId) $ust.val(String(unitSubTypeId));
                        $.get(urlHouses, { campus_id: campusId, block_id: blockId, unit_sub_type_id: unitSubTypeId, unit_type_id: unitTypeId }, function(resH) {
                            var $h = $('#request_house_no');
                            $h.html('<option value="">--Select--</option>');
                            if (resH.status && resH.data) resH.data.forEach(function(h) {
                                $h.append('<option value="' + String(h.pk) + '" data-house-no="' + (h.house_no || '') + '">' + (h.house_no || h.pk) + '</option>');
                            });
                            if (d.estate_house_master_pk) {
                                $h.val(String(d.estate_house_master_pk));
                                $('#request_house_no_display').val(d.house_no || '');
                            }
                        });
                    });
                });
            });
        });

        function clearRequestDetailsFields() {
            $('#request_estate_name').val('');
            $('#request_unit_name').html('<option value="">--Select Estate first--</option>');
            $('#request_building_name, #request_unit_sub_type, #request_house_no').html('<option value="">--Select--</option>');
            $('#request_house_no_display').val('');
            $('#request_date_allotment, #request_date_possession').val('');
        }

        // On load: Other is default, so select name is estate_other_req_pk
        $('#request_employee_name').attr('name', 'estate_other_req_pk');

        $('#request_estate_name').on('change', function() {
            var campusPk = $(this).val();
            var $unit = $('#request_unit_name');
            $unit.html('<option value="">--Select--</option>');
            $('#request_building_name, #request_unit_sub_type, #request_house_no').html('<option value="">--Select--</option>');
            $('#request_house_no_display').val('');
            if (!campusPk) return;
            var types = unitTypesByCampus[campusPk] || [];
            types.forEach(function(t) {
                $unit.append('<option value="' + t.pk + '">' + t.unit_type + '</option>');
            });
        });

        $('#request_unit_name').on('change', function() {
            var campusId = $('#request_estate_name').val();
            var unitTypeId = $(this).val();
            if (!campusId) return;
            $('#request_building_name').html('<option value="">--Select--</option>');
            $('#request_unit_sub_type, #request_house_no').html('<option value="">--Select--</option>');
            if (!unitTypeId) return;
            $.get(urlBlocks, { campus_id: campusId, unit_type_id: unitTypeId }, function(res) {
                if (res.status && res.data) res.data.forEach(function(b) {
                    $('#request_building_name').append('<option value="' + b.pk + '">' + b.block_name + '</option>');
                });
            });
        });

        $('#request_building_name').on('change', function() {
            var campusId = $('#request_estate_name').val();
            var blockId = $(this).val();
            var unitTypeId = $('#request_unit_name').val();
            if (!campusId || !blockId) return;
            $('#request_unit_sub_type').html('<option value="">--Select--</option>');
            $('#request_house_no').html('<option value="">--Select--</option>');
            $.get(urlUnitSubTypes, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId }, function(res) {
                if (res.status && res.data) res.data.forEach(function(u) {
                    $('#request_unit_sub_type').append('<option value="' + u.pk + '">' + u.unit_sub_type + '</option>');
                });
            });
        });

        $('#request_unit_sub_type').on('change', function() {
            var campusId = $('#request_estate_name').val();
            var blockId = $('#request_building_name').val();
            var unitSubTypeId = $(this).val();
            var unitTypeId = $('#request_unit_name').val();
            if (!campusId || !blockId || !unitSubTypeId) return;
            $('#request_house_no').html('<option value="">--Select--</option>');
            $.get(urlHouses, { campus_id: campusId, block_id: blockId, unit_sub_type_id: unitSubTypeId, unit_type_id: unitTypeId }, function(res) {
                if (res.status && res.data) res.data.forEach(function(h) {
                    $('#request_house_no').append('<option value="' + h.pk + '" data-house-no="' + (h.house_no || '') + '">' + (h.house_no || h.pk) + '</option>');
                });
            });
        });

        $('#request_house_no').on('change', function() {
            var no = $(this).find('option:selected').data('house-no');
            $('#request_house_no_display').val(no || $(this).find('option:selected').text());
        });

        $('#requestHouseForm').on('submit', function(e) {
            if ($('input[name="employee_type"]:checked').val() === 'LBSNAA') {
                e.preventDefault();
                alert('For LBSNAA employees please use Request for Estate.');
                return;
            }
            if (this.checkValidity()) {
                var h = $('#request_house_no').find('option:selected').data('house-no');
                $('#request_house_no_display').val(h || $('#request_house_no').find('option:selected').text());
            }
        });

        // --- Return House action ---
        var returnHouseUrl = null;
        $(document).on('click', '.btn-return-house', function() {
            returnHouseUrl = $(this).data('url');
            $('#confirmReturnHouseModal').modal('show');
        });

        $('#confirmReturnHouseBtn').on('click', function() {
            if (!returnHouseUrl) return;
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: returnHouseUrl,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    $('#confirmReturnHouseModal').modal('hide');
                    if (res.success) {
                        $('#returnHouseTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + (res.message || 'House marked as returned.') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#return-house-alerts').html(alertHtml);
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><span class="flex-grow-1">' + msg + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#return-house-alerts').html(alertHtml);
                },
                complete: function() { $btn.prop('disabled', false); }
            });
            returnHouseUrl = null;
        });
    });

    // Bootstrap form validation for Request House modal
    var form = document.getElementById('requestHouseForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }
});
</script>
@endpush
