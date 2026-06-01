@php
    /** @var \App\Models\City|null $city */
    $isEdit = isset($city) && $city;
    $countryPk = old('country_master_pk', $isEdit ? $city->country_master_pk : '');
    $statePk = old('state_master_pk', $isEdit ? $city->state_master_pk : '');
    $districtPk = old('district_master_pk', $isEdit ? $city->district_master_pk : '');
    $cityName = old('city_name', $isEdit ? $city->city_name : '');
    $statusValue = old('active_inactive', $isEdit ? $city->active_inactive : 1);
@endphp

<form action="{{ $isEdit ? route('master.city.update', $city->pk) : route('master.city.store') }}"
    method="POST"
    id="cityForm"
    class="cty-modal-form"
    novalidate
    data-selected-state="{{ $statePk }}"
    data-selected-district="{{ $districtPk }}">
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
        <small class="text-danger d-none cty-field-error" data-field="country_master_pk"></small>
    </div>

    <div class="mb-3">
        <label for="state_master_pk" class="form-label cgt-field-label mb-2">
            State Name <span class="text-danger">*</span>
        </label>
        <select name="state_master_pk" id="state_master_pk" class="form-select rounded-2" required>
            <option value="">Select State</option>
            @if ($isEdit && $states->isNotEmpty())
                @foreach ($states as $state)
                <option value="{{ $state->pk }}"
                    {{ (string) $statePk === (string) $state->pk ? 'selected' : '' }}>
                    {{ $state->state_name }}
                </option>
                @endforeach
            @endif
        </select>
        <small class="text-danger d-none cty-field-error" data-field="state_master_pk"></small>
    </div>

    <div class="mb-3">
        <label for="district_master_pk" class="form-label cgt-field-label mb-2">
            District Name <span class="text-danger">*</span>
        </label>
        <select name="district_master_pk" id="district_master_pk" class="form-select rounded-2" required>
            <option value="">Select District</option>
            @if ($isEdit && isset($districts) && $districts->isNotEmpty())
                @foreach ($districts as $district)
                <option value="{{ $district->pk }}"
                    {{ (string) $districtPk === (string) $district->pk ? 'selected' : '' }}>
                    {{ $district->district_name }}
                </option>
                @endforeach
            @endif
        </select>
        <small class="text-danger d-none cty-field-error" data-field="district_master_pk"></small>
    </div>

    <div class="mb-3">
        <label for="city_name" class="form-label cgt-field-label mb-2">
            City Name <span class="text-danger">*</span>
        </label>
        @if ($isEdit)
        <input type="text"
            name="city_name"
            id="city_name"
            class="form-control rounded-2"
            placeholder="City Name"
            value="{{ $cityName }}"
            maxlength="100"
            autocomplete="off"
            required>
        @else
        <input type="text"
            name="city_name"
            id="city_name"
            class="form-control rounded-2"
            placeholder="eg. New Delhi"
            value="{{ $cityName }}"
            maxlength="100"
            autocomplete="off"
            required>
        @endif
        <small class="text-danger d-none cty-field-error" data-field="city_name"></small>
    </div>

    <div class="mb-4">
        <label for="active_inactive" class="form-label cgt-field-label mb-2">
            Status <span class="text-danger">*</span>
        </label>
        <select name="active_inactive" id="active_inactive" class="form-select rounded-2" required>
            <option value="1" {{ (string) $statusValue === '1' ? 'selected' : '' }}>Active</option>
            <option value="2" {{ (string) $statusValue === '2' ? 'selected' : '' }}>Inactive</option>
        </select>
        <small class="text-danger d-none cty-field-error" data-field="active_inactive"></small>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary rounded-2 px-4" id="saveCityForm">
            {{ $isEdit ? 'Update City' : 'Add City' }}
        </button>
    </div>
</form>
