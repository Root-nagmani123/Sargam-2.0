{{-- Horizontal numbered stepper for dynamic FC form steps.
     Needs: $allSteps (collection), $step (current), $form.

     Travel Plan is appended as the LAST step. It is not an admin-configurable
     form step, but the flow reaches it after the final step, so the tracker must
     show it there — same rule the dashboard uses to render the Travel Plan card
     (the form has a Bank Details step, tracker_column = bank_done). --}}
@php
    $curIdx = $allSteps->search(fn ($s) => $s->id === $step->id);
    if ($curIdx === false) { $curIdx = 0; }

    $showTravel = (bool) $allSteps->firstWhere('tracker_column', 'bank_done');
    $travelDone = false;
    if ($showTravel) {
        $travelDone = app(\App\Services\FC\FcRegistrationFlowService::class)
            ->isTravelComplete((int) auth()->id(), $form);
    }
@endphp
<nav class="fc-stepper" aria-label="Form steps">
    @foreach($allSteps as $i => $s)
        @php $state = $i < $curIdx ? 'done' : ($i === $curIdx ? 'active' : 'todo'); @endphp
        <a href="{{ route('fc-reg.forms.step', [$form, $s]) }}"
           class="fc-stp fc-stp--{{ $state }}"
           @if($state === 'active') aria-current="step" @endif
           title="{{ $s->step_name }}">
            <span class="fc-stp__dot">
                @if($state === 'done')<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
            </span>
            <span class="fc-stp__lbl">{{ $s->step_name }}</span>
        </a>
    @endforeach

    @if($showTravel)
        @php $travelState = $travelDone ? 'done' : 'todo'; @endphp
        <a href="{{ route('fc-reg.registration.travel') }}"
           class="fc-stp fc-stp--{{ $travelState }}"
           title="Travel Plan">
            <span class="fc-stp__dot">
                @if($travelDone)<i class="bi bi-check-lg"></i>@else{{ $allSteps->count() + 1 }}@endif
            </span>
            <span class="fc-stp__lbl">Travel Plan</span>
        </a>
    @endif
</nav>
