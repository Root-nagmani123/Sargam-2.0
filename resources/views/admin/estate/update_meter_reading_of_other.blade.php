@extends('admin.layouts.master')

@section('title', 'Update Meter Reading of Other - Sargam')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Update Meter Reading of Other"></x-breadcrum>
<x-session_message />

    <div class="card">
        <div class="card-body p-4 p-lg-5">
<h4 class="h5 mb-4">Please Update Meter Reading</h4>
<hr class="my-2">
            <form id="meterReadingFilterForm">
                @csrf
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
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name">
                            <option value="">Select</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
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
                        <label for="building" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="building" name="building">
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="unit_sub_type" name="unit_sub_type" value="9356753250" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
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
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="loadMeterReadingsBtn">
                            <i class="bi bi-search me-2"></i>Load Data
                        </button>
                    </div>
                </div>
            </form>

            <form id="meterReadingSaveForm" method="POST" action="{{ route('admin.estate.update-meter-reading-of-other.store') }}" style="display:none;">
                @csrf
                <div class="table-responsive mt-4">
                    <table class="table" id="updateMeterReadingOtherTable">
                        <thead>
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

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-secondary">Cancel</a>
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
