<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-7" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content">

                        @if(hasRole('Admin') || hasRole('Training-Induction'))

                        <div class="sidebar-section-header text-uppercase fw-bold mb-1"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Master
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            {{-- General Master (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#generalMasterMenu" role="button"
                                    aria-expanded="false" aria-controls="generalMasterMenu">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">settings</i>
                                        <span class="hide-menu">General Master</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="generalMasterMenu">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('Venue-Master.index') }}">
                                        <span class="hide-menu">Venue Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('master.class.session.index') }}">
                                        <span class="hide-menu">Class Session</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('stream.index') }}">
                                        <span class="hide-menu">Stream</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Hostel (collapsible) --}}
                            <li class="sidebar-item mb-1 d-none">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#hostelMenu" role="button"
                                    aria-expanded="false" aria-controls="hostelMenu">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">apartment</i>
                                        <span class="hide-menu">Hostel</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="hostelMenu">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('master.hostel.building.index') }}">
                                        <span class="hide-menu">Building Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('master.hostel.floor.index') }}">
                                        <span class="hide-menu">Floor</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('hostel.building.floor.room.map.index') }}">
                                        <span class="hide-menu">Building Floor Room Mapping</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('hostel.building.map.assign.student') }}">
                                        <span class="hide-menu">Assign Hostel</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Address (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#addressMenu" role="button"
                                    aria-expanded="false" aria-controls="addressMenu">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">location_on</i>
                                        <span class="hide-menu">Address</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="addressMenu">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('master.country.index') }}">
                                        <span class="hide-menu">Country</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('master.state.index') }}">
                                        <span class="hide-menu">State</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('master.district.index') }}">
                                        <span class="hide-menu">District</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('master.city.index') }}">
                                        <span class="hide-menu">City</span>
                                    </a>
                                </li>
                            </ul>

                        </ul>

                        @endif

                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
    </div>
</nav>
