@extends('admin.layouts.master')

@section('title', 'Inactive Forms - Sargam | Lal Bahadur')

@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Inactive Registration Forms" />

        <div class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config"
                            class="table text-nowrap align-middle dataTable"
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
                            {{-- Rows come from the server-side DataTable (see script below). --}}
                            <tbody></tbody>
                        </table>
                        <div class="mt-3">
                            {{-- {{ $forms->links() }} --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            $(document).ready(function () {
                // Server-side: search, sort and paging are resolved in SQL.
                $('#zero_config').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('forms.inactive') }}",
                        type: 'GET'
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'id', name: 'id' },
                        { data: 'name', name: 'name' },
                        { data: 'description', name: 'description' },
                        { data: 'edit', name: 'edit', orderable: false, searchable: false },
                        { data: 'activate', name: 'activate', orderable: false, searchable: false }
                    ],
                    order: [],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    language: {
                        processing: 'Loading data…',
                        emptyTable: 'No inactive forms found.'
                    }
                });
            });

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
    @endpush
