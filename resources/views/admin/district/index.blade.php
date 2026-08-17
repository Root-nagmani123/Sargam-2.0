@extends('admin.layouts.master')

@section('title', 'District Master')

@push('styles')
{{-- Shared with Country, State and City: one module stylesheet for the whole
     address module (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/address-masters-admin.css') }}?v={{ @filemtime(public_path('css/address-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid district-master-page">
    <x-breadcrum title="District Master" :showBack="false">
        {{-- Add and Edit are modals, not pages (§3c). --}}
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#districtModal" data-mode="add">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add New District</span>
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
                                    <th scope="col">District Name</th>
                                    <th scope="col">State</th>
                                    {{-- Status before Action: the headers used to be the other way
                                         round from the cells, so the switch sat under "Action" and
                                         the menu under "Status". --}}
                                    <th scope="col" class="text-nowrap text-center">Status</th>
                                    <th scope="col" class="text-nowrap text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($districts as $key => $district)
                                    @php $dstIsActive = $district->active_inactive == 1; @endphp
                                    <tr>
                                        <td>{{ $districts->firstItem() + $key }}</td>
                                        <td>{{ $district->district_name }}</td>
                                        <td>{{ $district->state->state_name ?? 'N/A' }}</td>

                                        {{-- Status: soft badge, display only (§3b) --}}
                                        <td class="text-center">
                                            <span class="status-pill badge rounded-1 {{ $dstIsActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                                                data-order="{{ (int) $dstIsActive }}">
                                                {{ $dstIsActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>

                                        {{-- Action: Edit · the status switch · Delete — visible in the cell as
                                             equal-width icon-over-label stacks (§3b). Edit opens the modal. --}}
                                        <td>
                                            <div class="adr-act-group" role="group" aria-label="District actions">
                                                <button type="button" class="adr-act adr-act--edit"
                                                    title="Edit" aria-label="Edit district"
                                                    data-bs-toggle="modal" data-bs-target="#districtModal"
                                                    data-mode="edit"
                                                    data-action="{{ route('master.district.update', $district->pk) }}"
                                                    data-district-name="{{ $district->district_name }}"
                                                    data-country="{{ $district->country_master_pk }}"
                                                    data-state="{{ $district->state_master_pk }}"
                                                    data-status="{{ $dstIsActive ? 1 : 2 }}">
                                                    <span class="adr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                    <span class="adr-act__label">Edit</span>
                                                </button>

                                                {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                     custom.css pulls a .form-check-input inside one left
                                                     by -2.375rem. custom.js binds .status-toggle globally
                                                     off these data-* attributes. --}}
                                                <label class="adr-act adr-act--toggle"
                                                    title="{{ $dstIsActive ? 'Deactivate' : 'Activate' }}">
                                                    <span class="adr-act__icon">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="state_district_mapping" data-column="active_inactive"
                                                            data-id="{{ $district->pk }}"
                                                            {{ $dstIsActive ? 'checked' : '' }}
                                                            aria-label="{{ $dstIsActive ? 'Deactivate' : 'Activate' }} district">
                                                    </span>
                                                    <span class="adr-act__label">{{ $dstIsActive ? 'Deactivate' : 'Activate' }}</span>
                                                </label>

                                                @if ($dstIsActive)
                                                    {{-- An active district cannot be deleted — mirror that guard
                                                         rather than offering a control that fails. --}}
                                                    <span class="adr-act adr-act--del is-disabled" aria-disabled="true"
                                                        title="Deactivate this district before deleting">
                                                        <span class="adr-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="adr-act__label">Delete</span>
                                                    </span>
                                                @else
                                                    <form action="{{ route('master.district.delete', $district->pk) }}"
                                                        method="POST" class="adr-act adr-act--del"
                                                        onsubmit="return confirm('Are you sure you want to delete this district?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="adr-act__btn"
                                                            title="Delete" aria-label="Delete district">
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
                                            <span class="fw-medium">No districts found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- §4 variant B: a Laravel paginator wearing the shared footer chrome. --}}
                    @if ($districts->total() > 0)
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="programme-dt-pagination">
                                {{ $districts->links('vendor.pagination.custom') }}
                            </div>
                            <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                                <div class="dataTables_length">
                                    <label class="mb-0">Showing
                                        <select id="dstPerPage" class="form-select form-select-sm"
                                            aria-label="Rows per page">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="dataTables_info">of {{ number_format($districts->total()) }} items</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit modal (§3c). One dialog serves both: the mode only swaps the
         title, the form action, the submit caption and the pre-filled values.
         districtStore() and districtUpdate() are both POST, so no method spoofing. --}}
    <div class="modal fade adr-modal" id="districtModal" tabindex="-1"
        aria-labelledby="districtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ old('form_action', route('master.district.store')) }}" id="districtForm">
                    @csrf
                    <input type="hidden" name="form_mode" id="dstFormMode" value="{{ old('form_mode', 'add') }}">
                    <input type="hidden" name="form_action" id="dstFormAction" value="{{ old('form_action') }}">

                    <div class="adr-modal-header">
                        <h5 class="adr-modal-title" id="districtModalLabel">Add New District</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="adr-modal-body">
                        <div class="adr-field-card">
                            <div class="adr-field">
                                <label for="dst_country" class="adr-field-label">
                                    Country <span class="adr-req">*</span>
                                </label>
                                <select name="country_master_pk" id="dst_country" class="adr-control" required>
                                    <option value="">Select Country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->pk }}"
                                            @selected(old('country_master_pk') == $country->pk)>
                                            {{ $country->country_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Filled by the country cascade, so 37 states never sit in the
                                 page source (§3c). --}}
                            <div class="adr-field">
                                <label for="dst_state" class="adr-field-label">
                                    State <span class="adr-req">*</span>
                                </label>
                                <select name="state_master_pk" id="dst_state"
                                    class="adr-control @error('state_master_pk') is-invalid @enderror" required disabled>
                                    <option value="">Select Country first</option>
                                </select>
                                @error('state_master_pk')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="dst_district_name" class="adr-field-label">
                                    District Name <span class="adr-req">*</span>
                                </label>
                                <input type="text" id="dst_district_name" name="district_name"
                                    class="adr-control @error('district_name') is-invalid @enderror"
                                    value="{{ old('district_name') }}" placeholder="Enter district name" required>
                                @error('district_name')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="dst_status" class="adr-field-label">
                                    Status <span class="adr-req">*</span>
                                </label>
                                <select name="active_inactive" id="dst_status"
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
                        <button type="submit" class="btn adr-btn-submit" id="dstSubmit">Save District</button>
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
    var storeAction = @json(route('master.district.store'));
    var statesUrl = @json(route('master.country.get.state.by.country'));
    var csrf = @json(csrf_token());

    var modal = document.getElementById('districtModal');
    var form = document.getElementById('districtForm');
    if (!modal || !form) { return; }

    var elMode = document.getElementById('dstFormMode');
    var elAction = document.getElementById('dstFormAction');
    var elTitle = document.getElementById('districtModalLabel');
    var elSubmit = document.getElementById('dstSubmit');
    var elCountry = document.getElementById('dst_country');
    var elState = document.getElementById('dst_state');

    function applyMode(mode, action) {
        var editing = mode === 'edit';
        form.setAttribute('action', editing ? (action || '') : storeAction);
        elMode.value = editing ? 'edit' : 'add';
        elAction.value = editing ? (action || '') : '';
        elTitle.textContent = editing ? 'Edit District' : 'Add New District';
        elSubmit.textContent = editing ? 'Update District' : 'Save District';
    }

    // Populate first, THEN prefill — assigning select.value before the options
    // exist is silently dropped (§3c).
    function loadStates(countryId, selected) {
        elState.innerHTML = '<option value="">Select Country first</option>';
        elState.disabled = true;

        if (!countryId) { return Promise.resolve(); }

        elState.innerHTML = '<option value="">Loading…</option>';

        return fetch(statesUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ country_id: countryId }),
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                elState.innerHTML = '<option value="">Select State</option>';
                (data.states || []).forEach(function (state) {
                    var option = document.createElement('option');
                    option.value = state.pk;
                    option.textContent = state.state_name;
                    elState.appendChild(option);
                });
                elState.disabled = false;
                if (selected) { elState.value = String(selected); }
            })
            .catch(function () {
                // Leave the select usable-but-empty rather than stuck on "Loading…".
                elState.innerHTML = '<option value="">Could not load states</option>';
            });
    }

    elCountry.addEventListener('change', function () {
        loadStates(this.value, null);
    });

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) { return; }

        var data = trigger.dataset;
        applyMode(data.mode, data.action);

        document.getElementById('dst_district_name').value = data.districtName || '';
        elCountry.value = data.country || '';
        // Anything other than 1 is inactive — the column holds 2 from the forms
        // and 0 from the inline toggle.
        document.getElementById('dst_status').value = data.mode === 'edit'
            ? (data.status === '1' ? '1' : '2')
            : '1';

        loadStates(data.country || '', data.state || null);

        // A failed submit leaves its messages behind; a fresh open must not show
        // the previous attempt's errors.
        form.querySelectorAll('.adr-error').forEach(function (n) { n.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
    });

    modal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        applyMode('add', storeAction);
        loadStates('', null);
    });

    @if ($errors->any())
        // Validation bounced the save: reopen the dialog in the mode it was in,
        // with the values old() has already put back into the fields — including
        // the cascaded state, which has to be re-fetched before it can be set.
        applyMode(elMode.value, elAction.value);
        loadStates(elCountry.value, @json(old('state_master_pk')));
        new bootstrap.Modal(modal).show();
    @endif

    var perPage = document.getElementById('dstPerPage');
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
