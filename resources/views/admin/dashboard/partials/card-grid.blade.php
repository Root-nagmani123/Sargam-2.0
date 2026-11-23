{{-- The stat card grid: one row of cards. Queue cards with work in them come first (their
     "Needs action" badge marks them); the other cards with a figure follow in the same row.
     Cards whose figure is 0 fold into a closed "nothing to show" section under them,
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
@endphp
<div class="dashboard-stats-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-3 mt-3">
    @foreach($actionCards->concat($overviewCards) as $card)
    <div class="col">
        @include('admin.dashboard.partials.stat-card', ['card' => $card])
    </div>
    @endforeach
</div>
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
