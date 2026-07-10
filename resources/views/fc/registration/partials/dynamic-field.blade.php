{{-- Dynamic field renderer - used by both FC forms and admin preview --}}
@php
    $fieldName   = $field->field_name;
    $fieldType   = $field->field_type;
    $value       = old($fieldName, $existingData->{$field->target_column ?? $fieldName} ?? $field->default_value ?? '');
    // Academy-provided (first Excel upload) identity fields: prefilled + locked.
    $lockedFields = $lockedFields ?? [];
    $isLocked     = array_key_exists($fieldName, $lockedFields);
    if ($isLocked) {
        // The imported value always wins over old()/existing/default.
        $value = $lockedFields[$fieldName];
    }
    // Editable server suggestion (e.g. transliterated Hindi name): fill only when empty.
    $prefillValues = $prefillValues ?? [];
    $isPrefilled   = false;
    if (! $isLocked && ($value === '' || $value === null) && array_key_exists($fieldName, $prefillValues)) {
        $value = $prefillValues[$fieldName];
        $isPrefilled = ($value !== '' && $value !== null);
    }
    if ($fieldType === 'number' && $value !== '' && $value !== null) {
        $value = fc_numeric_display_value($value);
    }
    $isReadonly  = $readonly ?? false;
    // Text-like inputs use `readonly` so the value still submits; choice controls
    // (select/radio/checkbox/file) can't be readonly, so they are `disabled`.
    $inputLockAttr  = $isLocked ? 'readonly' : ($isReadonly ? 'disabled' : '');
    $choiceLockAttr = ($isLocked || $isReadonly) ? 'disabled' : '';
    $options     = $field->decoded_options ?? [];
    $lookupItems = $lookups[$fieldName] ?? [];
    $valCol      = $field->lookup_value_column ?? 'id';
    $lblCol      = $field->lookup_label_column ?? 'name';
    $isDistrictField = ($field->lookup_table ?? '') === 'state_district_mapping'
        || str_ends_with($fieldName, '_district')
        || str_contains(strtolower((string) $field->label), 'district');
    $districtStatePairs = [
        'perm_district' => 'perm_state_id',
        'pres_district' => 'pres_state_id',
        'birth_district' => 'birth_state_id',
        'matric_district' => 'matric_state_id',
        'domicile_district' => 'domicile_state_id',
    ];
    $pairedStateField = $districtStatePairs[$fieldName]
        ?? (str_ends_with($fieldName, '_district') ? str_replace('_district', '_state_id', $fieldName) : null);
    $pairedCountryField = str_replace('_district', '_country_id', str_replace('_state_id', '_country_id', $pairedStateField));
    if (str_ends_with($fieldName, '_state_id')) {
        $pairedCountryField = str_replace('_state_id', '_country_id', $fieldName);
    }
    $isStateLookup = $fieldType === 'select'
        && $field->lookup_table
        && str_contains(strtolower((string) $field->lookup_table), 'state');
    $isCountryLookup = $fieldType === 'select'
        && $field->lookup_table
        && str_contains(strtolower((string) $field->lookup_table), 'country');
    $districtRows = $districtOptions ?? collect();
    $panHelp = 'Format: ABCDE1234F (5 uppercase letters, 4 digits, 1 uppercase letter)';
    $isPanField = $fieldName === 'pan_card'
        || str_contains(strtolower((string) $field->label), 'pan');
    $isPassportField = $fieldName === 'passport_no'
        || str_contains(strtolower((string) $field->label), 'passport');
    $passportHelp = 'Passport number must be 8–9 characters.';
    $textInputMode = match ($fieldType) {
        'number' => 'decimal',
        'email' => 'email',
        default => 'text',
    };
    $textPattern = null;
    if ($fieldType === 'number') {
        $textPattern = '[0-9]*';
    } elseif (in_array($fieldType, ['text', 'textarea'], true)
        && ! preg_match('/\b(regex|alpha_num|alpha_dash|digits|numeric|integer)\b/', (string) ($field->validation_rules ?? ''))) {
        $textPattern = '.*[^\d].*|^$';
    }
    if (preg_match('/\balpha_num\b/', (string) ($field->validation_rules ?? ''))) {
        $textPattern = '[A-Za-z0-9]*';
    }
@endphp

<label class="form-label small fw-semibold">
    {{ $field->label }}
    @if($field->is_required)<span class="text-danger">*</span>@endif
</label>

@if($isDistrictField)
    <select name="{{ $fieldName }}"
            class="form-select fc-district-select @error($fieldName) is-invalid @enderror"
            @if($pairedStateField) data-fc-state-field="{{ $pairedStateField }}" @endif
            @if($field->is_required) data-fc-required="1" aria-required="true" @endif
            {{ $isReadonly ? 'disabled' : '' }}>
        <option value="">-- Select District --</option>
        @foreach($districtRows as $item)
            <option value="{{ $item->district_name }}"
                    data-state-id="{{ $item->state_master_pk }}"
                    @if(isset($item->country_master_pk)) data-country-id="{{ $item->country_master_pk }}" @endif
                    {{ (string) $value === (string) $item->district_name ? 'selected' : '' }}>
                {{ $item->district_name }}
            </option>
        @endforeach
    </select>
@else
@switch($fieldType)
    @case('text')
    @case('email')
    @case('number')
    @case('date')
        <input type="{{ $fieldType === 'number' ? 'text' : $fieldType }}"
               name="{{ $fieldName }}"
               class="form-control @error($fieldName) is-invalid @enderror @if($isLocked) bg-light @endif"
               value="{{ $value }}"
               placeholder="{{ $field->placeholder ?? '' }}"
               inputmode="{{ $textInputMode }}"
               @if($textPattern) pattern="{{ $textPattern }}" @endif
               @if($fieldType === 'date' && in_array(strtolower((string) ($field->target_column ?? $fieldName)), ['date_of_birth', 'dob'], true))
                   max="{{ now()->subYears(15)->format('Y-m-d') }}"
               @endif
               @if($isPanField) style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase()" @endif
               @if($isPassportField) minlength="8" maxlength="9" @endif
               @if($field->is_required) data-fc-required="1" aria-required="true" @endif
               {{ $inputLockAttr }}>
        @if($isPanField)
            <div class="form-text text-muted">{{ $panHelp }}</div>
        @endif
        @if($isPassportField)
            <div class="form-text text-muted">{{ $passportHelp }}</div>
        @endif
        @break

    @case('textarea')
        <textarea name="{{ $fieldName }}"
                  class="form-control @error($fieldName) is-invalid @enderror @if($isLocked) bg-light @endif"
                  rows="3"
                  placeholder="{{ $field->placeholder ?? '' }}"
                  inputmode="{{ $textInputMode }}"
                  @if($textPattern) pattern="{{ $textPattern }}" @endif
                  @if($field->is_required) data-fc-required="1" aria-required="true" @endif
                  {{ $inputLockAttr }}>{{ $value }}</textarea>
        @break

    @case('select')
        @if($isLocked)
            {{-- A disabled <select> is not submitted; mirror the locked value in a hidden input. --}}
            <input type="hidden" name="{{ $fieldName }}" value="{{ $value }}">
        @endif
        <select @if(! $isLocked) name="{{ $fieldName }}" @endif
                class="form-select @error($fieldName) is-invalid @enderror @if($isLocked) bg-light @endif
                    @if($isStateLookup) fc-state-select @endif
                    @if($isCountryLookup) fc-country-select @endif"
                @if($isStateLookup && $pairedCountryField) data-fc-country-field="{{ $pairedCountryField }}" @endif
                @if($field->is_required && ! $isLocked) data-fc-required="1" aria-required="true" @endif
                {{ $choiceLockAttr }}>
            <option value="">-- Select --</option>
            @if($field->lookup_table && count($lookupItems) > 0)
                @foreach($lookupItems as $item)
                    <option value="{{ $item->{$valCol} }}"
                            @if($isStateLookup && isset($item->country_master_pk))
                                data-country-id="{{ $item->country_master_pk }}"
                            @endif
                            {{ (string)$value === (string)$item->{$valCol} ? 'selected' : '' }}>
                        {{ $item->{$lblCol} }}
                    </option>
                @endforeach
            @elseif(count($options) > 0)
                @foreach($options as $opt)
                    <option value="{{ $opt['value'] }}" {{ (string)$value === (string)$opt['value'] ? 'selected' : '' }}>
                        {{ $opt['label'] }}
                    </option>
                @endforeach
            @endif
        </select>
        @break

    @case('radio')
        <div class="d-flex flex-wrap gap-3 mt-1">
            @foreach($options as $opt)
                <div class="form-check">
                    <input class="form-check-input @error($fieldName) is-invalid @enderror" type="radio"
                           name="{{ $fieldName }}" value="{{ $opt['value'] }}"
                           id="{{ $fieldName }}_{{ $opt['value'] }}"
                           {{ (string)$value === (string)$opt['value'] ? 'checked' : '' }}
                           {{ $isReadonly ? 'disabled' : '' }}>
                    <label class="form-check-label small" for="{{ $fieldName }}_{{ $opt['value'] }}">{{ $opt['label'] }}</label>
                </div>
            @endforeach
        </div>
        @break

    @case('checkbox')
        @if(count($options) > 0)
            @php
                $stored = old($fieldName, $existingData->{$field->target_column ?? $fieldName} ?? null);
                $selectedVals = is_array($stored)
                    ? array_map('strval', $stored)
                    : fc_checkbox_multi_selected($stored, $options);
            @endphp
            <div class="d-flex flex-wrap gap-3 mt-1">
                @foreach($options as $i => $opt)
                    @php
                        $optVal = (string) ($opt['value'] ?? '');
                        $optLbl = (string) ($opt['label'] ?? $optVal);
                    @endphp
                    <div class="form-check">
                        <input class="form-check-input @error($fieldName) is-invalid @enderror" type="checkbox"
                               name="{{ $fieldName }}[]" value="{{ $optVal }}"
                               id="fc_cb_{{ $field->id }}_{{ $i }}"
                               {{ in_array($optVal, $selectedVals, true) ? 'checked' : '' }}
                               {{ $isReadonly ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="fc_cb_{{ $field->id }}_{{ $i }}">{{ $optLbl }}</label>
                    </div>
                @endforeach
            </div>
        @else
            @php
                $singleChecked = fc_checkbox_single_checked($value);
                // "Same as above" is not persisted (target _skip). On a fresh load, restore its
                // checked state by inferring it from saved data: all present fields equal permanent.
                if ($fieldName === 'same_as_permanent' && ! $singleChecked
                    && ! session()->hasOldInput() && ! empty($existingData)) {
                    $eq = fn ($a, $b) => (string) ($a ?? '') === (string) ($b ?? '');
                    $singleChecked = filled($existingData->perm_state_id ?? null)
                        && $eq($existingData->pres_address_line1 ?? null, $existingData->perm_address_line1 ?? null)
                        && $eq($existingData->pres_address_line2 ?? null, $existingData->perm_address_line2 ?? null)
                        && $eq($existingData->pres_country_id ?? null, $existingData->perm_country_id ?? null)
                        && $eq($existingData->pres_state_id ?? null, $existingData->perm_state_id ?? null)
                        && $eq($existingData->pres_district ?? null, $existingData->perm_district ?? null)
                        && $eq($existingData->pres_city ?? null, $existingData->perm_city ?? null)
                        && $eq($existingData->pres_city_name ?? null, $existingData->perm_city_name ?? null)
                        && $eq($existingData->pres_pincode ?? null, $existingData->perm_pincode ?? null);
                }
            @endphp
            <div class="form-check mt-1">
                <input class="form-check-input @error($fieldName) is-invalid @enderror" type="checkbox"
                       name="{{ $fieldName }}" value="1"
                       id="{{ $fieldName === 'same_as_permanent' ? 'same_as_permanent' : 'fc_cb_single_'.$field->id }}"
                       {{ $singleChecked ? 'checked' : '' }}
                       {{ $isReadonly ? 'disabled' : '' }}>
                <label class="form-check-label small" for="{{ $fieldName === 'same_as_permanent' ? 'same_as_permanent' : 'fc_cb_single_'.$field->id }}">{{ $field->label }}</label>
            </div>
        @endif
        @break

    @case('file')
        @php
            $fileMaxKb = (int) ($field->file_max_kb ?? 0);
            if ($fileMaxKb <= 0 && preg_match('/max:(\d+)/', (string) ($field->validation_rules ?? ''), $m)) {
                $fileMaxKb = (int) $m[1];
            }
            if ($fileMaxKb <= 0) {
                $fileMaxKb = 5120;
            }
            $acceptExts = $field->file_extensions ?: 'jpeg,jpg,png,pdf';
            $fileHint = $field->help_text ?: fc_file_upload_hint($field->validation_rules ?? null, $fileMaxKb);
        @endphp
        <input type="file"
               name="{{ $fieldName }}"
               class="form-control fc-file-upload @error($fieldName) is-invalid @enderror"
               accept="{{ '.' . implode(',.', array_map('trim', explode(',', $acceptExts))) }}"
               data-max-kb="{{ $fileMaxKb }}"
               data-accept-ext="{{ $acceptExts }}"
               @if($field->is_required && ! ($value && ! $isReadonly)) data-fc-required="1" aria-required="true" @endif
               {{ $isReadonly ? 'disabled' : '' }}>
        <div class="form-text text-muted">{{ $fileHint }}</div>
        @if($value && !$isReadonly)
            @php $uploadedFileUrl = view_file_link($value); @endphp
            <small class="text-success d-block mt-1">
                <i class="bi bi-check-circle"></i> File uploaded: {{ basename($value) }}
                @if($uploadedFileUrl)
                    <a href="{{ $uploadedFileUrl }}" target="_blank" rel="noopener" class="ms-2 text-decoration-none">
                        <i class="bi bi-eye"></i> Preview
                    </a>
                @endif
            </small>
            <input type="hidden" name="{{ $fieldName }}_existing" value="1">
        @endif
        @break

    @case('hidden')
        <input type="hidden" name="{{ $fieldName }}" value="{{ $value }}">
        <small class="text-muted">[Hidden field: {{ $fieldName }}]</small>
        @break
@endswitch
@endif

@error($fieldName)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror

@if($isLocked)
    <small class="text-muted d-block mt-1"><i class="bi bi-lock-fill"></i> Provided by the academy — not editable.</small>
@endif

@if($isPrefilled)
    <small class="text-muted d-block mt-1"><i class="bi bi-translate"></i> Auto-filled from your name — please verify the Hindi spelling.</small>
@endif

@if($field->help_text && ! $isPanField && $fieldType !== 'file')
    <small class="text-muted d-block mt-1">{{ $field->help_text }}</small>
@endif
