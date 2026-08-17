@extends('admin.layouts.master')

@section('title', 'Venue Master')

@push('styles')
{{-- Shared with Class Session Master and Stream: same shape, one module
     stylesheet (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/basic-masters-admin.css') }}?v={{ @filemtime(public_path('css/basic-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid vm-master-page">
    <x-breadcrum title="Venue Master" :showBack="false">
        {{-- Add and Edit are modals, not pages (§3c). --}}
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#venueModal"
            data-mode="add" data-action="{{ route('Venue-Master.store') }}">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add New Venue</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-1 overflow-hidden">
            <div class="card-body p-3 p-md-4">

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">S. No.</th>
                                    <th scope="col">Venue Name</th>
                                    <th scope="col" class="text-nowrap">Short Name</th>
                                    <th scope="col">Description</th>
                                    {{-- Status before Action: the headers used to be the other way
                                         round from the cells, so the switch sat under "Action" and
                                         the buttons under "Status". --}}
                                    <th scope="col" class="text-nowrap text-center">Status</th>
                                    <th scope="col" class="text-nowrap text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($venues as $key => $venue)
                                    @php $vmIsActive = $venue->active_inactive == 1; @endphp
                                    <tr>
                                        <td>{{ $venues->firstItem() + $key }}</td>
                                        <td>{{ $venue->venue_name }}</td>
                                        <td>{{ $venue->venue_short_name }}</td>
                                        <td class="bm-col-wrap">{{ $venue->description }}</td>

                                        {{-- Status: soft badge, display only (§3b) --}}
                                        <td class="text-center">
                                            <span class="status-pill badge rounded-1 {{ $vmIsActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                                                data-order="{{ (int) $vmIsActive }}">
                                                {{ $vmIsActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>

                                        {{-- Action: Edit · the status switch · Delete — visible in the cell as
                                             equal-width icon-over-label stacks (§3b). Edit opens the modal. --}}
                                        <td>
                                            <div class="bm-act-group" role="group" aria-label="Venue actions">
                                                <button type="button" class="bm-act bm-act--edit"
                                                    title="Edit" aria-label="Edit venue"
                                                    data-bs-toggle="modal" data-bs-target="#venueModal"
                                                    data-mode="edit"
                                                    data-action="{{ route('Venue-Master.update', $venue->venue_id) }}"
                                                    data-venue-name="{{ $venue->venue_name }}"
                                                    data-venue-short-name="{{ $venue->venue_short_name }}"
                                                    data-venue-description="{{ $venue->description }}">
                                                    <span class="bm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                    <span class="bm-act__label">Edit</span>
                                                </button>

                                                {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                     custom.css pulls a .form-check-input inside one left
                                                     by -2.375rem. custom.js binds .status-toggle globally
                                                     off these data-* attributes. --}}
                                                <label class="bm-act bm-act--toggle"
                                                    title="{{ $vmIsActive ? 'Deactivate' : 'Activate' }}">
                                                    <span class="bm-act__icon">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="venue_master" data-column="active_inactive"
                                                            data-id="{{ $venue->venue_id }}" data-id_column="venue_id"
                                                            {{ $vmIsActive ? 'checked' : '' }}
                                                            aria-label="{{ $vmIsActive ? 'Deactivate' : 'Activate' }} venue">
                                                    </span>
                                                    <span class="bm-act__label">{{ $vmIsActive ? 'Deactivate' : 'Activate' }}</span>
                                                </label>

                                                @if ($vmIsActive)
                                                    {{-- An active venue cannot be deleted — mirror that guard
                                                         rather than offering a control that fails. --}}
                                                    <span class="bm-act bm-act--del is-disabled" aria-disabled="true"
                                                        title="Deactivate this venue before deleting">
                                                        <span class="bm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="bm-act__label">Delete</span>
                                                    </span>
                                                @else
                                                    <form action="{{ route('Venue-Master.destroy', $venue->venue_id) }}"
                                                        method="POST" class="bm-act bm-act--del delete-form"
                                                        onsubmit="return confirm('Are you sure you want to delete this venue?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bm-act__btn"
                                                            title="Delete" aria-label="Delete venue">
                                                            <span class="bm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                            <span class="bm-act__label">Delete</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                            <span class="fw-medium">No venues found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- §4 variant B: a Laravel paginator wearing the shared footer chrome. --}}
                    @if ($venues->total() > 0)
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="programme-dt-pagination">
                                {{ $venues->links('vendor.pagination.custom') }}
                            </div>
                            {{-- "Showing [10 v] of N items" — the rows-per-page select the
                                 DataTables pages get from the enhancer, hand-written here
                                 because this grid is a Laravel paginator (§4B). --}}
                            <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                                <div class="dataTables_length">
                                    <label class="mb-0">Showing
                                        <select id="vmPerPage" class="form-select form-select-sm"
                                            aria-label="Rows per page">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="dataTables_info">of {{ number_format($venues->total()) }} items</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit modal (§3c). One dialog serves both: the mode only swaps the
         title, the form action, the spoofed method and the submit caption. --}}
    <div class="modal fade bm-modal" id="venueModal" tabindex="-1" aria-labelledby="venueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ route('Venue-Master.store') }}" id="venueForm">
                    @csrf
                    {{-- update() is a resource PUT; add mode spoofs POST, which is a no-op. --}}
                    <input type="hidden" name="_method" id="venueFormMethod" value="{{ old('_method', 'POST') }}">
                    <input type="hidden" name="form_mode" id="venueFormMode" value="{{ old('form_mode', 'add') }}">
                    <input type="hidden" name="form_action" id="venueFormAction" value="{{ old('form_action') }}">

                    <div class="bm-modal-header">
                        <h5 class="bm-modal-title" id="venueModalLabel">Add New Venue</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="bm-modal-body">
                        <div class="bm-field-card">
                            <div class="mb-3">
                                <label for="vm_venue_name" class="bm-form-label">
                                    Venue Name <span class="bm-req">*</span>
                                </label>
                                <input type="text" id="vm_venue_name" name="venue_name"
                                    class="bm-control @error('venue_name') is-invalid @enderror"
                                    value="{{ old('venue_name') }}" placeholder="Enter venue name" required>
                                @error('venue_name')
                                    <span class="bm-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="vm_venue_short_name" class="bm-form-label">
                                    Short Name <span class="bm-req">*</span>
                                </label>
                                <input type="text" id="vm_venue_short_name" name="venue_short_name"
                                    class="bm-control @error('venue_short_name') is-invalid @enderror"
                                    value="{{ old('venue_short_name') }}" placeholder="Enter short name" required>
                                @error('venue_short_name')
                                    <span class="bm-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="vm_description" class="bm-form-label">Description</label>
                                <textarea id="vm_description" name="description" rows="3"
                                    class="bm-control @error('description') is-invalid @enderror"
                                    placeholder="Enter description">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="bm-field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bm-modal-footer">
                        <button type="button" class="btn bm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bm-btn-submit" id="venueSubmit">Save Venue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";

// On DOMContentLoaded, not inline: this section renders BEFORE the footer's
// script tags, so `bootstrap` does not exist yet at parse time.
document.addEventListener('DOMContentLoaded', function () {
    var storeAction = @json(route('Venue-Master.store'));
    var modal = document.getElementById('venueModal');
    var form = document.getElementById('venueForm');
    if (!modal || !form) { return; }

    var elMethod = document.getElementById('venueFormMethod');
    var elMode = document.getElementById('venueFormMode');
    var elAction = document.getElementById('venueFormAction');
    var elTitle = document.getElementById('venueModalLabel');
    var elSubmit = document.getElementById('venueSubmit');

    function applyMode(mode, action) {
        var editing = mode === 'edit';
        form.setAttribute('action', action || storeAction);
        elMethod.value = editing ? 'PUT' : 'POST';
        elMode.value = editing ? 'edit' : 'add';
        elAction.value = action || '';
        elTitle.textContent = editing ? 'Edit Venue' : 'Add New Venue';
        elSubmit.textContent = editing ? 'Update Venue' : 'Save Venue';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) { return; }

        var data = trigger.dataset;
        applyMode(data.mode, data.action);

        document.getElementById('vm_venue_name').value = data.venueName || '';
        document.getElementById('vm_venue_short_name').value = data.venueShortName || '';
        document.getElementById('vm_description').value = data.venueDescription || '';

        // A failed submit leaves its messages behind; a fresh open must not
        // show the previous attempt's errors.
        form.querySelectorAll('.bm-field-error').forEach(function (node) { node.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (node) { node.classList.remove('is-invalid'); });
    });

    modal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        applyMode('add', storeAction);
    });

    @if ($errors->any())
        // Validation bounced the save: reopen the dialog in the mode it was in,
        // with the values old() has already put back into the fields.
        applyMode(elMode.value, elAction.value);
        new bootstrap.Modal(modal).show();
    @endif

    var perPage = document.getElementById('vmPerPage');
    if (perPage) {
        perPage.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', '1');   // page 1, or the user lands past the end
            window.location.href = url.toString();
        });
    }
});
</script>
@endsection
