@props(['status'])

@php
  $labels = config('protocol.statuses');
@endphp

<span class="badge-status badge-{{ $status }}">{{ $labels[$status] ?? ucfirst($status) }}</span>
