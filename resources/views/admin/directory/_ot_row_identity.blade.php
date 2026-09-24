{{-- Identity cell for the OT Directory grid: avatar + name over OT code.
     Rendered server-side into the DataTables feed (DirectoryController@otData),
     so it lives in its own partial rather than inline in the controller. --}}
@php
    // Initials for the avatar fallback: first letter of the first name and of
    // the last. display_name is a single free-text column, often with double
    // spaces, so split on any run of whitespace; one word yields one letter.
    $nameParts = preg_split('/\s+/', trim((string) $student->display_name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = '';
    if ($nameParts) {
        $initials = mb_substr($nameParts[0], 0, 1);
        if (count($nameParts) > 1) {
            $initials .= mb_substr($nameParts[count($nameParts) - 1], 0, 1);
        }
    }
    $initials = mb_strtoupper($initials) ?: '?';
@endphp
<div class="dir-identity">
    {{-- The initials are the avatar; the photo, when there is one, is layered
         over them and only revealed once it actually decodes. Plenty of
         student_master rows carry a photo_path whose file is missing, so an
         <img> that errors removes itself and the initials are what stays on
         screen. Decorative — the name is right beside it. --}}
    <span class="dir-avatar" aria-hidden="true">
        <span class="dir-avatar__initials">{{ $initials }}</span>
        @if(!empty($student->photo_path))
            <img src="{{ asset('storage/' . $student->photo_path) }}"
                 alt="" class="dir-avatar__img" loading="lazy" decoding="async"
                 onload="this.classList.add('is-loaded')"
                 onerror="this.remove()">
        @endif
    </span>
    <span class="dir-identity__text">
        <span class="dir-identity__name">{{ $student->display_name ?: '-' }}</span>
        <span class="dir-identity__code">{{ $student->generated_OT_code ?: '-' }}</span>
    </span>
</div>
