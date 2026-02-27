@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* DataTable Enhanced Styling */
.dataTables_wrapper {
    font-family: inherit;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 6px 12px;
    margin-left: 8px;
    transition: all 0.3s ease;
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1);
    outline: none;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 4px 8px;
    margin: 0 8px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 6px;
    padding: 6px 12px;
    margin: 0 2px;
    border: 1px solid #dee2e6;
    background: white;
    transition: all 0.3s ease;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #004a93;
    color: white !important;
    border-color: #004a93;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #004a93;
    color: white !important;
    border-color: #004a93;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.dataTables_wrapper .dataTables_info {
    padding-top: 8px;
    font-size: 0.9rem;
    color: #6c757d;
}

/* Table styling */
#subjectTable thead th {
    background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 12px;
    cursor: pointer;
    position: relative;
}

#subjectTable thead th:hover {
    background: linear-gradient(135deg, #003d7a 0%, #0052a3 100%);
}

#subjectTable thead th.sorting:after,
#subjectTable thead th.sorting_asc:after,
#subjectTable thead th.sorting_desc:after {
    font-family: 'Material Icons', 'Material Symbols Rounded';
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.7;
}

#subjectTable thead th.sorting:after {
    content: 'unfold_more';
}

#subjectTable thead th.sorting_asc:after {
    content: 'arrow_upward';
    opacity: 1;
}

#subjectTable thead th.sorting_desc:after {
    content: 'arrow_downward';
    opacity: 1;
}

#subjectTable tbody tr {
    transition: all 0.2s ease;
}

#subjectTable tbody tr:hover {
    background-color: rgba(0, 74, 147, 0.05);
    transform: translateX(2px);
}

#subjectTable tbody td {
    padding: 12px;
    vertical-align: middle;
    border-color: #e9ecef;
}

/* Search highlight */
mark {
    background-color: #fff3cd;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}

/* Responsive table */
@media (max-width: 768px) {
    .dataTables_wrapper .dataTables_filter input {
        width: 100% !important;
        margin-top: 8px;
    }

    .dataTables_wrapper .dataTables_length {
        margin-bottom: 12px;
    }
}

/* Loading state */
.dataTables_processing {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Custom scrollbar for table */
.dataTables_wrapper .dataTables_scroll {
    scrollbar-width: thin;
    scrollbar-color: #004a93 #f1f1f1;
}

.dataTables_wrapper .dataTables_scroll::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.dataTables_wrapper .dataTables_scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.dataTables_wrapper .dataTables_scroll::-webkit-scrollbar-thumb {
    background: #004a93;
    border-radius: 4px;
}

.dataTables_wrapper .dataTables_scroll::-webkit-scrollbar-thumb:hover {
    background: #003d7a;
}
</style>

<div class="container-fluid">
    <x-breadcrum title="Subject"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="fw-semibold text-dark mb-0">Subject</h4>
                <div class="d-flex align-items-center gap-2">
                    <!-- Add New Button -->
                    <a href="{{ route('subject.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">add</i>
                        Add Subject
                    </a>
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table" id="subjectTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Major Subject Name</th>
                            <th>Short Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $key => $subject)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $subject->subject_name }}</td>
                            <td>{{ $subject->sub_short_name }}</td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="subject_master" data-column="active_inactive"
                                        data-id="{{ $subject->pk }}"
                                        {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons material-symbols-rounded"
                                            style="font-size: 22px;">more_horiz</i>
                                    </a>

                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                                        {{-- Edit --}}
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                href="{{ route('subject.edit', $subject->pk) }}">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size: 20px;">edit</i>
                                                Edit
                                            </a>
                                        </li>

                                        {{-- Delete --}}
                                        @if ($subject->active_inactive == 1)
                                        <li>
                                            <span class="dropdown-item text-muted d-flex align-items-center gap-2"
                                                style="cursor: not-allowed;">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size: 20px;">delete</i>
                                                Cannot delete (active)
                                            </span>
                                        </li>
                                        @else
                                        <li>
                                            <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="dropdown-item text-danger d-flex align-items-center gap-2"
                                                    type="submit">
                                                    <i class="material-icons material-symbols-rounded"
                                                        style="font-size: 20px;">delete</i>
                                                    Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endif

                                    </ul>
                                </div>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

        </div>
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";

// Initialize DataTable with Enhanced Features
$(document).ready(function() {
    const table = $('#subjectTable').DataTable({
        // Enable/disable features
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "stateSave": true, // Remember user's selections

        // Pagination settings
        "pageLength": 10,
        "lengthMenu": [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],

        // Column definitions
        "columnDefs": [{
                "targets": 0, // S.No column
                "orderable": true,
                "searchable": false,
                "className": "text-center"
            },
            {
                "targets": 1, // Subject Name
                "orderable": true,
                "searchable": true
            },
            {
                "targets": 2, // Short Name
                "orderable": true,
                "searchable": true
            },
            {
                "targets": 3, // Status column
                "orderable": true,
                "searchable": false,
                "className": "text-center"
            },
            {
                "targets": 4, // Action column
                "orderable": false,
                "searchable": false,
                "className": "text-center"
            }
        ],

        // Default sorting - Sort by Subject Name ascending
        "order": [
            [1, 'asc']
        ],

        // Language customization
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search subjects...",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ subjects",
            "infoEmpty": "No subjects available",
            "infoFiltered": "(filtered from _MAX_ total subjects)",
            "paginate": {
                "first": '<i class="material-icons material-symbols-rounded">first_page</i>',
                "last": '<i class="material-icons material-symbols-rounded">last_page</i>',
                "next": '<i class="material-icons material-symbols-rounded">chevron_right</i>',
                "previous": '<i class="material-icons material-symbols-rounded">chevron_left</i>'
            },
            "zeroRecords": "No matching subjects found",
            "emptyTable": "No subjects available in the system",
            "loadingRecords": "Loading subjects...",
            "processing": "Processing..."
        },

        // DOM positioning with Bootstrap styling
        "dom": '<"row mb-3"<"col-sm-12 col-md-6 d-flex align-items-center"l><"col-sm-12 col-md-6"f>>' +
            '<"row"<"col-sm-12"tr>>' +
            '<"row mt-3"<"col-sm-12 col-md-5 d-flex align-items-center"i><"col-sm-12 col-md-7"p>>',

        // Styling options
        "drawCallback": function(settings) {
            // Add custom styling to pagination
            $('.dataTables_paginate .pagination').addClass('pagination-sm');

            // Highlight search terms
            if (this.api().search()) {
                const searchTerm = this.api().search();
                $('tbody td').each(function() {
                    if ($(this).find('.form-check').length === 0 && $(this).find(
                            '.dropdown').length === 0) {
                        const cellText = $(this).text();
                        const regex = new RegExp('(' + searchTerm + ')', 'gi');
                        const newText = cellText.replace(regex, '<mark>$1</mark>');
                        if (cellText !== newText) {
                            $(this).html(newText);
                        }
                    }
                });
            }
        },

        // Initialize extensions
        "initComplete": function(settings, json) {
            console.log('DataTable initialized successfully');

            // Add custom search input styling
            $('.dataTables_filter input').addClass('form-control form-control-sm').css({
                'width': '250px',
                'display': 'inline-block'
            });

            // Add custom length menu styling
            $('.dataTables_length select').addClass('form-select form-select-sm').css({
                'width': 'auto',
                'display': 'inline-block'
            });

            // Add column search for specific columns (optional enhancement)
            this.api().columns([1, 2]).every(function() {
                const column = this;
                const header = $(column.header());

                // You can add individual column filters here if needed
            });
        }
    });

    // Custom search with debounce for better performance
    let searchTimer;
    $('.dataTables_filter input').off('keyup.DT input.DT').on('keyup', function() {
        clearTimeout(searchTimer);
        const searchValue = this.value;
        searchTimer = setTimeout(function() {
            table.search(searchValue).draw();
        }, 300);
    });

    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+F or Cmd+F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('.dataTables_filter input').focus();
        }
    });

    // Export button functionality (if needed in future)
    $('#exportBtn').on('click', function() {
        // Export logic here
        alert('Export functionality can be added here');
    });

    // Refresh table button functionality (if needed)
    $('#refreshBtn').on('click', function() {
        table.ajax.reload(null, false); // Reload without resetting paging
    });
});
</script>
@endsection