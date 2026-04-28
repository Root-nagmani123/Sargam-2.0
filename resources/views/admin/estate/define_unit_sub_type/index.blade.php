@extends('admin.layouts.master')

@section('title', 'Define Unit Sub Type - Sargam')

@section('content')
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
                    <tbody>
                        @forelse($items as $index => $row)
                        <tr>
                            <td class="ps-3">{{ $index + 1 }}</td>
                            <td class="fw-medium">{{ $row->unit_sub_type }}</td>
                            <td class="pe-3 text-end">
                                <a href="{{ route('admin.estate.define-unit-sub-type.edit', $row->pk) }}" class="text-primary" title="Edit">
                                    <i class="material-icons material-symbols-rounded">edit</i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No unit sub type found. <a href="{{ route('admin.estate.define-unit-sub-type.create') }}">Add one</a>.</td></tr>
                        @endforelse
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
    $('#unitSubTypeTable').DataTable({
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, width: '80px', render: function(data, type, row, meta) { return type === 'display' ? (meta.settings._iDisplayStart || 0) + meta.row + 1 : data; } },
            { targets: 2, orderable: false, searchable: false }
        ],
        language: {
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
