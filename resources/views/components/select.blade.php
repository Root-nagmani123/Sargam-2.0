
@if($label)
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
@endif

@php
    $formSelectClass = 'form-select' . ($errors->has($name) ? ' is-invalid' : '');
@endphp

<select 
    class="{{ $formSelectClass }}"
    id="{{ $name }}"
    name="{{ $name }}"
    @if($required) required @endif
    @if($multiple) multiple @endif
>
    <option value="">Select</option>
    @foreach($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" 
            {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
            {{ $optionLabel }}
        </option>
    @endforeach
</select>

@error($name)
    <span class="text-danger">{{ $message }}</span>
@enderror

