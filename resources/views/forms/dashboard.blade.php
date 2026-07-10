@extends('fc.layouts.master')
@section('title', $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
<div class="fc-form-page">
<div class="fc-shell">
    @php
        $gatedStepMeta = $gatedStepMeta ?? [];
        $totalSteps = $steps->count();
        $doneSteps  = $steps->filter(fn ($s) => ($stepStatus[$s->id] ?? false))->count();
        $pct        = $totalSteps > 0 ? (int) round($doneSteps / $totalSteps * 100) : 0;
    @endphp
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi {{ $form->icon ?? 'bi-file-text' }}"></i></div>
            <div>
                <h1>{{ $form->form_name }}</h1>
                @if($form->description)<p>{{ $form->description }}</p>@endif
            </div>
            <div class="fc-band__meta">
                <small>{{ $doneSteps }} of {{ $totalSteps }} steps completed</small>
                <div class="fc-prog"><span style="width: {{ $pct }}%"></span></div>
            </div>
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
                    $rawDone = $stepStatus[$step->id] ?? false;
                    $fcReg = ($form->form_slug ?? '') === 'fc-registration' && isset($registrationProgress);
                    if ($fcReg) {
                        $regSteps = $registrationProgress['steps'] ?? [];
                        $isDone = $rawDone;
                        $isAccessible = fc_registration_dynamic_form_step_accessible($step->step_slug, $regSteps, $isDone);
                        $blockedMsg = $isAccessible ? null : fc_registration_dynamic_form_step_blocked_message($step->step_slug);
                    } else {
                        $prevAllDone = true;
                        for ($pi = 0; $pi < $si; $pi++) {
                            $prevId = $steps[$pi]->id;
                            // A gated-off Special Assistant step is optional → it never blocks later steps.
                            if (!($stepStatus[$prevId] ?? false) && !isset($gatedStepMeta[$prevId])) {
                                $prevAllDone = false;
                                break;
                            }
                        }
                        // Sequential UX: do not show "Completed" / Review until every earlier step is done,
                        // even if this step's tracker/detail row is already set (avoids misleading cards).
                        $isDone = $rawDone && ($si === 0 || $prevAllDone);
                        $isAccessible = $si === 0 || $prevAllDone;
                        $blockedMsg = $isAccessible ? null : 'Complete the previous step first';
                    }

                    // Special Assistant with no ph_value on the roster: disabled + not applicable.
                    if (isset($gatedStepMeta[$step->id])) {
                        $isAccessible = false;
                        $isDone = false;
                        $blockedMsg = $gatedStepMeta[$step->id];
                    }
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

                            @if($isAccessible)
                                <a href="{{ route('fc-reg.forms.step', [$form, $step]) }}"
                                   class="btn btn-sm {{ $isDone ? 'btn-outline-success' : 'btn-primary' }} w-100">
                                    @if($isDone)
                                        <i class="bi bi-pencil me-1"></i>Review / Edit
                                    @else
                                        <i class="bi bi-arrow-right me-1"></i>Fill Now
                                    @endif
                                </a>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100" disabled>
                                    <i class="bi bi-lock me-1"></i>{{ $blockedMsg }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Travel Plan card moved out of the loop → rendered once at the very end (see after @endforeach). --}}
                @if(($form->form_slug ?? '') === 'fc-registration' && isset($registrationProgress, $fcRegistrationMeta) && $step->isDocumentsStep())
                    @php
                        $declarationDone = $registrationProgress['steps']['confirmed'] ?? false;
                        $docsProgress = $registrationProgress['steps']['documents'] ?? false;
                        $masterStatus = $fcRegistrationMeta['master_status'] ?? null;
                        $declarationAccessible = $declarationDone || ($masterStatus === 'SUBMITTED');
                        if (!$declarationAccessible) {
                            $declarationBlockedMsg = $docsProgress
                                ? 'Submit documents (final submit) first'
                                : 'Complete document upload first';
                        } else {
                            $declarationBlockedMsg = null;
                        }
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 {{ $declarationDone ? 'border-success' : '' }}" style="border-radius:10px; {{ $declarationDone ? 'border-left: 3px solid #198754 !important;' : '' }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                         style="width:40px;height:40px;background:{{ $declarationDone ? '#198754' : '#1a3c6e' }};color:#fff;font-size:1rem;">
                                        @if($declarationDone)
                                            <i class="bi bi-check-lg"></i>
                                        @else
                                            <span class="fw-bold">6</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Declaration &amp; Submit</h6>
                                        <small class="text-muted">Final declaration &amp; confirmation</small>
                                    </div>
                                    @if($declarationDone)
                                        <span class="badge bg-success ms-auto">Completed</span>
                                    @endif
                                </div>
                                <p class="text-muted small mb-3">Review your registration summary, accept the declaration, and confirm submission.</p>
                                @if($declarationAccessible)
                                    <a href="{{ route('fc-reg.registration.status') }}"
                                       class="btn btn-sm {{ $declarationDone ? 'btn-outline-success' : 'btn-primary' }} w-100">
                                        @if($declarationDone)
                                            <i class="bi bi-pencil me-1"></i>Review / Summary
                                        @else
                                            <i class="bi bi-arrow-right me-1"></i>Continue
                                        @endif
                                    </a>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" disabled>
                                        <i class="bi bi-lock me-1"></i>{{ $declarationBlockedMsg }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Travel Plan is not an admin-configurable step; render it once, LAST, after every
                 step, for any form that has a Bank Details step (tracker_column = bank_done). --}}
            @php
                $bankStep = $steps->firstWhere('tracker_column', 'bank_done');
            @endphp
            @if($bankStep)
                @php
                    if (($form->form_slug ?? '') === 'fc-registration' && isset($registrationProgress)) {
                        $travelDone = $registrationProgress['steps']['travel'] ?? false;
                        $bankDoneReg = $registrationProgress['steps']['bank'] ?? false;
                    } else {
                        $bankDoneReg = $stepStatus[$bankStep->id] ?? false;
                    }
                    $travelAccessible = $travelDone || $bankDoneReg;
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
        </div>
    @endif
</div>
</div>
@endsection
