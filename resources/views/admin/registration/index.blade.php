@extends('admin.layouts.master')

@section('title', 'Forms - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Forms</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="{{ route('dashboard') }}">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Registration
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <div class="row">
                    <div class="col-6">
                        <h4>Registration</h4>
                    </div>
                    <div class="col-6 text-end">
                        <a href="{{ route('forms.create') }}" class="btn btn-primary">+ Add Form</a>
                    </div>
                </div>
                <hr>
                @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <table id="zero_config" class="table table-striped table-bordered text-nowrap align-middle dataTable"
                    aria-describedby="zero_config_info">
                    <thead>
                        <tr>
                            <th class="col">S.No.</th>
                            <th class="col">Form ID</th>
                            <th class="col">Name</th>
                            <th class="col">Description</th>
                            <th class="col">From</th>
                            <th class="col">To</th>
                            <th class="col">Submissions List</th>
                            <th class="col">Pending Submissions</th>
                            <th class="col">Edit Form Fields</th>
                            <th class="col">Actions</th>
                            <th class="col">Status</th>
                            <th class="col">Position</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($forms as $index => $form)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $form->id }}</td>
                            <td>{{ $form->name }}</td>
                            <td>{{ $form->description }}</td>
                            <td>{{ \Carbon\Carbon::parse($form->course_sdate)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($form->course_edate)->format('d-m-Y') }}</td>
                            <td>
                                <a href="{{ route('forms.courseList', $form->id) }}" class="btn btn-sm btn-success">
                                    View
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('forms.show', $form->id) }}" class="btn btn-sm btn-info">
                                    Preview
                                </a>
                            </td>
                              <td>
                                <a href="{{ route('forms.fc_edit', $form->id) }}" class="btn btn-sm btn-warning">
                                    Edit Fields
                                </a>
                            </td>

                            <td class="d-flex gap-1">
                                <a href="{{ route('forms.edit', $form->id) }}" class="btn btn-sm btn-danger">Edit</a>
                            </td>
                            <td>
                                <!-- Visibility Toggle -->
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-visible-switch" type="checkbox"
                                        data-id="{{ $form->id }}" {{ $form->visible ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <!-- Move Up -->
                                @if ($index > 0)
                                <form action="{{ route('forms.moveup', $form->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" title="Move Up">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                </form>
                                @endif

                                <!-- Move Down -->
                                @if ($index < count($forms) - 1) <form action="{{ route('forms.movedown', $form->id) }}"
                                    method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" title="Move Down">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    </form>
                                    @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $forms->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.querySelectorAll('.toggle-visible-switch').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const id = this.getAttribute('data-id');
        const toggleSwitch = this; // Store reference to `this`

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