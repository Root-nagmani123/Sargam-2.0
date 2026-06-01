@php
    /** @var \App\Models\District|null $district */
    $isEdit = isset($district) && $district;
    $countryPk = old('country_master_pk', $isEdit ? $district->country_master_pk : '');
    $statePk = old('state_master_pk', $isEdit ? $district->state_master_pk : '');
    $districtName = old('district_name', $isEdit ? $district->district_name : '');
    $statusValue = old('active_inactive', $isEdit ? $district->active_inactive : 1);
@endphp

<form action="{{ $isEdit ? route('master.district.update', $district->pk) : route('master.district.store') }}"
    method="POST"
    id="districtForm"
    class="dst-modal-form"
    novalidate
    data-selected-state="{{ $statePk }}">
    @csrf

    <div class="mb-3">
        <label for="country_master_pk" class="form-label cgt-field-label mb-2">
            Country Name <span class="text-danger">*</span>
        </label>
        <select name="country_master_pk" id="country_master_pk" class="form-select rounded-2" required>
            <option value="" disabled {{ $countryPk === '' || $countryPk === null ? 'selected' : '' }}>Select Country</option>
            @foreach ($countries as $country)
            <option value="{{ $country->pk }}"
                {{ (string) $countryPk === (string) $country->pk ? 'selected' : '' }}>
                {{ $country->country_name }}
            </option>
            @endforeach
        </select>
        <small class="text-danger d-none dst-field-error" data-field="country_master_pk"></small>
    </div>

    <div class="mb-3">
        <label for="state" class="form-label cgt-field-label mb-2">
            State Name <span class="text-danger">*</span>
        </label>
        <select name="state_master_pk" id="state" class="form-select rounded-2" required>
            <option value="">Select State</option>
            @if ($isEdit || $statePk)
                @foreach ($states as $state)
                <option value="{{ $state->pk }}"
                    {{ (string) $statePk === (string) $state->pk ? 'selected' : '' }}>
                    {{ $state->state_name }}
                </option>
                @endforeach
            @endif
        </select>
        <small class="text-danger d-none dst-field-error" data-field="state_master_pk"></small>
    </div>

    <div class="mb-3">
        <label for="district_name" class="form-label cgt-field-label mb-2">
            District Name <span class="text-danger">*</span>
        </label>
        <input type="text"
            name="district_name"
            id="district_name"
            class="form-control rounded-2"
            placeholder="District Name"
            value="{{ $districtName }}"
            maxlength="100"
            autocomplete="off"
            required>
        <small class="text-danger d-none dst-field-error" data-field="district_name"></small>
    </div>

    <div class="mb-4">
        <label for="active_inactive" class="form-label cgt-field-label mb-2">
            Status <span class="text-danger">*</span>
        </label>
        <select name="active_inactive" id="active_inactive" class="form-select rounded-2" required>
            <option value="1" {{ (string) $statusValue === '1' ? 'selected' : '' }}>Active</option>
            <option value="2" {{ (string) $statusValue === '2' ? 'selected' : '' }}>Inactive</option>
        </select>
        <small class="text-danger d-none dst-field-error" data-field="active_inactive"></small>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary rounded-2 px-4" id="saveDistrictForm">
            {{ $isEdit ? 'Update District' : 'Add District' }}
        </button>
    </div>
</form>
