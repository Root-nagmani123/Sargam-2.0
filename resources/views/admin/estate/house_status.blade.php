@extends('admin.layouts.master')

@section('title', 'House Status - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.reports.pending-meter-reading') }}">Estate Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page">House Status</li>
        </ol>
    </nav>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">House Status</h2>
        <div>
            <a href="{{ route('admin.estate.reports.pending-meter-reading') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-speedometer2 me-2"></i>Pending Meter Reading
            </a>
            <a href="{{ route('admin.estate.reports.bill-report-grid') }}" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-text me-2"></i>Bill Reports
            </a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="houseStatusTable">
                    <thead class="table-primary">
                        <tr>
                            <th>Types</th>
                            <th>Grade Pay</th>
                            <th>House Available</th>
                            <th>House Und</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Laundromat</td>
                            <td></td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Office Building</td>
                            <td></td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Out House</td>
                            <td></td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Shops</td>
                            <td></td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Studio Apartment</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Type-I</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Type-II</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Type-III</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Type-IV</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Type-V</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Type-VI</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Type-VII</td>
                            <td>72,12,14,0,09,11,13A</td>
                            <td>3</td>
                            <td></td>
                        </tr>
                        <tr class="table-secondary fw-bold">
                            <td>Total</td>
                            <td></td>
                            <td>512</td>
                            <td></td>
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
    $('#houseStatusTable').DataTable({
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
