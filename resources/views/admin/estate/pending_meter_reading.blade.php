@extends('admin.layouts.master')

@section('title', 'Pending Meter Reading - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.reports.pending-meter-reading') }}">Estate Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pending Meter Reading</li>
        </ol>
    </nav>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Pending Meter Reading</h2>
        <div>
            <a href="{{ route('admin.estate.reports.house-status') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-house me-2"></i>House Status
            </a>
            <a href="{{ route('admin.estate.reports.bill-report-grid') }}" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-text me-2"></i>Bill Reports
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="bill_month" class="form-label">Select Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="bill_month" name="bill_month" value="December 2025" required>
                        <span class="input-group-text">
                            <i class="bi bi-calendar"></i>
                        </span>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Select Bill Month
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="pendingMeterReadingTable">
                    <thead class="table-primary">
                        <tr>
                            <th>S.No.</th>
                            <th>Employee Type</th>
                            <th>Name</th>
                            <th>House No.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>LBSNAA</td>
                            <td>Naresh Kumar Gupta</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>LBSNAA</td>
                            <td>Naresh Kumar Gupta</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>LBSNAA</td>
                            <td>KB Singha</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>LBSNAA</td>
                            <td>Naresh Kumar Gupta</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>LBSNAA</td>
                            <td>KB Singha</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>LBSNAA</td>
                            <td>Naresh Kumar Gupta</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>LBSNAA</td>
                            <td>KB Singha</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>LBSNAA</td>
                            <td>Naresh Kumar Gupta</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td>LBSNAA</td>
                            <td>KB Singha</td>
                            <td>KT-03</td>
                        </tr>
                        <tr>
                            <td>10</td>
                            <td>LBSNAA</td>
                            <td>Naresh Kumar Gupta</td>
                            <td>KT-03</td>
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
    $('#pendingMeterReadingTable').DataTable({
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
});
</script>
@endpush
