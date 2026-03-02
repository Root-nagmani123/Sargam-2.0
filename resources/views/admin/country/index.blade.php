@extends('admin.layouts.master')

@section('title', 'Country List')

@section('setup_content')
<div class="container-fluid country-index">
    <x-breadcrum title="Country List" variant="glass" />
    <x-session_message />
    <div class="datatables">
        <div class="card overflow-hidden" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                <div class="row align-items-center mb-0 g-2">
                    <div class="col-12 col-md-6">
                        <h4 class="mb-0 fw-bold">Country List</h4>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex justify-content-start justify-content-md-end align-items-center gap-2">
                            <button type="button" class="btn btn-primary px-3 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#createCountryModal">
                                <i class="material-icons material-symbols-rounded fs-5 align-middle">add</i>
                                Add Country
                            </button>
                        </div>
                    </div>
                </div>
                <hr class="my-3">

                <div class="table-responsive overflow-x-auto">
                    {!! $dataTable->table(['class' => 'table w-100 text-nowrap align-middle mb-0']) !!}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Country Modal --}}
<div class="modal fade" id="createCountryModal" tabindex="-1" aria-labelledby="createCountryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCountryModalLabel">Add Country</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('master.country.store') }}" method="POST" id="createCountryForm">
                    @csrf
                    <div class="row">
                        <div class="col-sm-6">
                            <label class="form-label">Country Name <span style="color:red;">*</span></label>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="country_name[]"
                                    placeholder="Country Name" value="{{ old('country_name.0') }}" required>
                                @error('country_name.0')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label for="create_active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                                <select name="active_inactive" id="create_active_inactive" class="form-select" required>
                                    <option value="1" {{ (old('active_inactive', 1) == 1) ? 'selected' : '' }}>Active</option>
                                    <option value="2" {{ (old('active_inactive') == 2) ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('active_inactive')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="createCountryForm" class="btn btn-primary d-flex align-items-center gap-2 btn-sm">Submit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Country Modal --}}
<div class="modal fade" id="editCountryModal" tabindex="-1" aria-labelledby="editCountryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCountryModalLabel">Edit Country</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editCountryForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="edit_country_name" class="form-label">Country Name <span style="color:red;">*</span></label>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="country_name" id="edit_country_name"
                                    placeholder="Country Name" required>
                                @error('country_name')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label for="edit_active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                                <select name="active_inactive" id="edit_active_inactive" class="form-select" required>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                                @error('active_inactive')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editCountryForm" class="btn btn-primary d-flex align-items-center gap-2 btn-sm">Update
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Open create modal when there are validation errors (e.g. after failed submit)
    @if($errors->has('country_name.0') || $errors->has('country_name.*') || $errors->has('active_inactive'))
    (function() {
        var modal = new bootstrap.Modal(document.getElementById('createCountryModal'));
        if (document.getElementById('createCountryModal')) modal.show();
    })();
    @endif

    // Populate edit modal when opened from table edit button
    var editCountryModalEl = document.getElementById('editCountryModal');
    if (editCountryModalEl) {
        editCountryModalEl.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            if (!button) return;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var status = button.getAttribute('data-status');
            var updateUrl = button.getAttribute('data-update-url');
            if (id && updateUrl) {
                document.getElementById('editCountryForm').action = updateUrl;
                document.getElementById('edit_country_name').value = name || '';
                document.getElementById('edit_active_inactive').value = status || '1';
            }
        });
    }
});
</script>
@endpush
