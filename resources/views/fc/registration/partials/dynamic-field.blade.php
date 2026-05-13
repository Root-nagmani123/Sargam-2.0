{{-- Dynamic field renderer - used by both FC forms and admin preview --}}
@php
    $fieldName   = $field->field_name;
    $fieldType   = $field->field_type;
    $value       = old($fieldName, $existingData->{$field->target_column ?? $fieldName} ?? $field->default_value ?? '');
    if ($fieldType === 'number' && $value !== '' && $value !== null) {
        $value = fc_numeric_display_value($value);
    }
    $isReadonly  = $readonly ?? false;
    $options     = $field->decoded_options ?? [];
    $lookupItems = $lookups[$fieldName] ?? [];
    $valCol      = $field->lookup_value_column ?? 'id';
    $lblCol      = $field->lookup_label_column ?? 'name';
@endphp

<label class="form-label small fw-semibold">
    {{ $field->label }}
    @if($field->is_required)<span class="text-danger">*</span>@endif
</label>

@switch($fieldType)
    @case('text')
    @case('email')
    @case('number')
    @case('date')
        <input type="{{ $fieldType }}"
               name="{{ $fieldName }}"
               class="form-control @error($fieldName) is-invalid @enderror"
               value="{{ $value }}"
               placeholder="{{ $field->placeholder ?? '' }}"
               {{ $field->is_required ? 'required' : '' }}
               {{ $isReadonly ? 'disabled' : '' }}>
        @break

    @case('textarea')
        <textarea name="{{ $fieldName }}"
                  class="form-control @error($fieldName) is-invalid @enderror"
                  rows="3"
                  placeholder="{{ $field->placeholder ?? '' }}"
                  {{ $field->is_required ? 'required' : '' }}
                  {{ $isReadonly ? 'disabled' : '' }}>{{ $value }}</textarea>
        @break

    @case('select')
        <select name="{{ $fieldName }}"
                class="form-select @error($fieldName) is-invalid @enderror"
                {{ $field->is_required ? 'required' : '' }}
                {{ $isReadonly ? 'disabled' : '' }}>
            <option value="">-- Select --</option>
            @if($field->lookup_table && count($lookupItems) > 0)
                @foreach($lookupItems as $item)
                    <option value="{{ $item->{$valCol} }}" {{ (string)$value === (string)$item->{$valCol} ? 'selected' : '' }}>
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
            <div class="form-check mt-1">
                <input class="form-check-input @error($fieldName) is-invalid @enderror" type="checkbox"
                       name="{{ $fieldName }}" value="1"
                       id="fc_cb_single_{{ $field->id }}"
                       {{ fc_checkbox_single_checked($value) ? 'checked' : '' }}
                       {{ $isReadonly ? 'disabled' : '' }}>
                <label class="form-check-label small" for="fc_cb_single_{{ $field->id }}">{{ $field->label }}</label>
            </div>
        @endif
        @break

    @case('file')
        <input type="file"
               name="{{ $fieldName }}"
               class="form-control @error($fieldName) is-invalid @enderror"
               accept="{{ $field->file_extensions ? '.' . implode(',.', explode(',', $field->file_extensions)) : '' }}"
               {{ $isReadonly ? 'disabled' : '' }}>
        @if($value && !$isReadonly)
            <small class="text-success"><i class="bi bi-check-circle"></i> File uploaded: {{ basename($value) }}</small>
        @endif
        @break

    @case('hidden')
        <input type="hidden" name="{{ $fieldName }}" value="{{ $value }}">
        <small class="text-muted">[Hidden field: {{ $fieldName }}]</small>
        @break
@endswitch

@error($fieldName)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror

@if($field->help_text)
    <small class="text-muted d-block mt-1">{{ $field->help_text }}</small>
@endif
