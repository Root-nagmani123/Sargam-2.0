@props([
    'photo' => null,
    'name' => '',
    'colorClass' => 'text-bg-primary',
])

@php
    $displayName = trim((string) $name);
    $initial = $displayName !== ''
        ? mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'))
        : '?';
@endphp

<div {{ $attributes->merge(['class' => 'dashboard-avatar-wrap flex-shrink-0']) }}>
    @if(!empty($photo))
        <img src="{{ $photo }}" alt="" width="40" height="40"
            class="rounded-circle object-fit-cover dashboard-avatar dashboard-avatar-img"
            onerror="this.classList.add('d-none'); this.nextElementSibling?.classList.remove('d-none');">
        <div class="rounded-circle {{ $colorClass }} fw-semibold d-none d-inline-flex align-items-center justify-content-center dashboard-avatar dashboard-avatar-initial"
            aria-hidden="true">{{ $initial }}</div>
    @else
        <div class="rounded-circle {{ $colorClass }} fw-semibold d-inline-flex align-items-center justify-content-center dashboard-avatar dashboard-avatar-initial"
            aria-hidden="true">{{ $initial }}</div>
    @endif
</div>
