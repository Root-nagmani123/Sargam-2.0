<nav class="sidebar-nav sargam-menu-flyout simplebar-scrollable-y" id="menu-right-mini-12" data-mini-nav-target="mini-12" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content">

                        <div class="sidebar-section-header text-uppercase fw-bold mb-1"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Communications
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            {{-- Notifications (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#communicationNotificationsCollapse" role="button"
                                    aria-expanded="false" aria-controls="communicationNotificationsCollapse">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">notifications</i>
                                        <span class="hide-menu">Notifications</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="communicationNotificationsCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="#">
                                        <span class="hide-menu">Notice</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="#">
                                        <span class="hide-menu">Campus Tweet</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('admin.birthday-wish.*') ? 'active' : '' }}"
                                        href="{{ route('admin.birthday-wish.index') }}">
                                        <span class="hide-menu">Birthday Wishes</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.word-of-day.*') ? 'active' : '' }}"
                                        href="{{ route('admin.word-of-day.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Word of the Day</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Meeting Management (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#communicationMeetingCollapse" role="button"
                                    aria-expanded="false" aria-controls="communicationMeetingCollapse">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">groups</i>
                                        <span class="hide-menu">Meeting Management</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="communicationMeetingCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="#">
                                        <span class="hide-menu">Define Meeting Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="#">
                                        <span class="hide-menu">Define Meeting</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="#">
                                        <span class="hide-menu">Define MOM</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="#">
                                        <span class="hide-menu">View MOM</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="#">
                                        <span class="hide-menu">Search Agenda</span>
                                    </a>
                                </li>
                            </ul>

                        </ul>

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
