@if($label)
    <label for="{{ $name }}" class="{{ $formLabelClass }}">
        {{ $label }}
    </label>
@endif

@php
    $formInputClass = 'form-control' . ($errors->has($name) ? ' is-invalid' : '');
@endphp
<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name }}"
    value="{{ old($name, $value) }}"
    {{ $attributes->merge(['class' => $formInputClass]) }}
    @if($required) required @endif
    
>

@error($name)
    <span class="text-danger">{{ $message }}</span>
@enderror
