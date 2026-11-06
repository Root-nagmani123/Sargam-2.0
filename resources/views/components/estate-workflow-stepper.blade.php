{{--
    Estate Workflow Stepper - All new entries follow this flow:
    1. Request For Estate → 2. Put In HAC → 3. HAC Approval → 4. Possession Details
    For HAC Person role: show Put In HAC → HAC Approval → Possession Details (no access to Request For Estate).
--}}
@props(['current' => 'request-for-estate'])
@php
    $isHacPersonOnly = hasRole('HAC Person') && !hasRole('Estate') && !hasRole('Super Admin') && !hasRole('Training Induction Admin') && !hasRole('Training MCTP Admin') && !hasRole('Training IST') && !hasRole('Staff') && !hasRole('Officer Trainee') && !hasRole('Doctor') && !hasRole('Guest Faculty') && !hasRole('Internal Faculty');
    // Authority users: Estate/Admin/Super Admin OR HAC Person.
    // Training roles must behave like normal staff (self-service), so they are NOT treated as privileged here.
    $isPrivilegedEstate = hasRole('Estate') || hasRole('Super Admin') || hasRole('Super Admin');
    $estateSelfServiceRoles = hasRole('Staff')
        || hasRole('Officer Trainee')
        || hasRole('Doctor')
        || hasRole('Guest Faculty')
        || hasRole('Internal Faculty')
        || hasRole('Training Induction Admin')
        || hasRole('Training MCTP Admin')
        || hasRole('Training IST');
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
        && (hasRole('Super Admin') || hasRole('Super Admin') || hasRole('Estate')))
        ? ['scope' => 'self']
        : [];
@endphp
{{-- Figma "Workflow for New Entries" card: caption over a hairline, then one pill
     per stage (current = brand tint) joined by arrow_forward_ios chevrons. --}}
<nav class="estate-workflow-stepper mb-3" aria-label="Workflow for new entries">
    <p class="estate-workflow-stepper__caption">Workflow for New Entries</p>
    <ol class="estate-workflow-stepper__steps">
        @foreach ($stages as $key => $stage)
            <li class="estate-workflow-stepper__item">
                @if ($key === $current)
                    <span class="estate-workflow-stepper__step is-current" aria-current="step">{{ $stage['label'] }}</span>
                @else
                    <a href="{{ route($stage['route'], $estateStepperRouteParams) }}" class="estate-workflow-stepper__step">{{ $stage['label'] }}</a>
                @endif
                @if (!$loop->last)
                    <i class="material-icons material-symbols-rounded estate-workflow-stepper__sep" aria-hidden="true">arrow_forward_ios</i>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@once
@push('styles')
<style>
    .estate-workflow-stepper {
        display: flex;
        flex-direction: column;
        gap: 0.9375rem;
        padding: 0.9375rem var(--ds-space-4);
        background: var(--ds-surface);
        border-radius: var(--ds-radius-card);
    }

    .estate-workflow-stepper__caption {
        margin: 0;
        padding-bottom: var(--ds-space-1);
        border-bottom: 1px solid var(--ds-line);
        font-size: 0.75rem;
        line-height: 0.875rem;
        color: var(--ds-ink);
    }

    .estate-workflow-stepper__steps {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.3125rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .estate-workflow-stepper__item {
        display: inline-flex;
        align-items: center;
        gap: 0.3125rem;
    }

    .estate-workflow-stepper__step {
        display: inline-block;
        padding: 0.3125rem 0.75rem;
        border-radius: 1.125rem;
        background: var(--ds-surface-2);
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.125rem;
        color: var(--ds-ink);
        text-decoration: none;
    }

    a.estate-workflow-stepper__step:hover {
        color: var(--ds-primary);
    }

    a.estate-workflow-stepper__step:focus-visible {
        outline: 0;
        box-shadow: var(--ds-focus-ring);
    }

    .estate-workflow-stepper__step.is-current {
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--ds-primary);
    }

    .estate-workflow-stepper__sep {
        font-size: 0.836rem;
        color: var(--ds-ink-muted);
    }
</style>
@endpush
@endonce