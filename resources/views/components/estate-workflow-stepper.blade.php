{{--
    Estate Workflow Stepper - All new entries follow this flow:
    1. Request For Estate → 2. Put In HAC → 3. HAC Approved → 4. Possession Details
    For HAC Person role: show Put In HAC → HAC Approved → Possession Details (no access to Request For Estate).
--}}
@props(['current' => 'request-for-estate'])
@php
    $isHacPersonOnly = hasRole('HAC Person') && !hasRole('Estate') && !hasRole('Admin') && !hasRole('Training-Induction') && !hasRole('Training-MCTP') && !hasRole('IST') && !hasRole('Staff') && !hasRole('Student-OT') && !hasRole('Doctor') && !hasRole('Guest Faculty') && !hasRole('Internal Faculty');
    $stages = [
        'request-for-estate' => ['label' => 'Request For Estate', 'route' => 'admin.estate.request-for-estate'],
        'put-in-hac'        => ['label' => 'Put In HAC', 'route' => 'admin.estate.put-in-hac'],
        'hac-approved'      => ['label' => 'HAC Approved', 'route' => 'admin.estate.change-request-hac-approved'],
        'possession-details'=> ['label' => 'Possession Details', 'route' => 'admin.estate.possession-details'],
    ];
    if ($isHacPersonOnly) {
        // HAC users see HAC flow steps, including Possession Details, but not Request For Estate.
        $stages = array_intersect_key($stages, array_flip(['put-in-hac', 'hac-approved', 'possession-details']));
    }
@endphp
<div class="estate-workflow-stepper mb-4" role="navigation" aria-label="Estate workflow flow">
    <div class="alert alert-light border rounded-3 mb-0 py-2 px-3">
        <p class="small text-muted mb-2"><i class="bi bi-arrow-repeat me-1"></i><strong>Workflow:</strong> {{ $isHacPersonOnly ? 'HAC flow' : 'All new entries follow this flow' }}</p>
        <nav class="d-flex flex-wrap align-items-center gap-1 gap-sm-2">
            @foreach ($stages as $key => $stage)
                @if ($key === $current)
                    <span class="badge bg-primary px-2 py-1">{{ $stage['label'] }}</span>
                @else
                    <a href="{{ route($stage['route']) }}" class="badge bg-light text-dark border px-2 py-1 text-decoration-none">{{ $stage['label'] }}</a>
                @endif
                @if (!$loop->last)
                    <i class="bi bi-chevron-right text-muted small" aria-hidden="true"></i>
                @endif
            @endforeach
        </nav>
    </div>
</div>
