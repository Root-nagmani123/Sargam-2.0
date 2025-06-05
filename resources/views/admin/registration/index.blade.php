@extends('admin.layouts.master')

@section('title', 'Forms - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row">
                <div class="col-6">
                    <h4>Registration</h4>
                </div>
                <div class="col-6 text-end">
                    <a href="{{ route('forms.create') }}" class="btn btn-primary">+ Add Form</a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-nowrap align-middle dataTable">
                        <thead>
                            <tr>
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

                            @if ($groupedForms->has(null))
                                @foreach ($groupedForms[null] as $parent)
                                    <tr class="">
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

                                    {{-- Show child forms --}}
                                    @if ($groupedForms->has($parent->id))
                                        @php
                                            $children = $groupedForms[$parent->id];
                                        @endphp
                                        @foreach ($children as $index => $child)
                                            <tr>
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
                                                        <input class="form-check-input toggle-visible-switch"
                                                            type="checkbox" data-id="{{ $child->id }}"
                                                            {{ $child->visible ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if ($index > 0)
                                                        <form action="{{ route('forms.moveup', $child->id) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                                title="Move Up">↑</button>
                                                        </form>
                                                    @endif
                                                    @if ($index < $children->count() - 1)
                                                        <form action="{{ route('forms.movedown', $child->id) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                                title="Move Down">↓</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        {{-- <tr>
                                            <td></td> <!-- Skip S.No -->
                                            <td colspan="9" class="text-center">
                                                <em>No child forms available for <strong>{{ $parent->name }}</strong></em>
                                            </td>
                                        </tr> --}}
                                    @endif
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="10" class="text-center">No forms found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('.toggle-visible-switch').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const id = this.getAttribute('data-id');
                const toggleSwitch = this;

                fetch(`/registration/forms/${id}/toggle-visible`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Visibility updated successfully.');
                        } else {
                            alert('Failed to update visibility.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating visibility.');
                    });
            });
        });
    </script>
@endsection
