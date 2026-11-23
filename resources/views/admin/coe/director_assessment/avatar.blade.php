{{-- OT photo, or their initials when none is on record (or it fails to load). --}}
@if ($photo)
    <img src="{{ $photo }}" alt="" width="{{ $size }}" height="{{ $size }}" class="da-avatar" loading="lazy"
         onerror="this.replaceWith(Object.assign(document.createElement('span'), {className: 'da-avatar da-avatar--initials', textContent: this.dataset.initials, style: this.getAttribute('style')}))"
         data-initials="{{ $initials }}" style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * 0.38) }}px">
@else
    <span class="da-avatar da-avatar--initials" aria-hidden="true"
          style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * 0.38) }}px">{{ $initials }}</span>
@endif
