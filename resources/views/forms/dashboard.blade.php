@extends('admin.layouts.master')
@section('title', $form->form_name)

@section('setup_content')
<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
             style="width:50px;height:50px;background:#1a3c6e;color:#fff;font-size:1.3rem;">
            <i class="bi {{ $form->icon ?? 'bi-file-text' }}"></i>
        </div>
        <div>
            <h4 class="mb-0">{{ $form->form_name }}</h4>
            @if($form->description)
                <p class="text-muted small mb-0">{{ $form->description }}</p>
            @endif
        </div>
    </div>

    @if($steps->isEmpty())
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            This form has no steps configured yet. Please contact the administrator.
        </div>
    @else
        <div class="row g-4">
            @foreach($steps as $si => $step)
                @php
                    $isDone = $stepStatus[$step->id] ?? false;
                    $isAccessible = true; // You can add sequential logic here if needed
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 {{ $isDone ? 'border-success' : '' }}" style="border-radius:10px; {{ $isDone ? 'border-left: 3px solid #198754 !important;' : '' }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                     style="width:40px;height:40px;background:{{ $isDone ? '#198754' : '#1a3c6e' }};color:#fff;font-size:1rem;">
                                    @if($isDone)
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        <span class="fw-bold">{{ $si + 1 }}</span>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $step->step_name }}</h6>
                                    <small class="text-muted">Step {{ $si + 1 }}</small>
                                </div>
                                @if($isDone)
                                    <span class="badge bg-success ms-auto">Completed</span>
                                @endif
                            </div>

                            @if($step->description)
                                <p class="text-muted small mb-3">{{ Str::limit($step->description, 100) }}</p>
                            @endif

                            <a href="{{ route('fc-reg.forms.step', [$form, $step]) }}"
                               class="btn btn-sm {{ $isDone ? 'btn-outline-success' : 'btn-primary' }} w-100">
                                @if($isDone)
                                    <i class="bi bi-pencil me-1"></i>Review / Edit
                                @else
                                    <i class="bi bi-arrow-right me-1"></i>Fill Now
                                @endif
                            </a>
                        </div>
                    </div>
                </div>
                @if(($form->form_slug ?? '') === 'fc-registration' && isset($registrationProgress) && $step->step_slug === 'bank')
                    @php
                        $travelDone = $registrationProgress['steps']['travel'] ?? false;
                        $bankDoneReg = $registrationProgress['steps']['bank'] ?? false;
                        $travelAccessible = $bankDoneReg || $isDone;
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 {{ $travelDone ? 'border-success' : '' }}" style="border-radius:10px; {{ $travelDone ? 'border-left: 3px solid #198754 !important;' : '' }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                         style="width:40px;height:40px;background:{{ $travelDone ? '#198754' : '#1a3c6e' }};color:#fff;font-size:1rem;">
                                        @if($travelDone)
                                            <i class="bi bi-check-lg"></i>
                                        @else
                                            <i class="bi bi-train-front"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Travel Plan</h6>
                                        <small class="text-muted">Joining journey and pickup</small>
                                    </div>
                                    @if($travelDone)
                                        <span class="badge bg-success ms-auto">Completed</span>
                                    @endif
                                </div>
                                <p class="text-muted small mb-3">Journey to Mussoorie and pickup preferences (same as main registration flow).</p>
                                @if($travelAccessible)
                                    <a href="{{ route('fc-reg.registration.travel') }}"
                                       class="btn btn-sm {{ $travelDone ? 'btn-outline-success' : 'btn-primary' }} w-100">
                                        @if($travelDone)
                                            <i class="bi bi-pencil me-1"></i>Review / Edit
                                        @else
                                            <i class="bi bi-arrow-right me-1"></i>Fill Now
                                        @endif
                                    </a>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" disabled>
                                        <i class="bi bi-lock me-1"></i>Complete bank details first
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
