<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-9" data-simplebar="init">
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
                            Security
                        </div>

                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">

                            @if (hasRole('Security Card') || hasRole('Admin Security'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.security.family_idcard_approval.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">badge</i>
                                    <span class="hide-menu">Requested Family ID</span>
                                </a>
                            </li>
                            @endif

                            @if (hasRole('Security Card') || hasRole('Admin Security'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.security.vehicle_pass_approval.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">directions_car</i>
                                    <span class="hide-menu">Requested Vehicle Pass</span>
                                </a>
                            </li>
                            @endif

                            @if (!hasRole('Security Card') && !hasRole('Admin Security'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.security.employee_idcard_approval.approval1') ? 'active' : '' }}"
                                   href="{{ route('admin.security.employee_idcard_approval.approval1') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">approval</i>
                                    <span class="hide-menu">Id Card Approval</span>
                                </a>
                            </li>
                            @endif

                            @if (hasRole('Security Card'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.security.employee_idcard_approval.approval2') ? 'active' : '' }}"
                                   href="{{ route('admin.security.employee_idcard_approval.approval2') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">credit_card</i>
                                    <span class="hide-menu">Requested ID Card</span>
                                </a>
                            </li>
                            @endif

                            @if (hasRole('Admin Security'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.security.employee_idcard_approval.approval3') ? 'active' : '' }}"
                                   href="{{ route('admin.security.employee_idcard_approval.approval3') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">verified_user</i>
                                    <span class="hide-menu">Id Card Approval</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.security.idcard_card_type.*') ? 'active' : '' }}"
                                   href="{{ route('admin.security.idcard_card_type.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">style</i>
                                    <span class="hide-menu">Card Type Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.security.idcard_sub_type.*') ? 'active' : '' }}"
                                   href="{{ route('admin.security.idcard_sub_type.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">account_tree</i>
                                    <span class="hide-menu">Sub Type Mapping</span>
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
