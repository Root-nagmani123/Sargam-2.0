@if($label)
    <label for="{{ $name }}" class="{{ $formLabelClass }}">
        {{ $label }}
    </label>
@endif
@php
    $formInputClass = 'form-control' . ($errors->has($name) ? ' is-invalid' : '') . ' ' . ($formInputClass ?? '');
@endphp


<input type="file" 
    class="form-control" 
    id="researchpublications" 
    name="researchpublications"
    placeholder="Research Publications " 
    required
    accept=".pdf, .doc, .docx"
    {{ $attributes->merge(['class' => $formInputClass]) }}


@if($helperSmallText)
    <small class="form-text text-muted">
        {{ $helperSmallText }}
    </small>
@endif