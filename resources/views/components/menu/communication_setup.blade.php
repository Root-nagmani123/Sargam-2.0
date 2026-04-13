<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-12" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            <li class="sidebar-item comm-sidebar-section-heading">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#communicationNotificationsCollapse" role="button"
                                    aria-expanded="false" aria-controls="communicationNotificationsCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Notifications</span>
                                    <i class="material-icons menu-icon material-symbols-rounded">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="communicationNotificationsCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Notice</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Campus Tweet</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.birthday-wish.*') ? 'active' : '' }}"
                                        href="{{ route('admin.birthday-wish.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Birthday Wishes</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.word-of-day.*') ? 'active' : '' }}"
                                        href="{{ route('admin.word-of-day.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Word of the Day</span>
                                    </a>
                                </li>
                            </ul>

                            <li class="sidebar-item comm-sidebar-section-heading mt-2">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#communicationMeetingCollapse" role="button"
                                    aria-expanded="false" aria-controls="communicationMeetingCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Meeting
                                        Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="communicationMeetingCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Meeting
                                            Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Meeting</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define MOM</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">View MOM</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Search Agenda</span>
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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);">
        </div>
    </div>
</nav>
