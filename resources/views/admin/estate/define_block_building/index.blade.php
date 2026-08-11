@extends('admin.layouts.master')

@section('title', 'Define Block/Building - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
  <x-breadcrum title="Define Block/Building" />

    <x-session_message />

    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Block/Building</h1>
                    <p class="text-muted small mb-0">This page displays all the Estate Block/Building added in the system and provides options such as add, edit, delete etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-block-building.create') }}" class="btn btn-primary rounded-1 px-3 d-inline-flex align-items-center gap-2 block-building-add-btn"><i class="material-icons material-symbols-rounded">add</i> Add New</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 text-nowrap w-100" id="blockBuildingTable">
                    <thead>
                        <tr>
                            <th class="border-0 ps-3 fw-semibold text-secondary">S.No.</th>
                            <th class="border-0 fw-semibold text-secondary">Building/Block</th>
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
    $('#blockBuildingTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.estate.define-block-building.index') }}"
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '80px', className: 'ps-3' },
            { data: 'block_name', name: 'block_name', className: 'fw-medium' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'pe-3 text-end' }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: "Loading data…",
            emptyTable: "No block/building found.",
            zeroRecords: "No matching block/building found.",
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
    // Move "Add New" button next to the search box (same as estate define-campus / define-unit-type)
    var $wrapper = $('#blockBuildingTable').closest('.dataTables_wrapper');
    var $filter = $wrapper.find('.dataTables_filter');
    var $addBtn = $('.block-building-add-btn').detach().addClass('ms-2');
    if ($filter.length && $addBtn.length) {
        $filter.append($addBtn);
        $filter.addClass('d-flex align-items-center justify-content-end gap-2');
    }
});
</script>
@endpush
