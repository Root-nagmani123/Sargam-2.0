@extends('admin.layouts.master')

@section('title', 'Update Meter No. - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
<x-breadcrum title="Update Meter No."></x-breadcrum>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h5 mb-0">Update Meter No.</h2>
                <div>
                    <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-outline-primary text-decoration-none">Update Reading & Meter No.</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="updateMeterNoTable">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Employee Type</th>
                            <th scope="col">Unit Type</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Ravindra Kumar Prajapati</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Chanda Kiran</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Kishan Chandra Joshi</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Ravindra Kumar Prajapati</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>Chanda Kiran</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>Kishan Chandra Joshi</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>Ravindra Kumar Prajapati</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>Chanda Kiran</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td>Kishan Chandra Joshi</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>10</td>
                            <td>Ravindra Kumar Prajapati</td>
                            <td>LBSNAA</td>
                            <td>Residential</td>
                            <td>
                                <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-sm btn-primary" title="Update Meter">
                                    <i class="bi bi-pencil"></i>
                                </a>
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
    $('#updateMeterNoTable').DataTable({
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
