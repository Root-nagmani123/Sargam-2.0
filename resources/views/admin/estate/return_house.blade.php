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

    <!-- Request House Modal - Add Request Details -->
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
<script>
$(document).ready(function() {
    $('#returnHouseTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
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
