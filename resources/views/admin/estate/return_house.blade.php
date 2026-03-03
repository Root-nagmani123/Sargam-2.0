@extends('admin.layouts.master')

@section('title', 'Return House - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Return House</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Return House</h2>
        <div>
            <button class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Request House
            </button>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="returnHouseTable">
                    <thead class="table-primary">
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>Employee Type</th>
                            <th>Section</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Bhumeshwari devi</td>
                            <td>LBSNAA</td>
                            <td>Estate</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Nehpal Singh</td>
                            <td>LBSNAA</td>
                            <td>Medical Center</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Prem Singh</td>
                            <td>LBSNAA</td>
                            <td>Reprographic Unit</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Bhumeshwari devi</td>
                            <td>LBSNAA</td>
                            <td>Academy Man Can</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>Nehpal Singh</td>
                            <td>LBSNAA</td>
                            <td>Faculty</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>Prem Singh</td>
                            <td>LBSNAA</td>
                            <td>Estate</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>Bhumeshwari devi</td>
                            <td>LBSNAA</td>
                            <td>Medical Center</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>Nehpal Singh</td>
                            <td>LBSNAA</td>
                            <td>Reprographic Unit</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td>Prem Singh</td>
                            <td>LBSNAA</td>
                            <td>Academy Man Can</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>10</td>
                            <td>Bhumeshwari devi</td>
                            <td>LBSNAA</td>
                            <td>Faculty</td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Return House">
                                    <i class="bi bi-house-door"></i>
                                </button>
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
});
</script>
@endpush
