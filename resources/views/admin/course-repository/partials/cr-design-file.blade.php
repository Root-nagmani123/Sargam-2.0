{{-- Design-system file control: "Choose file" | "No file chosen" (IDs/names unchanged on input) --}}
@php
    $inputId = $inputId ?? 'file';
    $inputName = $inputName ?? 'file';
    $required = !empty($required);
    $accept = $accept ?? '';
    $multiple = !empty($multiple);
    $inputClass = trim('cr-design-file-input ' . ($inputClass ?? ''));
@endphp
<div class="cr-design-file">
    <label for="{{ $inputId }}" class="cr-design-file-label mb-0">
        <span class="cr-design-file-btn">Choose file</span>
        <span class="cr-design-file-name" id="{{ $inputId }}_label">No file chosen</span>
        <input type="file"
               class="{{ $inputClass }}"
               id="{{ $inputId }}"
               name="{{ $inputName }}"
               @if($accept) accept="{{ $accept }}" @endif
               @if($required) required @endif
               @if($multiple) multiple @endif>
    </label>
</div>
