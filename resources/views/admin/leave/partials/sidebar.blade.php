@php
    $currentRoute = request()->route()?->getName();
@endphp
<div class="leave-sidebar mb-3 mb-lg-0">
    <div class="small text-uppercase text-muted fw-semibold mb-2 px-2">Leave Module</div>
    <nav class="nav flex-column gap-1">
        <a href="{{ route('leave.my-leave') }}"
            class="nav-link {{ in_array($currentRoute, ['leave.my-leave'], true) ? 'active' : '' }}">
            <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:18px;">dashboard</i>
            Dashboard
        </a>
        <a href="{{ route('leave.apply') }}"
            class="nav-link {{ in_array($currentRoute, ['leave.apply', 'leave.edit', 'leave.view'], true) ? 'active' : '' }}">
            <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:18px;">edit_note</i>
            Apply Leave
        </a>
        <a href="{{ route('leave.my-leave') }}"
            class="nav-link {{ $currentRoute === 'leave.my-leave' ? 'active' : '' }}">
            <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:18px;">list_alt</i>
            My Leave
        </a>
        <a href="{{ route('leave.balance') }}"
            class="nav-link {{ $currentRoute === 'leave.balance' ? 'active' : '' }}">
            <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:18px;">account_balance_wallet</i>
            Leave Balance
        </a>
    </nav>

    @if(isset($ptBalance))
    <div class="mt-3 p-3 rounded-3 border bg-light">
        <div class="small text-muted">PT Remaining</div>
        <div class="fw-bold text-success fs-5">{{ number_format($ptBalance['remaining'], 1) }} Days</div>
        <div class="small text-muted mt-1">PT balance is updated once leave is approved.</div>
    </div>
    @endif
</div>
