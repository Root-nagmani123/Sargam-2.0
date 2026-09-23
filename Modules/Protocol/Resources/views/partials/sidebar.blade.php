<div id="protocol-sidebar">
  <div class="brand">
    <div class="emblem">PM</div>
    <div class="brand-text">
      <div class="brand-title">Protocol Module</div>
      <small>Guest House &middot; Vehicle &middot; Ticket</small>
    </div>
  </div>

  <div class="nav-section-label">General</div>
  <a class="nav-link {{ request()->routeIs('protocol.dashboard') ? 'active' : '' }}"
     href="{{ route('protocol.dashboard') }}">
    <i class="bi bi-grid-1x2-fill"></i> Dashboard
  </a>

  @role(config('protocol.roles.employee'))
    <div class="nav-section-label">Employee</div>
    <a class="nav-link {{ request()->routeIs('protocol.requests.create') ? 'active' : '' }}"
       href="{{ route('protocol.requests.create') }}">
      <i class="bi bi-plus-circle-fill"></i> New Request
    </a>
    <a class="nav-link {{ request()->routeIs('protocol.requests.my') ? 'active' : '' }}"
       href="{{ route('protocol.requests.my') }}">
      <i class="bi bi-list-check"></i> My Requests
    </a>
  @endrole

  @role(config('protocol.roles.protocol_staff'))
    <div class="nav-section-label">Protocol Staff</div>
    <a class="nav-link {{ request()->routeIs('protocol.approval.queue') ? 'active' : '' }}"
       href="{{ route('protocol.approval.queue') }}">
      <i class="bi bi-inbox-fill"></i> Approval Queue
    </a>
    <a class="nav-link {{ request()->routeIs('protocol.approval.all') ? 'active' : '' }}"
       href="{{ route('protocol.approval.all') }}">
      <i class="bi bi-collection-fill"></i> All Requests
    </a>
  @endrole

  @role(config('protocol.roles.manager'))
    <div class="nav-section-label">Manager</div>
    <a class="nav-link {{ request()->routeIs('protocol.manager.queue') ? 'active' : '' }}"
       href="{{ route('protocol.manager.queue') }}">
      <i class="bi bi-person-check-fill"></i> Recommended to Me
    </a>
  @endrole

  <div class="nav-section-label">Records</div>
  <a class="nav-link {{ request()->routeIs('protocol.history.index') ? 'active' : '' }}"
     href="{{ route('protocol.history.index') }}">
    <i class="bi bi-clock-history"></i> History Log
  </a>
</div>
