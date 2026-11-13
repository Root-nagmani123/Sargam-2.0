{{-- Identity cell for the LBSNAA Directory grid: avatar + name.
     Rendered server-side into the DataTables feed (DirectoryController@lbsnaaData).

     No sub-line here, unlike the OT grid: an employee's second identifier would
     be their Designation, and that is a column of its own on this grid. --}}
@php
    $fullName = trim(implode(' ', array_filter([
        $employee->first_name, $employee->middle_name, $employee->last_name,
    ])));

    // Initials for the avatar fallback: first letter of the first name and of the
    // last. Fall back to whatever single part exists when one of them is blank.
    $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
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
         employee_master rows carry a profile_picture whose file is missing, so an
         <img> that errors removes itself and the initials are what stays on
         screen. Decorative — the name is right beside it. --}}
    <span class="dir-avatar" aria-hidden="true">
        <span class="dir-avatar__initials">{{ $initials }}</span>
        @if(!empty($employee->profile_picture))
            <img src="{{ asset('storage/' . $employee->profile_picture) }}"
                 alt="" class="dir-avatar__img" loading="lazy" decoding="async"
                 onload="this.classList.add('is-loaded')"
                 onerror="this.remove()">
        @endif
    </span>
    <span class="dir-identity__text">
        <span class="dir-identity__name">{{ $fullName ?: '-' }}</span>
    </span>
</div>
