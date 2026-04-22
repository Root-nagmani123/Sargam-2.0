@php
    $stepLabels = ['Basic Info','Personal','Other Details','Bank','Documents','Status'];
    $stepRoutes = [
        'fc-reg.registration.step1',
        'fc-reg.registration.step2',
        'fc-reg.registration.step3',
        'fc-reg.registration.bank',
        'fc-reg.registration.documents',
        'fc-reg.registration.status',
    ];
@endphp
<div class="mb-3">
    <div class="d-flex align-items-center gap-0 flex-nowrap overflow-auto pb-1">
        @foreach($stepLabels as $i => $label)
            @php
                $stepNum  = $i + 1;
                $isCurrent= $stepNum === $current;
                $isDone   = $stepNum < $current;
            @endphp
            <div class="d-flex align-items-center {{ $i < count($stepLabels)-1 ? 'flex-grow-1' : '' }}">
                <a href="{{ $isDone || $isCurrent ? route($stepRoutes[$i]) : '#' }}"
                   class="d-flex flex-column align-items-center text-decoration-none flex-shrink-0"
                   style="min-width:56px;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                         style="width:32px;height:32px;font-size:0.8rem;
                         background:{{ $isCurrent ? '#1a3c6e' : ($isDone ? '#198754' : '#dee2e6') }};
                         color:{{ ($isCurrent || $isDone) ? '#fff' : '#6c757d' }};">
                        @if($isDone)
                            <i class="bi bi-check2"></i>
                        @else
                            {{ $stepNum }}
                        @endif
                    </div>
                    <div class="mt-1 text-center" style="font-size:0.65rem;
                         color:{{ $isCurrent ? '#1a3c6e' : ($isDone ? '#198754' : '#6c757d') }};
                         font-weight:{{ $isCurrent ? '700' : '400' }};white-space:nowrap;">
                        {{ $label }}
                    </div>
                </a>
                @if($i < count($stepLabels)-1)
                    <div class="flex-grow-1 mx-1" style="height:2px;
                         background:{{ $stepNum < $current ? '#198754' : '#dee2e6' }};
                         min-width:20px;margin-bottom:18px;"></div>
                @endif
            </div>
        @endforeach
    </div>
</div>
