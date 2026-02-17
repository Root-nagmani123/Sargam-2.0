@extends('admin.layouts.master')

@section('title', 'Update Meter Reading - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
<x-breadcrum :title="'Update Meter Reading'" :items="['Home', 'Estate Management', 'Update Meter Reading']" />  

    <!-- Page Title -->
    <div class="card shadow-sm">
        <div class="card-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title">Please Update Meter Reading</h5>
    
        </div>
        <div class="card-body p-4">
            <form>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="meter_change_month" class="form-label">Meter Change Month <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="meter_change_month" name="meter_change_month" value="January 2026" required>
                            <span class="input-group-text">
                                <i class="bi bi-calendar"></i>
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Master Change Month
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name" required>
                            <option value="administrative_officer" selected>Administrative Officer</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="building" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="building" name="building" required>
                            <option value="bhagirathi_avas" selected>Bhagirathi Avas</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building
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
                        <select class="form-select" id="unit_sub_type" name="unit_sub_type" required>
                            <option value="type_i" selected>Type-I</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    
                    
                    <div class="col-md-4">
                        <label for="master_update_date" class="form-label">Master Update Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="master_update_date" name="master_update_date" value="01-12-2025" required>
                            <span class="input-group-text">
                                <i class="bi bi-calendar"></i>
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Master Update Date
                        </small>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-hover" id="updateMeterReadingTable">
                        <thead class="table-primary">
                            <tr>
                                <th>
                                    <input type="checkbox" class="form-check-input" id="select_all">
                                </th>
                                <th>House No.</th>
                                <th>Name</th>
                                <th>Old Meter No.</th>
                                <th>Electric Meter Reading</th>
                                <th>New Meter No.</th>
                                <th>New Meter Reading</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Ashok Arya</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Virender Lal</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Ashok Arya</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Virender Lal</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Ashok Arya</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Virender Lal</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Ashok Arya</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td>BH-01</td>
                                <td>Virender Lal</td>
                                <td>102659</td>
                                <td>34132</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-danger mb-4">
                    <small>*Required Fields: All marked fields are mandatory for registration</small>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.update-meter-no') }}" class="btn btn-outline-secondary">
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
    $('#updateMeterReadingTable').DataTable({
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
