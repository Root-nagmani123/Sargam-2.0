@extends('admin.layouts.master')

@section('title', 'State Master')

@push('styles')
{{-- Shared with Country, District and City: one module stylesheet for the whole
     address module (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/address-masters-admin.css') }}?v={{ @filemtime(public_path('css/address-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid state-master-page">
    <x-breadcrum title="State Master" :showBack="false">
        {{-- Add and Edit are modals, not pages (§3c). --}}
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#stateModal" data-mode="add">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add New State</span>
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
                                    <th scope="col">State Name</th>
                                    <th scope="col">Country</th>
                                    {{-- Status before Action: the headers used to be the other way
                                         round from the cells, so the switch sat under "Action" and
                                         the buttons under "Status". --}}
                                    <th scope="col" class="text-nowrap text-center">Status</th>
                                    <th scope="col" class="text-nowrap text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($states as $key => $state)
                                    @php $stIsActive = $state->active_inactive == 1; @endphp
                                    <tr>
                                        <td>{{ $states->firstItem() + $key }}</td>
                                        <td>{{ $state->state_name }}</td>
                                        <td>{{ $state->country->country_name ?? 'N/A' }}</td>

                                        {{-- Status: soft badge, display only (§3b) --}}
                                        <td class="text-center">
                                            <span class="status-pill badge rounded-1 {{ $stIsActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                                                data-order="{{ (int) $stIsActive }}">
                                                {{ $stIsActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>

                                        {{-- Action: Edit · the status switch · Delete — visible in the cell as
                                             equal-width icon-over-label stacks (§3b). Edit opens the modal. --}}
                                        <td>
                                            <div class="adr-act-group" role="group" aria-label="State actions">
                                                <button type="button" class="adr-act adr-act--edit"
                                                    title="Edit" aria-label="Edit state"
                                                    data-bs-toggle="modal" data-bs-target="#stateModal"
                                                    data-mode="edit"
                                                    data-action="{{ route('master.state.update', $state->pk) }}"
                                                    data-state-name="{{ $state->state_name }}"
                                                    data-country="{{ $state->country_master_pk }}"
                                                    data-status="{{ $stIsActive ? 1 : 2 }}">
                                                    <span class="adr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                    <span class="adr-act__label">Edit</span>
                                                </button>

                                                {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                     custom.css pulls a .form-check-input inside one left
                                                     by -2.375rem. custom.js binds .status-toggle globally
                                                     off these data-* attributes. --}}
                                                <label class="adr-act adr-act--toggle"
                                                    title="{{ $stIsActive ? 'Deactivate' : 'Activate' }}">
                                                    <span class="adr-act__icon">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="state_master" data-column="active_inactive"
                                                            data-id="{{ $state->pk }}"
                                                            {{ $stIsActive ? 'checked' : '' }}
                                                            aria-label="{{ $stIsActive ? 'Deactivate' : 'Activate' }} state">
                                                    </span>
                                                    <span class="adr-act__label">{{ $stIsActive ? 'Deactivate' : 'Activate' }}</span>
                                                </label>

                                                @if ($stIsActive)
                                                    {{-- An active state cannot be deleted — mirror that guard
                                                         rather than offering a control that fails. --}}
                                                    <span class="adr-act adr-act--del is-disabled" aria-disabled="true"
                                                        title="Deactivate this state before deleting">
                                                        <span class="adr-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="adr-act__label">Delete</span>
                                                    </span>
                                                @else
                                                    <form action="{{ route('master.state.delete', $state->pk) }}"
                                                        method="POST" class="adr-act adr-act--del"
                                                        onsubmit="return confirm('Are you sure you want to delete this state?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="adr-act__btn"
                                                            title="Delete" aria-label="Delete state">
                                                            <span class="adr-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                            <span class="adr-act__label">Delete</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                            <span class="fw-medium">No states found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- §4 variant B: a Laravel paginator wearing the shared footer chrome. --}}
                    @if ($states->total() > 0)
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="programme-dt-pagination">
                                {{ $states->links('vendor.pagination.custom') }}
                            </div>
                            <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                                <div class="dataTables_length">
                                    <label class="mb-0">Showing
                                        <select id="stPerPage" class="form-select form-select-sm"
                                            aria-label="Rows per page">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="dataTables_info">of {{ number_format($states->total()) }} items</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit modal (§3c). One dialog serves both: the mode only swaps the
         title, the form action, the submit caption and the pre-filled values.
         stateStore() and stateUpdate() are both POST, so no method spoofing. --}}
    <div class="modal fade adr-modal" id="stateModal" tabindex="-1"
        aria-labelledby="stateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ old('form_action', route('master.state.store')) }}" id="stateForm">
                    @csrf
                    <input type="hidden" name="form_mode" id="stFormMode" value="{{ old('form_mode', 'add') }}">
                    <input type="hidden" name="form_action" id="stFormAction" value="{{ old('form_action') }}">

                    <div class="adr-modal-header">
                        <h5 class="adr-modal-title" id="stateModalLabel">Add New State</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="adr-modal-body">
                        <div class="adr-field-card">
                            <div class="adr-field">
                                <label for="st_country" class="adr-field-label">
                                    Country <span class="adr-req">*</span>
                                </label>
                                <select name="country_master_pk" id="st_country"
                                    class="adr-control @error('country_master_pk') is-invalid @enderror" required>
                                    <option value="">Select Country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->pk }}"
                                            @selected(old('country_master_pk') == $country->pk)>
                                            {{ $country->country_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_master_pk')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="st_state_name" class="adr-field-label">
                                    State Name <span class="adr-req">*</span>
                                </label>
                                <input type="text" id="st_state_name" name="state_name"
                                    class="adr-control @error('state_name') is-invalid @enderror"
                                    value="{{ old('state_name') }}" placeholder="Enter state name" required>
                                @error('state_name')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="st_status" class="adr-field-label">
                                    Status <span class="adr-req">*</span>
                                </label>
                                <select name="active_inactive" id="st_status"
                                    class="adr-control @error('active_inactive') is-invalid @enderror" required>
                                    <option value="1" @selected(old('active_inactive', 1) == 1)>Active</option>
                                    <option value="2" @selected(old('active_inactive') == 2)>Inactive</option>
                                </select>
                                @error('active_inactive')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="adr-modal-footer">
                        <button type="button" class="btn adr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn adr-btn-submit" id="stSubmit">Save State</button>
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
    var storeAction = @json(route('master.state.store'));
    var modal = document.getElementById('stateModal');
    var form = document.getElementById('stateForm');
    if (!modal || !form) { return; }

    var elMode = document.getElementById('stFormMode');
    var elAction = document.getElementById('stFormAction');
    var elTitle = document.getElementById('stateModalLabel');
    var elSubmit = document.getElementById('stSubmit');

    function applyMode(mode, action) {
        var editing = mode === 'edit';
        form.setAttribute('action', editing ? (action || '') : storeAction);
        elMode.value = editing ? 'edit' : 'add';
        elAction.value = editing ? (action || '') : '';
        elTitle.textContent = editing ? 'Edit State' : 'Add New State';
        elSubmit.textContent = editing ? 'Update State' : 'Save State';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) { return; }

        var data = trigger.dataset;
        applyMode(data.mode, data.action);

        document.getElementById('st_state_name').value = data.stateName || '';
        document.getElementById('st_country').value = data.country || '';
        // Anything other than 1 is inactive — the column holds 2 from the forms
        // and 0 from the inline toggle.
        document.getElementById('st_status').value = data.mode === 'edit'
            ? (data.status === '1' ? '1' : '2')
            : '1';

        // A failed submit leaves its messages behind; a fresh open must not show
        // the previous attempt's errors.
        form.querySelectorAll('.adr-error').forEach(function (n) { n.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
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

    var perPage = document.getElementById('stPerPage');
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
