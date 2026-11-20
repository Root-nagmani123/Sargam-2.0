{{-- The stat card grid. Queue cards with work in them come first, under their own heading;
     everything else (totals, empty queues) follows as the overview. Headings only when both
     groups exist. The page first renders it with no figures (one grid of placeholders), then
     swaps in the copy UserController::dashboardCards() renders with them. --}}
@php
    [$actionCards, $overviewCards] = $cardsToRender->partition(fn ($c) => ($c['actionable'] ?? false)
        && ($c['show_count'] ?? true) && is_numeric($c['count']) && $c['count'] > 0);
    $cardGroups = $actionCards->isNotEmpty() && $overviewCards->isNotEmpty()
        ? [['Needs your action', $actionCards], ['Overview', $overviewCards]]
        : [[null, $cardsToRender]];
@endphp
@foreach($cardGroups as [$groupTitle, $groupCards])
@if($groupTitle)
<h2 class="dashboard-card-group-title {{ $loop->first ? 'mt-3' : '' }}">{{ $groupTitle }}</h2>
@endif
<div class="dashboard-stats-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-3 {{ $groupTitle ? '' : 'mt-3' }}">
    @foreach($groupCards as $card)
    <div class="col">
        @include('admin.dashboard.partials.stat-card', ['card' => $card])
    </div>
    @endforeach
</div>
@endforeach
