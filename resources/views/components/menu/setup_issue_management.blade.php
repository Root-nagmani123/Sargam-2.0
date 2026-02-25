<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-mini-10" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 20px 24px">
                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">
                            <!-- ---------------------------------- -->
                            <!-- Issue Management / CENTCOM -->
                            <!-- ---------------------------------- -->
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-management.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">All Issues</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-management.centcom') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">CENTCOM -  Assigned Complaints</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-management.create') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Log New Issue</span>
                                </a>
                            </li>
                            @if(hasRole('Admin') || hasRole('SuperAdmin'))
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-categories.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Manage Categories</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-sub-categories.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Manage Sub-Categories</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-priorities.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Manage Priorities</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-escalation-matrix.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Escalation Matrix</span>
                                </a>
                            </li>
                            @endif
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
