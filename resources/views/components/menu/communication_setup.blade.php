@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-mini-12',
    'panelMenuTitle' => 'COMMUNICATION',
    'panelMenuClass' => 'sidebar-communication-setup-menu',
])

<li class="sidebar-item mb-1">
    <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
        data-bs-toggle="collapse" href="#communicationNotificationsCollapse" role="button"
        aria-expanded="false" aria-controls="communicationNotificationsCollapse">
        <span class="d-flex align-items-center gap-2 min-w-0">
            <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">notifications</i>
            <span class="hide-menu small small-sm-normal text-nowrap">Notifications</span>
        </span>
        <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
    </a>
</li>
<ul class="collapse list-unstyled mb-2" id="communicationNotificationsCollapse">
    <li class="sidebar-panel-submenu-tree">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="#">
                    <span class="hide-menu small small-sm-normal text-nowrap">Notice</span>
                </a>
            </li>
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="#">
                    <span class="hide-menu small small-sm-normal text-nowrap">Campus Tweet</span>
                </a>
            </li>
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.birthday-wish.*') ? 'active' : '' }}"
                    href="{{ route('admin.birthday-wish.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Birthday Wishes</span>
                </a>
            </li>
        </ul>
    </li>
</ul>

<li class="sidebar-item mb-1">
    <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
        data-bs-toggle="collapse" href="#communicationMeetingCollapse" role="button"
        aria-expanded="false" aria-controls="communicationMeetingCollapse">
        <span class="d-flex align-items-center gap-2 min-w-0">
            <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">groups</i>
            <span class="hide-menu small small-sm-normal text-nowrap">Meeting Management</span>
        </span>
        <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
    </a>
</li>
<ul class="collapse list-unstyled mb-2" id="communicationMeetingCollapse">
    <li class="sidebar-panel-submenu-tree">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="#">
                    <span class="hide-menu small small-sm-normal text-nowrap">Define Meeting Type</span>
                </a>
            </li>
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="#">
                    <span class="hide-menu small small-sm-normal text-nowrap">Define Meeting</span>
                </a>
            </li>
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="#">
                    <span class="hide-menu small small-sm-normal text-nowrap">Define MOM</span>
                </a>
            </li>
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="#">
                    <span class="hide-menu small small-sm-normal text-nowrap">View MOM</span>
                </a>
            </li>
            <li class="sidebar-item mb-1">
                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="#">
                    <span class="hide-menu small small-sm-normal text-nowrap">Search Agenda</span>
                </a>
            </li>
        </ul>
    </li>
</ul>

@include('components.menu.partials.panel-shell-close')
