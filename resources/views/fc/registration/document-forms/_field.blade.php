{{-- Renders a single scalar form field. Expects: $f (schema array), $value --}}
@php
    $type     = $f['type'] ?? 'text';
    $name     = $f['name'];
    $required = ! empty($f['required']);
@endphp
<div class="{{ $f['width'] ?? 'col-md-6' }}">
    <label class="form-label small fw-semibold">
        {!! $f['label'] !!}
        @if($required)<span class="text-danger">*</span>@endif
    </label>

    @if($type === 'textarea')
        <textarea name="{{ $name }}" rows="2"
                  class="form-control @error($name) is-invalid @enderror"
                  {{ $required ? 'required' : '' }}>{{ $value }}</textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" class="form-select @error($name) is-invalid @enderror" {{ $required ? 'required' : '' }}>
            <option value="">— Select —</option>
            @foreach($f['options'] ?? [] as $opt)
                <option value="{{ $opt }}" {{ (string) $value === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ in_array($type, ['date','number','email']) ? $type : 'text' }}"
               name="{{ $name }}"
               class="form-control @error($name) is-invalid @enderror"
               value="{{ $value }}"
               {{ $required ? 'required' : '' }}>
    @endif

    @error($name)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>
