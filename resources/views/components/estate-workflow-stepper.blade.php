{{--
    Estate Workflow Stepper - All new entries follow this flow:
    1. Request For Estate → 2. Put In HAC → 3. HAC Approval → 4. Possession Details
    For HAC Person role: show Put In HAC → HAC Approval → Possession Details (no access to Request For Estate).
--}}
@props(['current' => 'request-for-estate'])
@php
    $isHacPersonOnly = hasRole('HAC Person') && !hasRole('Estate') && !hasRole('Admin') && !hasRole('Training-Induction') && !hasRole('Training-MCTP') && !hasRole('IST') && !hasRole('Staff') && !hasRole('Student-OT') && !hasRole('Doctor') && !hasRole('Guest Faculty') && !hasRole('Internal Faculty');
    // Authority users: Estate/Admin/Super Admin OR HAC Person.
    // Training roles must behave like normal staff (self-service), so they are NOT treated as privileged here.
    $isPrivilegedEstate = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');
    $estateSelfServiceRoles = hasRole('Staff')
        || hasRole('Student-OT')
        || hasRole('Doctor')
        || hasRole('Guest Faculty')
        || hasRole('Internal Faculty')
        || hasRole('Training-Induction')
        || hasRole('Training-MCTP')
        || hasRole('IST');
    $hasHacPersonRole = hasRole('HAC Person');

    $stages = [
        'request-for-estate' => ['label' => 'Request For Estate', 'route' => 'admin.estate.request-for-estate'],
        'put-in-hac'        => ['label' => 'Put In HAC', 'route' => 'admin.estate.put-in-hac'],
        'hac-approved'      => ['label' => 'HAC Approval', 'route' => 'admin.estate.change-request-hac-approved'],
        'possession-details'=> ['label' => 'Possession Details', 'route' => 'admin.estate.possession-details'],
    ];

    // Full workflow (all 4 buttons) for: Estate/Admin/Super Admin OR HAC Person (with or without Staff).
    if ($isPrivilegedEstate || $hasHacPersonRole) {
        // Keep all $stages – show Request For Estate, Put In HAC, HAC Approved, Possession Details.
    } elseif ($estateSelfServiceRoles) {
        // Self-service only (no HAC Person): show only Request For Estate and Possession Details.
        $stages = array_intersect_key($stages, array_flip(['request-for-estate', 'possession-details']));
    }

    // Home ?scope=self (Admin/Estate/Super Admin personal view): same as staff — no Put In HAC / HAC Approved here; use Setup → Estate for those.
    if (request('scope') === 'self' && $isPrivilegedEstate) {
        $stages = array_intersect_key($stages, array_flip(['request-for-estate', 'possession-details']));
    }

    // Home sidebar personal estate: keep ?scope=self on stepper links so tab stays on Home.
    $estateStepperRouteParams = (request('scope') === 'self'
        && (hasRole('Admin') || hasRole('Super Admin') || hasRole('Estate')))
        ? ['scope' => 'self']
        : [];
@endphp
<div class="estate-workflow-stepper mb-4" role="navigation" aria-label="Estate workflow flow">
    <div class="alert alert-light border rounded-3 mb-0 py-2 px-3">
        <p class="small text-muted mb-2"><i class="bi bi-arrow-repeat me-1"></i><strong>Workflow:</strong> All new entries follow this flow</p>
        <nav class="d-flex flex-wrap align-items-center gap-1 gap-sm-2">
            @foreach ($stages as $key => $stage)
                @if ($key === $current)
                    <span class="badge bg-primary px-2 py-1">{{ $stage['label'] }}</span>
                @else
                    <a href="{{ route($stage['route'], $estateStepperRouteParams) }}" class="badge bg-light text-dark border px-2 py-1 text-decoration-none">{{ $stage['label'] }}</a>
                @endif
                @if (!$loop->last)
                    <i class="bi bi-chevron-right text-muted small" aria-hidden="true"></i>
                @endif
            @endforeach
        </nav>
    </div>
</div>