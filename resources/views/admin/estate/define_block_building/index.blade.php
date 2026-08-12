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
                    <tbody class="bg-white">
                        @forelse($items as $index => $row)
                        <tr>
                            <td class="ps-3">{{ $index + 1 }}</td>
                            <td class="fw-medium">{{ $row->block_name }}</td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('admin.estate.define-block-building.edit', $row->pk) }}" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                                    <form action="{{ route('admin.estate.define-block-building.destroy', $row->pk) }}" method="POST" onsubmit="return confirm('Delete this Building/Block?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                            <i class="material-icons material-symbols-rounded">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No block/building found. <a href="{{ route('admin.estate.define-block-building.create') }}">Add one</a>.</td></tr>
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
    $('#blockBuildingTable').DataTable({
        // Khaali rakho: controller pehle hi pk desc bhejta hai (naya record sabse upar).
        // Yahan koi order dene par DataTables use client-side dobara sort kar dega.
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
    // Move "Add New" button next to the search box (same as estate define-campus / define-unit-type)
    // NOTE: public/js/datatable-global-ui.js moves .dataTables_filter OUT of .dataTables_wrapper
    // into its own toolbar (stamping it data-sargam-dt-filter="<tableId>"), so search the stamp
    // first and the wrapper second. Never detach() before a target is found — that is how this
    // button used to disappear completely.
    var $addBtn = $('.block-building-add-btn');
    var $filter = $('[data-sargam-dt-filter="blockBuildingTable"]').first();
    if (!$filter.length) {
        $filter = $('#blockBuildingTable').closest('.dataTables_wrapper').find('.dataTables_filter').first();
    }
    if ($filter.length && $addBtn.length) {
        $filter.append($addBtn.addClass('ms-2'));
        $filter.addClass('d-flex align-items-center justify-content-end gap-2');
    }
});
</script>
@endpush
