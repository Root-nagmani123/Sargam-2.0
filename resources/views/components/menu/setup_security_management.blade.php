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
                            <!-- ---------------------------------- -->
                            <!-- Issue Management / CENTCOM -->
                            <!-- ---------------------------------- -->
                            <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.employee_idcard.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">ID Card List</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.employee_idcard.create') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Generate New ID Card</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.family_idcard.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request Family ID Card</span>
                                    </a></li>
                                    @if (hasRole('Admin'))
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.security.family_idcard_approval.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Family ID Card Approval</span>
                                    </a></li>
                                    @endif
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.duplicate_idcard.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request Duplicate ID Card</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.security.vehicle_pass.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Vehicle Pass Request</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.security.duplicate_vehicle_pass.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Duplicate Vehicle Pass Request</span>
                                    </a></li>
                                    @if (hasRole('Admin'))
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.security.vehicle_pass_approval.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Vehicle Pass Approval</span>
                                        </a></li>
                                    @endif
                                <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.security.employee_idcard_approval.approval1') ? 'active' : '' }}"
                                        href="{{ route('admin.security.employee_idcard_approval.approval1') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Approval I</span>
                                    </a></li>
                                    @if (hasRole('Admin'))
                                <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.security.employee_idcard_approval.approval2') ? 'active' : '' }}"
                                        href="{{ route('admin.security.employee_idcard_approval.approval2') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Approval II</span>
                                    </a></li>
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