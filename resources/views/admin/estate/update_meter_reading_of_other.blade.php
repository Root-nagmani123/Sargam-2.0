@extends('admin.layouts.master')

@section('title', 'Update Meter Reading of Other - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.possession-for-others') }}">Estate Possession for Others</a></li>
            <li class="breadcrumb-item active" aria-current="page">Update Meter Reading of Other</li>
        </ol>
    </nav>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <a href="{{ route('admin.estate.possession-for-others') }}" class="text-decoration-none text-dark">
                <i class="bi bi-arrow-left me-2"></i>Update Meter Reading of Other
            </a>
        </h2>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Please Update Meter Reading</h5>
        </div>
        <div class="card-body">
            <form>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                        <select class="form-select" id="bill_month" name="bill_month" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Bill Month
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_name" class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_name" name="unit_name" required>
                            <option value="residential" selected>Residential</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="unit_sub_type" name="unit_sub_type" value="9356753250" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name" required>
                            <option value="above_himachal_avas" selected>Above Himachal Avas</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="building" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="building" name="building" required>
                            <option value="deodar_ii" selected>Deodar-II</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="meter_reading_date" class="form-label">Meter Reading Date <span class="text-danger">*</span></label>
                        <select class="form-select" id="meter_reading_date" name="meter_reading_date" required>
                            <option value="01/01/2026" selected>01/01/2026</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Meter Reading Date
                        </small>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-hover" id="updateMeterReadingOtherTable">
                        <thead class="table-primary">
                            <tr>
                                <th>
                                    <input type="checkbox" class="form-check-input" id="select_all">
                                </th>
                                <th>House No.</th>
                                <th>Name</th>
                                <th>Last Month Electric Reading Date</th>
                                <th>Meter No.</th>
                                <th>Last Month Meter Reading</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>DEO-04</td>
                                <td>Pritam S Pawar</td>
                                <td>11/03/2025</td>
                                <td>99634496</td>
                                <td>749</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-danger mb-4">
                    <small>*Required Fields: All marked fields are mandatory for registration</small>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-outline-primary">
                        Cancel
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
    $('#updateMeterReadingOtherTable').DataTable({
        order: [[1, 'asc']],
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
});
</script>
@endpush
