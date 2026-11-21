{{-- The stat card grid. Queue cards with work in them come first, under their own heading;
     the other cards with a figure follow as the overview. Headings only when both groups
     exist. Cards whose figure is 0 fold into a closed "nothing to show" section under them,
     so the live figures are not lost among zeros (unless every card is 0: then nothing folds).
     The page first renders it with no figures (one grid of placeholders), then swaps in the
     copy UserController::dashboardCards() renders with them. --}}
@php
    [$emptyCards, $shownCards] = $cardsToRender->partition(fn ($c) => ($c['show_count'] ?? true)
        && is_numeric($c['count']) && (float) $c['count'] == 0);
    if ($shownCards->isEmpty()) {
        [$shownCards, $emptyCards] = [$cardsToRender, collect()];
    }
    [$actionCards, $overviewCards] = $shownCards->partition(fn ($c) => ($c['actionable'] ?? false)
        && ($c['show_count'] ?? true) && is_numeric($c['count']) && $c['count'] > 0);
    $cardGroups = $actionCards->isNotEmpty() && $overviewCards->isNotEmpty()
        ? [['Needs your action', $actionCards], ['Overview', $overviewCards]]
        : [[null, $shownCards]];
@endphp
@foreach($cardGroups as [$groupTitle, $groupCards])
@if($groupTitle)
<h2 class="dashboard-card-group-title {{ $loop->first ? 'mt-3' : '' }}">{{ $groupTitle }} <span class="dashboard-card-group-count">({{ $groupCards->count() }})</span></h2>
@endif
<div class="dashboard-stats-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-3 {{ $groupTitle ? '' : 'mt-3' }}">
    @foreach($groupCards as $card)
    <div class="col">
        {{-- Under the "Needs your action" heading the badge would only repeat it. --}}
        @include('admin.dashboard.partials.stat-card', ['card' => $card, 'grouped' => $groupTitle !== null])
    </div>
    @endforeach
</div>
@endforeach
@if($emptyCards->isNotEmpty())
<details class="dashboard-empty-cards mb-3">
    <summary>
        <span>{{ $emptyCards->count() }} {{ \Illuminate\Support\Str::plural('card', $emptyCards->count()) }} with nothing to show</span>
        <span class="dashboard-empty-cards__names">{{ $emptyCards->pluck('label')->implode(' · ') }}</span>
    </summary>
    <div class="dashboard-stats-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mt-2">
        @foreach($emptyCards as $card)
        <div class="col">
            @include('admin.dashboard.partials.stat-card', ['card' => $card, 'grouped' => true])
        </div>
        @endforeach
    </div>
</details>
@endif
