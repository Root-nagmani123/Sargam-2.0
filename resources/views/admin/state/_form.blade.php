@php
    /** @var \App\Models\State|null $state */
    $isEdit = isset($state) && $state;
    $statusValue = old('active_inactive', $state->active_inactive ?? 1);
    $countryPk = old('country_master_pk', $isEdit ? $state->country_master_pk : '');
    $stateNameValue = old('state_name', $isEdit ? $state->state_name : '');
    $presetStateNames = $stateNameOptions ?? [];
    if ($isEdit && $stateNameValue && !in_array($stateNameValue, $presetStateNames, true)) {
        array_unshift($presetStateNames, $stateNameValue);
    }
@endphp

<form action="{{ $isEdit ? route('master.state.update', $state->pk) : route('master.state.store') }}"
    method="POST"
    id="stateForm"
    class="stt-modal-form"
    novalidate>
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
        <small class="text-danger d-none stt-field-error" data-field="country_master_pk"></small>
    </div>

    <div class="mb-3">
        <label for="state_name" class="form-label cgt-field-label mb-2">
            State Name <span class="text-danger">*</span>
        </label>
        @if ($isEdit)
        <input type="text"
            name="state_name"
            id="state_name"
            class="form-control rounded-2"
            placeholder="State Name"
            value="{{ $stateNameValue }}"
            maxlength="255"
            autocomplete="off"
            required>
        @else
        <select name="state_name" id="state_name" class="form-select rounded-2" required>
            <option value="" disabled {{ $stateNameValue === '' ? 'selected' : '' }}>Select State</option>
            @foreach ($presetStateNames as $name)
            <option value="{{ $name }}" {{ (string) $stateNameValue === (string) $name ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        @endif
        <small class="text-danger d-none stt-field-error" data-field="state_name"></small>
    </div>

    <div class="mb-4">
        <label for="active_inactive" class="form-label cgt-field-label mb-2">
            Status <span class="text-danger">*</span>
        </label>
        <select name="active_inactive" id="active_inactive" class="form-select rounded-2" required>
            <option value="1" {{ (string) $statusValue === '1' ? 'selected' : '' }}>Active</option>
            <option value="2" {{ (string) $statusValue === '2' ? 'selected' : '' }}>Inactive</option>
        </select>
        <small class="text-danger d-none stt-field-error" data-field="active_inactive"></small>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary rounded-2 px-4" id="saveStateForm">
            {{ $isEdit ? 'Update State' : 'Add State' }}
        </button>
    </div>
</form>
