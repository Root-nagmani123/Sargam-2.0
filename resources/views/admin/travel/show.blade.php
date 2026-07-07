@extends('admin.layouts.master')
@section('title', 'Travel Plan — '.($displayName ?? $userId))

@section('setup_content')
<div class="container-fluid px-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.travel.index') }}">Travel Plans</a></li>
            <li class="breadcrumb-item active">{{ $userId }}</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-train-front me-2"></i>Travel Plan — Joining (Joining Date report)
            </h5>
            <small class="text-muted">{{ $displayName ?? $userId }}</small>
            @if($plan->is_submitted)
                <span class="badge bg-success ms-2">Submitted</span>
            @else
                <span class="badge bg-warning text-dark ms-2">Draft</span>
            @endif
        </div>

        <div class="card-body p-4">
            <dl class="row small mb-0">
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $displayName ?? '—' }}</dd>
                <dt class="col-sm-3">Code</dt><dd class="col-sm-9">{{ $displayCode ?? '—' }}</dd>
                <dt class="col-sm-3">Mobile</dt><dd class="col-sm-9">{{ $displayMobile ?? '—' }}</dd>
                <dt class="col-sm-3">Arrival date</dt><dd class="col-sm-9">{{ $plan->joining_date?->format('d M Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Activity slot</dt><dd class="col-sm-9">
                    {{ $plan->fcArrivalSlot?->slot_label ?? '—' }}
                    @if($plan->fcArrivalSlot?->time_start && $plan->fcArrivalSlot?->time_end)
                        <span class="text-muted">(
                            {{ \Illuminate\Support\Str::substr($plan->fcArrivalSlot->time_start, 0, 5) }}–{{ \Illuminate\Support\Str::substr($plan->fcArrivalSlot->time_end, 0, 5) }}
                        )</span>
                    @endif
                </dd>
                <dt class="col-sm-3">Mode of journey</dt><dd class="col-sm-9">{{ $plan->mode_of_journey ?? '—' }}</dd>
                <dt class="col-sm-3">Flight / Train / Vehicle no.</dt><dd class="col-sm-9">{{ $plan->journey_vehicle_no ?? '—' }}</dd>
                <dt class="col-sm-3">Arrival time at Dehradun</dt><dd class="col-sm-9">{{ $plan->arrival_time_dehradun ?? '—' }}</dd>
                <dt class="col-sm-3">Require academy vehicle</dt>
                <dd class="col-sm-9">{{ $plan->requiresAcademyVehicleYes() ? 'Yes' : 'No' }}</dd>
            </dl>
            @if($plan->special_requirements)
                <p class="small mt-2"><span class="text-muted">Remarks:</span> {{ $plan->special_requirements }}</p>
            @endif

            <div class="mt-4">
                <a href="{{ route('admin.travel.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
                <a href="{{ route('admin.travel.edit', $userId) }}" class="btn btn-primary btn-sm ms-2">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>
            </div>
        </div>
    </div>
    </div>
    </div>
</div>
@endsection
