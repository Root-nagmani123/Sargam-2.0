@php
    // Help and error text are tied to the field (WCAG 1.3.1 / 3.3.1); the asterisk follows the same
    // truthiness as the `required` attribute below, so the visible marker matches what is enforced.
    $hasError = $errors->has($name);
    $describedBy = trim(($helperSmallText ? $name . '-help ' : '') . ($hasError ? $name . '-error' : ''));
@endphp
@if($label)
    <label for="{{ $name }}" class="{{ $formLabelClass }}">
        {{ $label }}@if($labelRequired || $required) <span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
@endif

@php
    $formInputClass = 'form-control' . ($hasError ? ' is-invalid' : '') . ' ' . ($formInputClass ?? '');
@endphp
<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name }}"
    value="{{ old($name, $value) }}"
    {{ $attributes->merge(['class' => $formInputClass]) }}
    @if($required) required @endif
    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
    @if($hasError) aria-invalid="true" @endif
>

@if($helperSmallText)
    <small class="form-text text-muted" id="{{ $name }}-help">
        {{ $helperSmallText }}
    </small>
@endif

@error($name)
    <span class="text-danger" id="{{ $name }}-error">{{ $message }}</span>
@enderror
