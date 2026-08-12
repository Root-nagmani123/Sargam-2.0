@extends('admin.layouts.master')

@section('title', 'Define Estate/Campus - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
 <x-breadcrum title="Define Estate/Campus" />

    <x-session_message />

    <div class="card shadow-sm border-0" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Estate/Campus</h1>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-campus.create') }}" class="btn btn-primary rounded-1 px-3 d-inline-flex align-items-center gap-2 campus-add-btn">
                        <i class="material-icons material-symbols-rounded">add</i> Add New
                    </a>
                </div>
            </div>


            <div class="table-responsive">
                <table class="table text-nowrap w-100" id="campusTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Estate/Campus</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->campus_name }}</td>
                            <td>{{ $row->description ?? '--' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.estate.define-campus.edit', $row->pk) }}" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                                    <form action="{{ route('admin.estate.define-campus.destroy', $row->pk) }}" method="POST" onsubmit="return confirm('Delete this Estate/Campus?')">
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
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No campus found.</td>
                        </tr>
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
    $('#campusTable').DataTable({
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    if (type === 'display') {
                        var start = meta.settings._iDisplayStart || 0;
                        return start + meta.row + 1;
                    }
                    return data;
                }
            }
        ],
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

    // Move "Add New" button next to the search box and align right.
    // NOTE: public/js/datatable-global-ui.js moves .dataTables_filter OUT of .dataTables_wrapper
    // into its own toolbar (stamping it data-sargam-dt-filter="<tableId>"), so search the stamp
    // first and the wrapper second. Never detach() before a target is found — that is how this
    // button used to disappear completely.
    var $addBtn = $('.campus-add-btn');
    var $filter = $('[data-sargam-dt-filter="campusTable"]').first();
    if (!$filter.length) {
        $filter = $('#campusTable').closest('.dataTables_wrapper').find('.dataTables_filter').first();
    }
    if ($filter.length && $addBtn.length) {
        $filter.append($addBtn.addClass('ms-2'));
        $filter.addClass('d-flex align-items-center justify-content-end gap-2');
    }
});
</script>
@endpush
