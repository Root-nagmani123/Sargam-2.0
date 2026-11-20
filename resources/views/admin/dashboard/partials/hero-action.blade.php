{{-- The hero's "N cards need your action" line. Rendered by UserController::dashboardCards()
     once the figures are in; on the first paint (counts null) it renders nothing. --}}
@php
    $actionCardCount = $cardsToRender->filter(fn ($c) => ($c['actionable'] ?? false)
        && ($c['show_count'] ?? true) && is_numeric($c['count']) && $c['count'] > 0)->count();
@endphp
@if($actionCardCount > 0)
<p class="dashboard-hero-action mb-0 mt-1">
    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
    {{ $actionCardCount }} {{ \Illuminate\Support\Str::plural('card', $actionCardCount) }} need{{ $actionCardCount === 1 ? 's' : '' }} your action
</p>
@endif
