{{-- Faculty add/edit wizard step rail. Shared by create + edit — keep the step
     order in sync with the .ds-wizard-pane[data-step] blocks on both pages. --}}
<ul class="ds-stepper ds-stepper--wizard" id="facultyStepper">
    @foreach ([
        1 => 'Personal Information',
        2 => 'Qualifications Details',
        3 => 'Experience Details',
        4 => 'Bank Details',
        5 => 'Other Information',
    ] as $stepNo => $stepLabel)
        <li class="ds-step {{ $stepNo === 1 ? 'is-active' : '' }}" data-step-item="{{ $stepNo }}">
            <span class="ds-step-index">
                <span class="ds-step-num">{{ $stepNo }}</span>
                <i class="bi bi-check-lg ds-step-check" aria-hidden="true"></i>
            </span>
            <span class="ds-step-label">{{ $stepLabel }}</span>
        </li>
    @endforeach
</ul>
