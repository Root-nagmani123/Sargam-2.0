{{-- Horizontal numbered stepper for dynamic FC form steps.
     Needs: $allSteps (collection), $step (current), $form. --}}
@php
    $curIdx = $allSteps->search(fn ($s) => $s->id === $step->id);
    if ($curIdx === false) { $curIdx = 0; }
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
</nav>
