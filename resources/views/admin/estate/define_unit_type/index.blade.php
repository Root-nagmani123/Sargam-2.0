@extends('admin.layouts.master')

@section('title', 'Define Unit Type - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Define Unit Type" />

    <x-session_message />

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Unit Type</h1>
                    <p class="text-muted small mb-0 opacity-75">Manage unit types in the system. Add, edit, or view unit type records.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-unit-type.create') }}" class="btn btn-primary rounded-1 px-3 d-inline-flex align-items-center gap-2 unit-type-add-btn">
                        <i class="material-icons material-symbols-rounded">add</i> Add New
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 text-nowrap" id="unitTypeTable">
                    <thead>
                        <tr>
                            <th class="border-0 ps-3 fw-semibold text-secondary">S.No.</th>
                            <th class="border-0 fw-semibold text-secondary">Unit Type</th>
                            <th class="border-0 pe-3 fw-semibold text-secondary text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#unitTypeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.estate.define-unit-type.index') }}"
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '80px', className: 'ps-3' },
            { data: 'unit_type', name: 'unit_type', className: 'fw-medium' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'pe-3 text-end' }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: "Loading data…",
            emptyTable: "No unit type found.",
            zeroRecords: "No matching unit type found.",
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
        dom: '<\"row\"<\"col-sm-12 col-md-6\"l><\"col-sm-12 col-md-6\"f>>rt<\"row\"<\"col-sm-12 col-md-5\"i><\"col-sm-12 col-md-7\"p>>'
    });

    // Move "Add New" button next to the search box and align right
    var $wrapper = $('#unitTypeTable').closest('.dataTables_wrapper');
    var $filter = $wrapper.find('.dataTables_filter');
    var $addBtn = $('.unit-type-add-btn').detach().addClass('ms-2');
    if ($filter.length && $addBtn.length) {
        $filter.append($addBtn);
        $filter.addClass('d-flex align-items-center justify-content-end gap-2');
    }
});
</script>
@endpush
