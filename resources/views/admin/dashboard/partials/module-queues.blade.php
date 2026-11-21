{{-- Home only: the other modules where this login has work waiting; each chip opens that
     module's dashboard. Rendered by UserController::dashboardCards(). --}}
@if(($moduleQueues ?? collect())->isNotEmpty())
<nav class="dashboard-module-queues mt-3" aria-label="Pending in other modules">
    <span class="dashboard-module-queues__title">Pending across modules</span>
    @foreach($moduleQueues as $queue)
    <a href="{{ route('admin.dashboard', ['category' => $queue['module']->id]) }}" class="dashboard-module-queue"
        title="{{ $queue['cards']->map(fn ($c) => $c['label'].': '.number_format($c['count']))->implode(', ') }}">
        <span>{{ $queue['module']->name }}</span>
        <span class="dashboard-module-queue__count">{{ number_format($queue['total']) }}</span>
        <span class="visually-hidden">pending items</span>
    </a>
    @endforeach
</nav>
@endif
