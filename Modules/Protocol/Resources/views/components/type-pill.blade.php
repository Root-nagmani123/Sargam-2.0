@props(['type'])

@php
  $label = config("protocol.request_types.$type.label", $type);
  $icon = match($type) {
      'guesthouse' => 'bi-building',
      'vehicle' => 'bi-truck-front',
      'ticket' => 'bi-ticket-perforated',
      default => 'bi-file-earmark',
  };
@endphp

<span class="type-pill type-{{ $type === 'guesthouse' ? 'guesthouse' : ($type === 'vehicle' ? 'vehicle' : 'ticket') }}">
  <i class="bi {{ $icon }}"></i>{{ $label }}
</span>
