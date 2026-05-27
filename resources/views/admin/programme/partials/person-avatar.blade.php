@php
    $personName = $name ?? 'User';
    $nameParts = preg_split('/\s+/', trim($personName));
    $firstName = $nameParts[0] ?? '';
    $initial = $firstName !== '' ? mb_strtoupper(mb_substr($firstName, 0, 1)) : '?';
    $sizeClass = $size ?? 'programme-person-avatar--md';
    $photoPath = $photo ?? null;
    $hasPhoto = filled($photoPath) && $photoPath !== 'default-profile.jpg';
    $photoUrl = $hasPhoto ? asset('storage/' . $photoPath) : '';
@endphp
<div class="programme-person-avatar {{ $sizeClass }}{{ $hasPhoto ? '' : ' is-fallback' }}"
    data-person-name="{{ $personName }}">
    @if($hasPhoto)
    <img src="{{ $photoUrl }}"
        alt="Photo of {{ $personName }}"
        class="programme-person-avatar__img rounded-circle"
        loading="lazy"
        decoding="async">
    @endif
    <span class="programme-person-avatar__fallback rounded-circle" aria-hidden="true">{{ $initial }}</span>
</div>
