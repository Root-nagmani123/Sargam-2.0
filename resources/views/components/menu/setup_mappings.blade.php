@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-setup-mini-7',
    'panelMenuTitle' => 'MASTER',
    'panelMenuClass' => 'sidebar-setup-mappings-menu',
])
                            @if(hasRole('Admin') || hasRole('Training-Induction'))

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#generalMasterMenu" role="button"
                                    aria-expanded="false" aria-controls="generalMasterMenu">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">inventory_2</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">General Master</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="generalMasterMenu">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('Venue-Master.*') ? 'active' : '' }}"
                                                href="{{ route('Venue-Master.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Venue Master</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.class.session.*') ? 'active' : '' }}"
                                                href="{{ route('master.class.session.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Class Session</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('stream.*') ? 'active' : '' }}"
                                                href="{{ route('stream.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Stream</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1 d-none">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#hostelMenu" role="button"
                                    aria-expanded="false" aria-controls="hostelMenu">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">hotel</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Hostel</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2 d-none" id="hostelMenu">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.hostel.building.*') ? 'active' : '' }}"
                                                href="{{ route('master.hostel.building.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Building Master</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.hostel.floor.*') ? 'active' : '' }}"
                                                href="{{ route('master.hostel.floor.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Floor</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('hostel.building.floor.room.map.*') ? 'active' : '' }}"
                                                href="{{ route('hostel.building.floor.room.map.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Building Floor Room Mapping</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('hostel.building.map.assign.student.*') ? 'active' : '' }}"
                                                href="{{ route('hostel.building.map.assign.student') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Assign Hostel</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#addressMenu" role="button"
                                    aria-expanded="false" aria-controls="addressMenu">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">location_on</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Address</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="addressMenu">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.country.*') ? 'active' : '' }}"
                                                href="{{ route('master.country.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Country</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.state.*') ? 'active' : '' }}"
                                                href="{{ route('master.state.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">State</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.district.*') ? 'active' : '' }}"
                                                href="{{ route('master.district.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">District</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.city.*') ? 'active' : '' }}"
                                                href="{{ route('master.city.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">City</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            @endif
@include('components.menu.partials.panel-shell-close')
