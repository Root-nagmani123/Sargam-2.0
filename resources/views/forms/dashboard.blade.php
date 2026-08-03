@extends('fc.layouts.master')
@section('title', $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
<div class="fc-form-page">
<div class="fc-shell">
    @php
        $gatedStepMeta = $gatedStepMeta ?? [];
        // A step that does not apply to this trainee can never be completed, so it is
        // excluded from the denominator — otherwise a finished trainee reads "6 of 7".
        // $progressDone / $progressTotal come from FcStepApplicabilityService; the
        // filters below are the same rule, kept as a fallback for any other caller.
        $doneSteps  = $progressDone ?? $steps->filter(fn ($s) => ($stepStatus[$s->id] ?? false))->count();
        $totalSteps = $progressTotal ?? $steps->filter(
            fn ($s) => ($stepStatus[$s->id] ?? false) || !isset($gatedStepMeta[$s->id])
        )->count();
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
            @if($formComplete ?? false)
                {{-- Offered only once every applicable step is done; the controller enforces
                     the same check, so the link cannot be used to skip ahead.

                     The PDF itself only contains the first two steps. Requiring all of them
                     anyway is deliberate academy policy — registration must be finished before
                     a trainee can self-print from it. See ReportController@myDescriptiveRollPdf. --}}
                <a href="{{ route('fc-reg.forms.descriptive-roll.pdf', $form) }}"
                   class="btn btn-light btn-sm fw-semibold"
                   style="white-space:nowrap;">
                    <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Descriptive Roll (PDF)
                </a>
            @endif
        </div>
    </div>

    {{-- ── Fixed information card ─────────────────────────────────────────────
         Always rendered, always first — before Step 1 — regardless of the form's
         configured steps or the trainee's progress. It is not a step: it carries no
         status, cannot be completed, and is never counted in the progress bar.
         The letter is a static asset in public/fc-documents/. --}}
    @php
        $fcLetterPath = 'fc-documents/1st-communication-letter-to-ots.pdf';
        $fcLetterExists = is_file(public_path($fcLetterPath));
    @endphp
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px; border-left:4px solid #004a93 !important;">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start gap-3">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center"
                     style="width:52px; height:52px; border-radius:10px; background:#e7edf6; color:#004a93;">
                    <i class="bi bi-envelope-paper fs-3" aria-hidden="true"></i>
                </div>
                <div class="flex-grow-1" style="min-width:16rem;">
                    <h5 class="fw-bold mb-1" style="color:#004a93;">Read this first</h5>
                    <p class="small fw-bold mb-3">Please read this joining document before you begin.</p>

                    @if($fcLetterExists)
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ asset($fcLetterPath) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-primary" style="background-color:#004a93; border-color:#004a93;">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Read the Joining Document (PDF)
                            </a>
                            <a href="{{ asset($fcLetterPath) }}" download
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download me-1"></i>Download
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning small mb-0 py-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>The joining document is not available at the moment.
                            Please contact the Academy office.
                        </div>
                    @endif
                </div>
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

                    // Step whose applicability rule does not hold for this trainee (e.g. Special
                    // Assistant with no ph_value on the roster): disabled, and rendered in its own
                    // "not applicable" state rather than as a pending step, so it does not read as
                    // outstanding work. Already-filled steps keep their normal Completed state.
                    $isNotApplicable = isset($gatedStepMeta[$step->id]) && !$rawDone;
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
                                     style="width:40px;height:40px;background:{{ $isDone ? '#198754' : ($isNotApplicable ? '#adb5bd' : '#1a3c6e') }};color:#fff;font-size:1rem;">
                                    @if($isDone)
                                        <i class="bi bi-check-lg"></i>
                                    @elseif($isNotApplicable)
                                        <i class="bi bi-dash-lg"></i>
                                    @else
                                        <span class="fw-bold">{{ $si + 1 }}</span>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $step->step_name }}</h6>
                                    <small class="text-muted">Step {{ $si + 1 }}</small>
                                </div>
                                @if($isDone)
                                    <span class="badge bg-success ms-auto">Completed</span>
                                @elseif($isNotApplicable)
                                    <span class="badge bg-secondary ms-auto">Not applicable</span>
                                @endif
                            </div>

                            @if($step->description)
                                <p class="small fw-bold mb-3">{{ Str::limit($step->description, 100) }}</p>
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
