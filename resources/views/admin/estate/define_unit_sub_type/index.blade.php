@extends('admin.layouts.master')

@section('title', 'Define Unit Sub Type - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
<x-breadcrum title="Define Unit Sub Type" />

    <x-session_message />

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Unit Sub Type</h1>
                    <p class="text-muted small mb-0">This page displays all the unit sub type added in the system and provides options such as add, edit, delete, etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-unit-sub-type.create') }}" class="btn btn-primary rounded-1 px-3 d-inline-flex align-items-center gap-2 unit-sub-type-add-btn"><i class="material-icons material-symbols-rounded">add</i> Add New</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 text-nowrap w-100" id="unitSubTypeTable">
                    <thead>
                        <tr>
                            <th class="border-0 ps-3 fw-semibold text-secondary">S.No.</th>
                            <th class="border-0 fw-semibold text-secondary">Unit Sub Type</th>
                            <th class="border-0 pe-3 fw-semibold text-secondary text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#unitSubTypeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.estate.define-unit-sub-type.index') }}"
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '80px', className: 'ps-3' },
            { data: 'unit_sub_type', name: 'unit_sub_type', className: 'fw-medium' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'pe-3 text-end' }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: "Loading data…",
            emptyTable: "No unit sub type found.",
            zeroRecords: "No matching unit sub type found.",
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
        },
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Move "Add New" button next to the search box and align right
    var $wrapper = $('#unitSubTypeTable').closest('.dataTables_wrapper');
    var $filter = $wrapper.find('.dataTables_filter');
    var $addBtn = $('.unit-sub-type-add-btn').detach().addClass('ms-2');
    if ($filter.length && $addBtn.length) {
        $filter.append($addBtn);
        $filter.addClass('d-flex align-items-center justify-content-end gap-2');
    }
});
</script>
@endpush
