{{-- Home sidebar: personal estate shortcuts (same routes as staff self-service; Admin/Estate/Super Admin labels match Setup). --}}
@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-home-mini-estate',
    'panelMenuTitle' => 'ESTATE',
    'panelMenuClass' => 'sidebar-home-estate-menu',
])

<li class="sidebar-item mb-1">
    <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.estate.request-for-estate') && request('scope') === 'self' ? 'active' : '' }}"
        href="{{ route('admin.estate.request-for-estate', ['scope' => 'self']) }}">
        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">home_work</i>
        <span class="hide-menu small small-sm-normal text-nowrap">Request For Estate</span>
    </a>
</li>
<li class="sidebar-item mb-1">
    <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.estate.generate-estate-bill*') && request('scope') === 'self' ? 'active' : '' }}"
        href="{{ route('admin.estate.generate-estate-bill', ['scope' => 'self']) }}">
        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">receipt_long</i>
        <span class="hide-menu small small-sm-normal text-nowrap">My Estate Bill</span>
    </a>
</li>

@include('components.menu.partials.panel-shell-close')
