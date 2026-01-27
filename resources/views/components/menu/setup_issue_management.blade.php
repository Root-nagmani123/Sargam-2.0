<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-mini-9" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 20px 24px">
                        <ul class="sidebar-menu" id="sidebarnav">
                            <!-- ---------------------------------- -->
                            <!-- Issue Management / CENTCOM -->
                            <!-- ---------------------------------- -->
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#issueManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="issueManagementCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Issue Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="issueManagementCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.issue-management.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">All Issues</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.issue-management.centcom') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">CENTCOM - Reported Complaints</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.issue-management.create') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Log New Issue</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.issue-categories.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Manage Categories</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.issue-sub-categories.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Manage Sub-Categories</span>
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
