@php
    $navForm = $formStepNav['form'] ?? null;
    $navItems = $formStepNav['items'] ?? [];
@endphp
@if($navForm && count($navItems) > 0)
<div class="mb-3">
    <div class="d-flex align-items-center gap-0 flex-nowrap overflow-auto pb-1">
        @foreach($navItems as $i => $item)
            @php
                $isDone = !empty($item['done']);
                // On this page Travel is always "current"; when also done, show completed (green) styling.
                $isActive = !empty($item['current']) && ! $isDone;
                $href = $item['url'] ?? '#';
                $canLink = filled($href) && $href !== '#' && ($isDone || $isActive);
            @endphp
            <div class="d-flex align-items-center {{ $i < count($navItems) - 1 ? 'flex-grow-1' : '' }}">
                <a href="{{ $canLink ? $href : '#' }}"
                   class="d-flex flex-column align-items-center text-decoration-none flex-shrink-0 {{ $canLink ? '' : 'pe-none' }}"
                   style="min-width:56px;"
                   @if(! $canLink) aria-disabled="true" @endif>
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                         style="width:32px;height:32px;font-size:0.8rem;
                         background:{{ $isDone ? '#198754' : ($isActive ? '#1a3c6e' : '#dee2e6') }};
                         color:{{ ($isActive || $isDone) ? '#fff' : '#6c757d' }};">
                        @if($isDone)
                            <i class="bi bi-check2"></i>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="mt-1 text-center" style="font-size:0.65rem;
                         color:{{ $isDone ? '#198754' : ($isActive ? '#1a3c6e' : '#6c757d') }};
                         font-weight:{{ ($isActive || $isDone) ? '600' : '400' }};white-space:nowrap;max-width:88px;">
                        {{ $item['label'] }}
                    </div>
                </a>
                @if($i < count($navItems) - 1)
                    <div class="flex-grow-1 mx-1" style="height:2px;
                         background:{{ $isDone ? '#198754' : '#dee2e6' }};
                         min-width:16px;margin-bottom:18px;"></div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif
