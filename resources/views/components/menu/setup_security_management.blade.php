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
                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">
                            <!-- Security Management Section -->
                            <!-- Vehicle Management -->
                                <li class="sidebar-item mb-2">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center rounded-pill px-3 py-2 text-decoration-none"
                                        data-bs-toggle="collapse" 
                                        href="#vehicleManagementCollapse" 
                                        role="button"
                                        aria-expanded="false" 
                                        aria-controls="vehicleManagementCollapse">
                                        <span class="hide-menu small small-sm-normal text-nowrap fw-semibold">Vehicle Management</span>
                                        <i class="material-icons menu-icon transition-transform" style="font-size: 18px;">keyboard_arrow_down</i>
                                    </a>
                                    <ul class="collapse list-unstyled ps-3 mt-2" id="vehicleManagementCollapse" data-bs-parent="#sidebarnav">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.vehicle_type.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Vehicle Types</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.vehicle_pass_config.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Pass Configuration</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.vehicle_pass.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">My Applications</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.vehicle_pass.create') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Apply for Pass</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.vehicle_pass_approval.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Pending Approvals</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.vehicle_pass_approval.all') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">All Applications</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <!-- Visitor/Gate Pass Management -->
                                <li class="sidebar-item mb-2">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center rounded-pill px-3 py-2 text-decoration-none"
                                        data-bs-toggle="collapse" 
                                        href="#visitorPassCollapse" 
                                        role="button"
                                        aria-expanded="false" 
                                        aria-controls="visitorPassCollapse">
                                        <span class="hide-menu small small-sm-normal text-nowrap fw-semibold">Visitor Pass</span>
                                        <i class="material-icons menu-icon transition-transform" style="font-size: 18px;">keyboard_arrow_down</i>
                                    </a>
                                    <ul class="collapse list-unstyled ps-3 mt-2" id="visitorPassCollapse" data-bs-parent="#sidebarnav">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.visitor_pass.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">All Visitors</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.security.visitor_pass.create') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Register Visitor</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
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
