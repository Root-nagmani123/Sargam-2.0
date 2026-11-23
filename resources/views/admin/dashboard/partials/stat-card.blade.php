{{-- One dashboard stat tile. $card is a row of UserController::dashboardStatCards(): the dashboard
     grid and the module-page preview both render it, so the two never drift apart. --}}
@php
    $statNum = ($card['show_count'] ?? true) && is_numeric($card['count']) ? (float) $card['count'] : null;
    // A queue with work in it is flagged; an empty figure steps back so the live ones stand out.
    $statState = $statNum === null ? '' : ($statNum == 0 ? 'stat-card--empty' : (($card['actionable'] ?? false) ? 'stat-card--action' : ''));
@endphp
@if($card['link'])
<a href="{{ $card['link'] }}" class="text-decoration-none d-block h-100">
@endif
    <div class="card stat-card h-100 p-3 {{ $statState }}">
        <div class="d-flex align-items-center gap-3 stat-card-row">
            <div class="stat-icon-wrapper {{ $card['color_class'] }}">
                <i class="material-symbols-rounded" aria-hidden="true">{{ $card['icon'] }}</i>
            </div>
            <div class="stat-card-body flex-grow-1 min-w-0">
                {{-- Two lines are reserved for the label, set on its bottom edge,
                     so the figure sits right under it and lines up across a row. --}}
                <div class="stat-title-box">
                    <p class="stat-title" title="{{ $card['label'] }}">{{ $card['label'] }}</p>
                </div>
                @if(($card['show_count'] ?? true) && ($card['count_failed'] ?? false))
                <p class="stat-value" title="This figure could not be loaded">–</p>
                @elseif(($card['show_count'] ?? true) && $card['count'] === null)
                {{-- First paint: the figure is on its way from admin.dashboard.cards. --}}
                <p class="stat-value"><span class="stat-value-skeleton" aria-hidden="true"></span><span class="visually-hidden">Loading</span></p>
                @elseif($card['show_count'] ?? true)
                @php
                    // Counts are whole numbers and keep their 0-padding, but a
                    // marks total can carry a half mark, and (int) would have
                    // shown 13.5 as 13.
                    $v = is_numeric($card['count']) ? (float) $card['count'] : 0;
                    $statValue = floor($v) == $v
                        ? ($v < 10 ? sprintf('%02d', $v) : number_format($v))
                        : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
                @endphp
                <p class="stat-value">{{ $statValue }}@if($statState === 'stat-card--action' && ! ($grouped ?? false))<span class="stat-action-badge">Needs action</span>@endif</p>
                @else
                {{-- Cards that open a page rather than count rows (the
                     timetables) carry no number — an "00" would read as
                     "nothing there". --}}
                <p class="stat-value stat-value-link">Open</p>
                @endif
            </div>
        </div>
    </div>
@if($card['link'])
</a>
@endif
