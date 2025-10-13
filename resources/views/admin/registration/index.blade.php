@extends('admin.layouts.master')

@section('title', 'Forms - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row">
                <div class="col-6">
                    <h4>Registration</h4>
                </div>
                {{-- <div class="col-6 text-end">
                <a href="{{ route('forms.create') }}" class="btn btn-primary">Add Form</a>
                <a href="{{ route('forms.inactive') }}" class="btn btn-secondary">Inactive Forms</a>
            </div> --}}
                <div class="col-6 text-end">
                    <a href="{{ route('forms.create') }}" class="btn btn-primary">Add Form</a>

                    <!-- Use Template Button -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Use Template
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @foreach ($forms_parent as $form)
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('forms.template.create', ['template' => $form->id]) }}">
                                        {{ $form->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <a href="{{ route('forms.inactive') }}" class="btn btn-secondary"> Archived Courses</a>
                </div>

            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="zero_config" class="table table-striped table-bordered text-nowrap align-middle">
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
                            @php $serial = 1; @endphp

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
                                        <a href="{{ route('forms.show', $parent->id) }}"
                                            class="btn btn-sm btn-info">Preview</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('forms.fc_edit', $parent->id) }}"
                                            class="btn btn-sm btn-warning">Edit Fields</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('forms.edit', $parent->id) }}"
                                            class="btn btn-sm btn-danger">Edit</a>
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
                                        <tr class="child-row bg-light" data-parent="{{ $parent->id }}"
                                            style="display:none;">
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
                                                <a href="{{ route('forms.show', $child->id) }}"
                                                    class="btn btn-sm btn-info">Preview</a>
                                            </td>
                                            <td>
                                                <a href="{{ route('forms.fc_edit', $child->id) }}"
                                                    class="btn btn-sm btn-warning">Edit Fields</a>
                                            </td>
                                            <td>
                                                <a href="{{ route('forms.edit', $child->id) }}"
                                                    class="btn btn-sm btn-danger">Edit</a>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input toggle-visible-switch" type="checkbox"
                                                        data-id="{{ $child->id }}"
                                                        {{ $child->visible ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if ($index > 0)
                                                    <form action="{{ route('forms.moveup', $child->id) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-secondary"
                                                            title="Move Up">↑</button>
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
            $('.toggle-visible-switch').on('change', function() {
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
                            location.reload();
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
