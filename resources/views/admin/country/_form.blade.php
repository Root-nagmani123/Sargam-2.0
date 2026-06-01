@php
    /** @var \App\Models\Country|null $country */
    $isEdit = isset($country) && $country;
    $statusValue = old('active_inactive', $country->active_inactive ?? 1);
    $countryNameValue = old('country_name', $isEdit ? $country->country_name : '');
    $presetNames = $countryNameOptions ?? [
        'India', 'United States', 'China', 'United Kingdom', 'Canada', 'Australia',
        'Germany', 'France', 'Japan', 'Brazil', 'Russia', 'South Africa',
        'Nepal', 'Bangladesh', 'Sri Lanka', 'Pakistan', 'Afghanistan', 'Bhutan',
    ];
    if ($isEdit && $countryNameValue && !in_array($countryNameValue, $presetNames, true)) {
        array_unshift($presetNames, $countryNameValue);
    }
@endphp

<form action="{{ $isEdit ? route('master.country.update', $country->pk) : route('master.country.store') }}"
    method="POST"
    id="countryForm"
    class="cty-modal-form"
    novalidate>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="country_name" class="form-label cgt-field-label mb-2">
            Country Name <span class="text-danger">*</span>
        </label>
        @if ($isEdit)
        <input type="text"
            name="country_name"
            id="country_name"
            class="form-control rounded-2"
            placeholder="Country Name"
            value="{{ $countryNameValue }}"
            maxlength="100"
            autocomplete="off"
            required>
        @else
        <select name="country_name" id="country_name" class="form-select rounded-2" required>
            <option value="" disabled {{ $countryNameValue === '' ? 'selected' : '' }}>Select Country</option>
            @foreach ($presetNames as $name)
            <option value="{{ $name }}" {{ (string) $countryNameValue === (string) $name ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        @endif
        <small class="text-danger d-none cty-field-error" data-field="country_name"></small>
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
        <button type="submit" class="btn btn-primary rounded-2 px-4" id="saveCountryForm">
            {{ $isEdit ? 'Update Country' : 'Add Country' }}
        </button>
    </div>
</form>
