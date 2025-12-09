@extends('admin.layouts.master')

@section('title', 'Inactive Forms - Sargam | Lal Bahadur')

@section('setup_content')
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row">
                <div class="col-6">
                    <h4>Inactive Registration Forms</h4>
                </div>
                <div class="col-6 text-end">
                    <a href="{{ route('forms.index') }}" class="btn btn-primary"> Back to Active Forms</a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Form ID</th>
                                    <th>Course Name</th>
                                    <th>Description</th>
                                    <th>Edit</th>
                                    <th>Activate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $serial = 1; @endphp
                                @forelse ($forms as $index => $form)
                                    <tr>
                                        <td>{{ $serial++ }}</td>
                                        <td>{{ $form->id }}</td>
                                        <td>{{ $form->name }}</td>
                                        <td>{{ $form->description }}</td>
                                        <td>
                                            <a href="{{ route('forms.edit', $form->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input toggle-visible-switch" type="checkbox"
                                                    data-id="{{ $form->id }}" {{ $form->visible ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No inactive forms found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{-- {{ $forms->links() }} --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @section('scripts')
        <script>
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
                            location.reload(); // refresh table state
                        } else {
                            alert('Failed to update visibility.');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('An error occurred while updating visibility.');
                    });
            });
        </script>
    @endsection
