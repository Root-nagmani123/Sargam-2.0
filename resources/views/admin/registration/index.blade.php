@extends('admin.layouts.master')

@section('title', 'Forms - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card mt-3" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-4">
                    <h4>Registration</h4>
                </div>
                <div class="col-12 col-md-8 text-end d-flex gap-2 justify-content-end align-items-center">

                    <!-- Add Form -->
                    <a href="{{ route('forms.create') }}"
                        class="btn btn-primary px-4 py-2 fw-semibold shadow-sm rounded-pill">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">add</i> Add Form
                    </a>

                    <!-- Use Template Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-info px-4 py-2 fw-semibold shadow-sm rounded-pill dropdown-toggle"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="Use Template Options">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 20px; vertical-align: middle;">file_present</i> Use Template
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3"
                            style="min-width: 240px;">
                            @foreach ($forms_parent as $form)
                            <li>
                                <a class="dropdown-item py-2"
                                    href="{{ route('forms.template.create', ['template' => $form->id]) }}">
                                    <i class="bi bi-folder-check me-2 text-primary"></i>
                                    {{ $form->name }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Archived Courses -->
                    <a href="{{ route('forms.inactive') }}"
                        class="btn btn-secondary px-4 py-2 fw-semibold shadow-sm rounded-pill">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">archive</i> Archived Courses
                    </a>
                    <!-- Search Box + Icon -->
                    <!-- Search Expand -->
                    <div class="search-expand d-flex align-items-center">
                        <a href="javascript:void(0)" id="searchToggle">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 20px; vertical-align: middle;">search</i>
                        </a>

                        <input type="text" class="form-control search-input ms-2" id="searchInput" placeholder="Search…"
                            aria-label="Search">
                    </div>

                </div>


            </div>
            <hr>
            <div class="table-responsive">
                <table class="table w-100 text-nowrap">
                    <thead>
                        <tr>
                            <th></th> {{-- Chevron column --}}
                            <th>S.No.</th>
                            <th>Form ID</th>
                            <th>Course Name</th>
                            <th>Form Name</th>
                            <th>Submissions List</th>
                            <th>Pending Submissions</th>
                            <th>Edit Form Fields</th>
                            <th>Actions</th>
                            <th>Status</th>
                            <th>Position</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $serial = $forms->firstItem() ?? 1; @endphp

                        @foreach ($groupedForms[null] ?? [] as $parent)
                        {{-- Parent Row --}}
                        <tr class="parent-row" data-id="{{ $parent->id }}" style="cursor:pointer;">
                            <td>
                                @if (isset($groupedForms[$parent->id]) && count($groupedForms[$parent->id]) > 0)
                                <i class="fas fa-chevron-right toggle-child"></i>
                                @endif
                            </td>
                            <td>{{ $serial++ }}</td>
                            <td>{{ $parent->id }}</td>
                            <td><strong>{{ $parent->name }}</strong></td>
                            <td>{{ $parent->description }}</td>
                            <td>
                                <a href="{{ route('forms.courseList', $parent->id) }}"
                                    class="btn btn-sm btn-success">View</a>
                            </td>
                            <td>
                                <a href="{{ route('forms.show', $parent->id) }}" class="btn btn-sm btn-info">Preview</a>
                            </td>
                            <td>
                                <a href="{{ route('forms.fc_edit', $parent->id) }}" class="btn btn-sm btn-warning">Edit
                                    Fields</a>
                            </td>
                            <td>
                                <a href="{{ route('forms.edit', $parent->id) }}" class="btn btn-sm btn-danger">Edit</a>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-visible-switch" type="checkbox"
                                        data-id="{{ $parent->id }}" {{ $parent->visible ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td></td>
                        </tr>

                        {{-- Child Rows --}}
                        @if (isset($groupedForms[$parent->id]))
                        @foreach ($groupedForms[$parent->id] as $index => $child)
                        <tr class="child-row bg-light" data-parent="{{ $parent->id }}" style="display:none;">
                            <td></td>
                            <td>{{ $serial++ }}</td>
                            <td>{{ $child->id }}</td>
                            <td></td>
                            <td>{{ $child->description }}</td>
                            <td>
                                <a href="{{ route('forms.courseList', $child->id) }}"
                                    class="btn btn-sm btn-success">View</a>
                            </td>
                            <td>
                                <a href="{{ route('forms.show', $child->id) }}" class="btn btn-sm btn-info">Preview</a>
                            </td>
                            <td>
                                <a href="{{ route('forms.fc_edit', $child->id) }}" class="btn btn-sm btn-warning">Edit
                                    Fields</a>
                            </td>
                            <td>
                                <a href="{{ route('forms.edit', $child->id) }}" class="btn btn-sm btn-danger">Edit</a>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-visible-switch" type="checkbox"
                                        data-id="{{ $child->id }}" {{ $child->visible ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">
                                @if ($index > 0)
                                <form action="{{ route('forms.moveup', $child->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Move Up">↑</button>
                                </form>
                                @endif
                                @if ($index < count($groupedForms[$parent->id]) - 1)
                                    <form action="{{ route('forms.movedown', $child->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary"
                                            title="Move Down">↓</button>
                                    </form>
                                    @endif
                            </td>
                        </tr>
                        @endforeach
                        @endif
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                            <div class="text-muted small mb-2">
                                Showing {{ $forms->firstItem() }}
                                to {{ $forms->lastItem() }}
                                of {{ $forms->total() }} items
                            </div>

                            <div>
                                {{ $forms->links('vendor.pagination.custom') }}
                            </div>

                        </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
$(document).ready(function() {
    // Destroy existing DataTable if already initialized
    if ($.fn.DataTable.isDataTable('#zero_config')) {
        $('#zero_config').DataTable().destroy();
    }
    // Initialize DataTable
    var table = $('#zero_config').DataTable({
        "order": [],
        "columnDefs": [{
            "orderable": false,
            "targets": [0, 5, 6, 7, 8, 9, 10]
        }]
    });

    // Expand/Collapse child rows
    $('#zero_config tbody').on('click', '.toggle-child', function(e) {
        e.stopPropagation();
        var icon = $(this);
        var tr = icon.closest('tr');
        var parentId = tr.data('id');

        table.rows().every(function(rowIdx, tableLoop, rowLoop) {
            var row = this.node();
            if ($(row).hasClass('child-row') && $(row).data('parent') == parentId) {
                $(row).toggle();
            }
        });

        icon.toggleClass('fa-chevron-right fa-chevron-down');
    });

    // Toggle visibility
    // $('.toggle-visible-switch').on('change', function() {
    //     const id = $(this).data('id');
    //     fetch(`/registration/forms/${id}/toggle-visible`, {
    //             method: 'POST',
    //             headers: {
    //                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
    //                 'Content-Type': 'application/json'
    //             },
    //             body: JSON.stringify({})
    //         })
    //         .then(res => res.json())
    //         .then(data => {
    //             if (data.success) {
    //                 alert('Visibility updated successfully.');
    //                 location.reload();
    //             } else {
    //                 alert('Failed to update visibility.');
    //             }
    //         })
    //         .catch(err => {
    //             console.error(err);
    //             alert('An error occurred while updating visibility.');
    //         });
    // });
    $(document).on('change', '.toggle-visible-switch', function() {
        const id = $(this).data('id');

        fetch(`/registration/forms/${id}/toggle-visible`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Visibility updated successfully.');
                    location.reload(); //  Refresh the page to reflect change
                } else {
                    alert('Failed to update visibility.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while updating visibility.');
            });
    });

});
</script>
@endsection