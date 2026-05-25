
@if($label)
    <label class="{{ $formLabelClass ?? 'form-label' }}" for="{{ $id ?? $name }}">{{ $label }} <span class="text-danger">{{ $labelRequired ? '*' : '' }}</span></label>
@endif

@php
    $formSelectClass = 'form-select' . ($errors->has($name) ? ' is-invalid' : ''). ' ' . $formSelectClass;
@endphp

<select 
    class="{{ $formSelectClass }}"
    id="{{ $id ?? $name }}"
    name="{{ $name }}"
    @if($required) required @endif
    @if($multiple) multiple @endif
>
    <option value="">{{ $placeholder }}</option>
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
    <span class="text-danger">{{ $message }}</span>
@enderror

