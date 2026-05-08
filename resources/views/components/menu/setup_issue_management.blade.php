<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-10" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">

                        <div class="sidebar-section-header text-uppercase fw-bold mb-3"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Issue Management
                        </div>

                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">

                            @if (!hasRole('Student-OT'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.issue-management.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">list_alt</i>
                                    <span class="hide-menu">All Issues</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.issue-management.centcom') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">support_agent</i>
                                    <span class="hide-menu">CENTCOM - Assigned Complaints</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.issue-management.create') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">add_circle</i>
                                    <span class="hide-menu">Log New Issue</span>
                                </a>
                            </li>
                            @endif

                            @if(hasRole('Admin') || hasRole('SuperAdmin'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.issue-categories.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">category</i>
                                    <span class="hide-menu">Manage Categories</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.issue-sub-categories.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">label</i>
                                    <span class="hide-menu">Manage Sub-Categories</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.issue-priorities.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">priority_high</i>
                                    <span class="hide-menu">Manage Priorities</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.issue-escalation-matrix.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">call_split</i>
                                    <span class="hide-menu">Escalation Matrix</span>
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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
    </div>
</nav>
