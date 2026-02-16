@extends('admin.layouts.master')

@section('title', 'Estate Request for Others - Sargam')

@section('setup_content')
<div class="container-fluid py-3">
    <x-breadcrum title="Estate Request for Others"></x-breadcrum>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-body border-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3">
                <h5 class="mb-0 fw-semibold">Estate Request for Others</h5>
                <a href="{{ route('admin.estate.add-other-estate-request') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 flex-shrink-0">
                    <i class="material-symbols-rounded">add</i>
                    <span>Add Other Estate</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0 p-md-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="estateRequestTable">
                    <thead>
                        <tr>
                            <th class="w-auto pe-2">
                                <input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all">
                            </th>
                            <th>S.No.</th>
                            <th>Request ID</th>
                            <th>Employee Name</th>
                            <th>Father's Name</th>
                            <th>Section</th>
                            <th>Date of Joining in Academy</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="pe-2">
                                <input type="checkbox" class="form-check-input" aria-label="Select row">
                            </td>
                            <td>1</td>
                            <td><span class="fw-medium">Oth-req-1</span></td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>
                                <div class="d-inline-flex gap-1">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-link text-primary p-1" title="Edit" aria-label="Edit">
                                        <i class="material-symbols-rounded">edit</i>
                                    </a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-link text-danger p-1" title="Delete" aria-label="Delete">
                                        <i class="material-symbols-rounded">delete</i>
                                    </a>
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
    $('#estateRequestTable').DataTable({
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
