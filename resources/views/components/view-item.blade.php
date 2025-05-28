@props(['label', 'value', 'isLink' => false])

<div class="col-12 col-md-6 mb-3">
    <div class="border rounded px-3 py-2 bg-white shadow-sm h-100">
        <div class="fw-semibold text-secondary small mb-1">
            {{ $label }}
        </div>
        <div class="text-dark">
            @if($isLink)
                <a href="{{ $value }}" target="_blank" class="text-primary text-decoration-underline">{{ $value }}</a>
            @else
                {{ $value ?? '-' }}
            @endif
        </div>
    </div>
</div>
