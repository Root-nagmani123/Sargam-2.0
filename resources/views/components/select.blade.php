
@php
    // Same accessibility contract as x-input: visible required marker, error tied to the control.
    $selectId = $id ?? $name;
    $hasError = $errors->has($name);
@endphp
@if($label)
    <label class="form-label" for="{{ $selectId }}">{{ $label }}@if($labelRequired || $required) <span class="text-danger" aria-hidden="true">*</span>@endif</label>
@endif

@php
    $formSelectClass = 'form-select' . ($errors->has($name) ? ' is-invalid' : ''). ' ' . $formSelectClass;
@endphp

<select 
    class="{{ $formSelectClass }}"
    id="{{ $selectId }}"
    name="{{ $name }}"
    @if($required) required @endif
    @if($hasError) aria-invalid="true" aria-describedby="{{ $selectId }}-error" @endif
    @if($multiple) multiple @endif
>
    {{-- Callers that pass a placeholder get it here; everyone else keeps "Select". --}}
    <option value="">{{ filled($placeholder) ? $placeholder : 'Select' }}</option>
    @foreach($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" 
            @if($multiple) 
                {{ in_array($optionValue, old($name, $value)) ? 'selected' : '' }}
            @else
                {{ old($name, $value) == $optionValue ? 'selected' : '' }}
            @endif
        >
            {{ $optionLabel }}
        </option>
    @endforeach
</select>

@error($name)
    <span class="text-danger" id="{{ $selectId }}-error">{{ $message }}</span>
@enderror

