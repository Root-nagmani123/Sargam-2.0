@if($label)
    <label for="{{ $name }}" class="{{ $formLabelClass }}">
        {{ $label }} <span class="text-danger">{{ $labelRequired ? '*' : '' }}</span>
        
    </label>
@endif

@php
    $formInputClass = 'form-control' . ($errors->has($name) ? ' is-invalid' : '') . ' ' . ($formInputClass ?? '');
@endphp
<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name }}"
    value="{{ old($name, $value) }}"
    {{ $attributes->merge(['class' => $formInputClass]) }}
    @if($required) required @endif
    
>

@if($helperSmallText)
    <small class="form-text text-muted">
        {{ $helperSmallText }}
    </small>
@endif

@error($name)
    <span class="text-danger">{{ $message }}</span>
@enderror
