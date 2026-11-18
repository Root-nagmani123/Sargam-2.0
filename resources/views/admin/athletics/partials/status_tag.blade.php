{{-- UX4G tonal tag for any workflow state; an unknown value degrades to neutral. $status (string|int). --}}
@php
    $key = is_numeric($status) ? ((int) $status === 1 ? 'active' : 'inactive') : (string) $status;
    [$tone, $label] = [
        'active' => ['success', 'Active'],
        'inactive' => ['error', 'Inactive'],
        'draft' => ['warning', 'Draft'],
        'pending' => ['warning', 'Pending'],
        'approved' => ['success', 'Approved'],
        'rejected' => ['error', 'Rejected'],
        'complete' => ['success', 'Complete'],
    ][$key] ?? ['neutral', ucfirst($key)];
@endphp
<span class="ux4g-tag-tonal-{{ $tone }}">{{ $label }}</span>
