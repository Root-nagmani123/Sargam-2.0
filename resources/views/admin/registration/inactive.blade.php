@extends('admin.layouts.master')

@section('title', 'Inactive Forms - Sargam | Lal Bahadur')

@section('setup_content')
    <div class="container-fluid inactive-forms-page">
        <x-breadcrum title="Inactive Registration Forms" />

        <div class="card mt-3 overflow-hidden rounded-3">
            <div class="card-body p-3 p-md-4">

                {{-- Toolbar (§2). The controller has always supported ?search=; it
                     just had no UI until now. --}}
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <x-programme-dt-search :action="route('forms.inactive')"
                            placeholder="Search course name"
                            label="Search inactive forms by course name">
                            <input type="hidden" name="per_page" value="{{ request('per_page', '10') }}">
                        </x-programme-dt-search>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        {{-- The old markup wrapped this in a hand-written
                             #zero_config_wrapper.dataTables_wrapper and tagged the table
                             `dataTable` — decoration only, no DataTable was ever
                             initialised here (and `dataTable` !== the `datatable`
                             class the footer auto-init looks for). Dropped, because a
                             fake wrapper is exactly what getWrapper() in
                             datatable-global-ui.js would latch onto. --}}
                        <table id="zero_config"
                            class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                            data-sargam-dt-ui="false">
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
                                @forelse ($forms as $form)
                                    <tr>
                                        {{-- firstItem() so the numbering continues on page 2 rather than restarting. --}}
                                        <td>{{ $forms->firstItem() + $loop->index }}</td>
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
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            @if (request('search') !== null && request('search') !== '')
                                                No inactive form matches “{{ request('search') }}”.
                                                <a href="{{ route('forms.inactive') }}">Clear the search</a>.
                                            @else
                                                No inactive forms found.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer variant B (§4B). --}}
                    <x-programme-dt-footer :paginator="$forms" per-page-id="inactiveFormsPerPage" />
                </div>

            </div>
        </div>
    </div>
    @endsection

    @push('scripts')
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
    @endpush
