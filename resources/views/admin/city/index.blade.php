@extends('admin.layouts.master')

@section('title', 'City Master')

@push('styles')
{{-- Shared with Country, State and District: one module stylesheet for the whole
     address module (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/address-masters-admin.css') }}?v={{ @filemtime(public_path('css/address-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid city-master-page">
    <x-breadcrum title="City Master" :showBack="false">
        {{-- Add and Edit are modals, not pages (§3c). --}}
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#cityModal" data-mode="add">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add New City</span>
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
                                    <th scope="col">City Name</th>
                                    {{-- State then District, matching the cells: the headers used to
                                         read "District, State" while the cells rendered the state
                                         first, so every row showed the two swapped. --}}
                                    <th scope="col">State</th>
                                    <th scope="col">District</th>
                                    <th scope="col" class="text-nowrap text-center">Status</th>
                                    <th scope="col" class="text-nowrap text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cities as $key => $city)
                                    @php $citIsActive = $city->active_inactive == 1; @endphp
                                    <tr>
                                        <td>{{ $cities->firstItem() + $key }}</td>
                                        <td>{{ $city->city_name }}</td>
                                        <td>{{ $city->state->state_name ?? 'N/A' }}</td>
                                        <td>{{ $city->district->district_name ?? 'N/A' }}</td>

                                        {{-- Status: soft badge, display only (§3b) --}}
                                        <td class="text-center">
                                            <span class="status-pill badge rounded-1 {{ $citIsActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                                                data-order="{{ (int) $citIsActive }}">
                                                {{ $citIsActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>

                                        {{-- Action: Edit · the status switch · Delete — visible in the cell as
                                             equal-width icon-over-label stacks (§3b). Edit opens the modal. --}}
                                        <td>
                                            <div class="adr-act-group" role="group" aria-label="City actions">
                                                <button type="button" class="adr-act adr-act--edit"
                                                    title="Edit" aria-label="Edit city"
                                                    data-bs-toggle="modal" data-bs-target="#cityModal"
                                                    data-mode="edit"
                                                    data-action="{{ route('master.city.update', $city->pk) }}"
                                                    data-city-name="{{ $city->city_name }}"
                                                    data-country="{{ $city->country_master_pk }}"
                                                    data-state="{{ $city->state_master_pk }}"
                                                    data-district="{{ $city->district_master_pk }}"
                                                    data-status="{{ $citIsActive ? 1 : 2 }}">
                                                    <span class="adr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                    <span class="adr-act__label">Edit</span>
                                                </button>

                                                {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                     custom.css pulls a .form-check-input inside one left
                                                     by -2.375rem. custom.js binds .status-toggle globally
                                                     off these data-* attributes. --}}
                                                <label class="adr-act adr-act--toggle"
                                                    title="{{ $citIsActive ? 'Deactivate' : 'Activate' }}">
                                                    <span class="adr-act__icon">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="city_master" data-column="active_inactive"
                                                            data-id="{{ $city->pk }}"
                                                            {{ $citIsActive ? 'checked' : '' }}
                                                            aria-label="{{ $citIsActive ? 'Deactivate' : 'Activate' }} city">
                                                    </span>
                                                    <span class="adr-act__label">{{ $citIsActive ? 'Deactivate' : 'Activate' }}</span>
                                                </label>

                                                @if ($citIsActive)
                                                    {{-- An active city cannot be deleted — mirror that guard
                                                         rather than offering a control that fails. --}}
                                                    <span class="adr-act adr-act--del is-disabled" aria-disabled="true"
                                                        title="Deactivate this city before deleting">
                                                        <span class="adr-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="adr-act__label">Delete</span>
                                                    </span>
                                                @else
                                                    <form action="{{ route('master.city.delete', $city->pk) }}"
                                                        method="POST" class="adr-act adr-act--del"
                                                        onsubmit="return confirm('Are you sure you want to delete this city?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="adr-act__btn"
                                                            title="Delete" aria-label="Delete city">
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
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                            <span class="fw-medium">No cities found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- §4 variant B: a Laravel paginator wearing the shared footer chrome. --}}
                    @if ($cities->total() > 0)
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="programme-dt-pagination">
                                {{ $cities->links('vendor.pagination.custom') }}
                            </div>
                            <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                                <div class="dataTables_length">
                                    <label class="mb-0">Showing
                                        <select id="citPerPage" class="form-select form-select-sm"
                                            aria-label="Rows per page">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="dataTables_info">of {{ number_format($cities->total()) }} items</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit modal (§3c). One dialog serves both: the mode only swaps the
         title, the form action, the submit caption and the pre-filled values.
         cityStore() and cityUpdate() are both POST, so no method spoofing. --}}
    <div class="modal fade adr-modal" id="cityModal" tabindex="-1"
        aria-labelledby="cityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ old('form_action', route('master.city.store')) }}" id="cityForm">
                    @csrf
                    <input type="hidden" name="form_mode" id="citFormMode" value="{{ old('form_mode', 'add') }}">
                    <input type="hidden" name="form_action" id="citFormAction" value="{{ old('form_action') }}">

                    <div class="adr-modal-header">
                        <h5 class="adr-modal-title" id="cityModalLabel">Add New City</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="adr-modal-body">
                        <div class="adr-field-card">
                            <div class="adr-field">
                                <label for="cit_country" class="adr-field-label">
                                    Country <span class="adr-req">*</span>
                                </label>
                                <select name="country_master_pk" id="cit_country" class="adr-control" required>
                                    <option value="">Select Country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->pk }}"
                                            @selected(old('country_master_pk') == $country->pk)>
                                            {{ $country->country_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- State and District are filled by the cascade, so 37 states and
                                 850 districts never sit in the page source (§3c). --}}
                            <div class="adr-field">
                                <label for="cit_state" class="adr-field-label">
                                    State <span class="adr-req">*</span>
                                </label>
                                <select name="state_master_pk" id="cit_state"
                                    class="adr-control @error('state_master_pk') is-invalid @enderror" required disabled>
                                    <option value="">Select Country first</option>
                                </select>
                                @error('state_master_pk')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="cit_district" class="adr-field-label">
                                    District <span class="adr-req">*</span>
                                </label>
                                <select name="district_master_pk" id="cit_district"
                                    class="adr-control @error('district_master_pk') is-invalid @enderror" required disabled>
                                    <option value="">Select State first</option>
                                </select>
                                @error('district_master_pk')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="cit_city_name" class="adr-field-label">
                                    City Name <span class="adr-req">*</span>
                                </label>
                                <input type="text" id="cit_city_name" name="city_name"
                                    class="adr-control @error('city_name') is-invalid @enderror"
                                    value="{{ old('city_name') }}" placeholder="Enter city name" required>
                                @error('city_name')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="cit_status" class="adr-field-label">
                                    Status <span class="adr-req">*</span>
                                </label>
                                <select name="active_inactive" id="cit_status"
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
                        <button type="submit" class="btn adr-btn-submit" id="citSubmit">Save City</button>
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
    var storeAction = @json(route('master.city.store'));
    var statesUrl = @json(route('master.country.get.state.by.country'));
    var districtsUrl = @json(route('master.country.get.district.by.state'));
    var csrf = @json(csrf_token());

    var modal = document.getElementById('cityModal');
    var form = document.getElementById('cityForm');
    if (!modal || !form) { return; }

    var elMode = document.getElementById('citFormMode');
    var elAction = document.getElementById('citFormAction');
    var elTitle = document.getElementById('cityModalLabel');
    var elSubmit = document.getElementById('citSubmit');
    var elCountry = document.getElementById('cit_country');
    var elState = document.getElementById('cit_state');
    var elDistrict = document.getElementById('cit_district');

    function applyMode(mode, action) {
        var editing = mode === 'edit';
        form.setAttribute('action', editing ? (action || '') : storeAction);
        elMode.value = editing ? 'edit' : 'add';
        elAction.value = editing ? (action || '') : '';
        elTitle.textContent = editing ? 'Edit City' : 'Add New City';
        elSubmit.textContent = editing ? 'Update City' : 'Save City';
    }

    function fill(select, rows, valueKey, labelKey, placeholder, selected) {
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        (rows || []).forEach(function (row) {
            var option = document.createElement('option');
            option.value = row[valueKey];
            option.textContent = row[labelKey];
            select.appendChild(option);
        });
        select.disabled = false;
        // Populate first, THEN prefill — assigning select.value before the options
        // exist is silently dropped (§3c).
        if (selected) { select.value = String(selected); }
    }

    function lookup(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        }).then(function (response) { return response.json(); });
    }

    function loadStates(countryId, selectedState, selectedDistrict) {
        elState.innerHTML = '<option value="">Select Country first</option>';
        elState.disabled = true;
        elDistrict.innerHTML = '<option value="">Select State first</option>';
        elDistrict.disabled = true;

        if (!countryId) { return Promise.resolve(); }

        elState.innerHTML = '<option value="">Loading…</option>';

        return lookup(statesUrl, { country_id: countryId })
            .then(function (data) {
                fill(elState, data.states, 'pk', 'state_name', 'Select State', selectedState);
                // Only chase the districts once a state is actually selected.
                if (selectedState) { return loadDistricts(selectedState, selectedDistrict); }
            })
            .catch(function () {
                elState.innerHTML = '<option value="">Could not load states</option>';
            });
    }

    function loadDistricts(stateId, selectedDistrict) {
        elDistrict.innerHTML = '<option value="">Select State first</option>';
        elDistrict.disabled = true;

        if (!stateId) { return Promise.resolve(); }

        elDistrict.innerHTML = '<option value="">Loading…</option>';

        return lookup(districtsUrl, { state_id: stateId })
            .then(function (data) {
                fill(elDistrict, data.districts, 'pk', 'district_name', 'Select District', selectedDistrict);
            })
            .catch(function () {
                elDistrict.innerHTML = '<option value="">Could not load districts</option>';
            });
    }

    elCountry.addEventListener('change', function () {
        loadStates(this.value, null, null);
    });

    elState.addEventListener('change', function () {
        loadDistricts(this.value, null);
    });

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) { return; }

        var data = trigger.dataset;
        applyMode(data.mode, data.action);

        document.getElementById('cit_city_name').value = data.cityName || '';
        elCountry.value = data.country || '';
        // Anything other than 1 is inactive — the column holds 2 from the forms
        // and 0 from the inline toggle.
        document.getElementById('cit_status').value = data.mode === 'edit'
            ? (data.status === '1' ? '1' : '2')
            : '1';

        loadStates(data.country || '', data.state || null, data.district || null);

        // A failed submit leaves its messages behind; a fresh open must not show
        // the previous attempt's errors.
        form.querySelectorAll('.adr-error').forEach(function (n) { n.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
    });

    modal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        applyMode('add', storeAction);
        loadStates('', null, null);
    });

    @if ($errors->any())
        // Validation bounced the save: reopen the dialog in the mode it was in,
        // with the values old() has already put back — including the two cascaded
        // selects, which have to be re-fetched before they can be set.
        applyMode(elMode.value, elAction.value);
        loadStates(elCountry.value, @json(old('state_master_pk')), @json(old('district_master_pk')));
        new bootstrap.Modal(modal).show();
    @endif

    var perPage = document.getElementById('citPerPage');
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
