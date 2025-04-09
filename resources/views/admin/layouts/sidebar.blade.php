<!-- Sidebar Start -->
<aside class="side-mini-panel with-vertical">
    <div>
        <!-- ---------------------------------- -->
        <!-- Start Vertical Layout Sidebar -->
        <!-- ---------------------------------- -->
        <div class="iconbar">
            <div>
                <div class="mini-nav">
                    <div class="brand-logo d-flex align-items-center justify-content-center">
                        <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                            <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-7"></iconify-icon>
                        </a>
                    </div>
                    <ul class="mini-nav-ul simplebar-scrollable-y" data-simplebar="init">
                        <div class="simplebar-wrapper" style="margin: 0px;">
                            <div class="simplebar-height-auto-observer-wrapper">
                                <div class="simplebar-height-auto-observer"></div>
                            </div>
                            <div class="simplebar-mask">
                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                        aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                                        <div class="simplebar-content" style="padding: 0px;">

                                            <!-- --------------------------------------------------------------------------------------------------------- -->
                                            <!-- Dashboards -->
                                            <!-- --------------------------------------------------------------------------------------------------------- -->
                                            <li class="mini-nav-item selected" id="mini-1">
                                                <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                    data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                    data-bs-title="General">
                                                    <iconify-icon icon="solar:layers-line-duotone" class="fs-7">
                                                    </iconify-icon>
                                                </a>
                                            </li>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="simplebar-placeholder" style="width: 80px; height: 537px;"></div>
                        </div>
                        <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                            <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                        </div>
                        <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                            <div class="simplebar-scrollbar"
                                style="height: 75px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
                        </div>
                    </ul>

                </div>
                <div class="sidebarmenu">
                    <div class="brand-logo d-flex align-items-center nav-logo">
                        <a href="#" class="text-nowrap logo-img">
                            <img src="{{asset('admin_assets/images/logos/logo.svg')}}" alt="Logo">
                        </a>

                    </div>
                    <!-- ---------------------------------- -->
                    <!-- Dashboard -->
                    <!-- ---------------------------------- -->
                    <nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-1"
                        data-simplebar="init">
                        <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
                            <div class="simplebar-height-auto-observer-wrapper">
                                <div class="simplebar-height-auto-observer"></div>
                            </div>
                            <div class="simplebar-mask">
                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                        aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                                        <div class="simplebar-content" style="padding: 0px 20px 24px;">
                                            <ul class="sidebar-menu" id="sidebarnav">
                                                <!-- ---------------------------------- -->
                                                <!-- Home -->
                                                <!-- ---------------------------------- -->
                                                <li class="nav-small-cap">
                                                    <span class="hide-menu">General</span>
                                                </li>
                                                <!-- ---------------------------------- -->
                                                <!-- Dashboard -->
                                                <!-- ---------------------------------- -->
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('dashboard') }}" id="get-url"
                                                        aria-expanded="false">
                                                        <iconify-icon icon="solar:atom-line-duotone"></iconify-icon>
                                                        <span class="hide-menu">Dashboard</span>
                                                    </a>
                                                </li>
                                                <!-- <li class="sidebar-item">
                                                    <a class="sidebar-link has-arrow" href="javascript:void(0)"
                                                        aria-expanded="false">
                                                        <iconify-icon icon="solar:home-angle-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">User Management</span>
                                                    </a>
                                                    <ul aria-expanded="false" class="collapse first-level">
                                                        <li class="sidebar-item">
                                                            <a class="sidebar-link"
                                                                href="{{ route('admin.users.index') }}">
                                                                <span class="icon-small"></span>
                                                                Users
                                                            </a>
                                                        </li>
                                                        <li class="sidebar-item">
                                                            <a class="sidebar-link"
                                                                href="{{ route('admin.roles.index') }}">
                                                                <span class="icon-small"></span>
                                                                Roles
                                                            </a>
                                                        </li>
                                                        <li class="sidebar-item">
                                                            <a class="sidebar-link"
                                                                href="{{ route('admin.permissions.index') }}">
                                                                <span class="icon-small"></span>
                                                                Permissions
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li> -->
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('member.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Member</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('faculty.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Faculty</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('programme.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Programme</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('batch.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Batch</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('stream.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Stream</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('subject.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Subject</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('country.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Country</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('state.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">State</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('curriculum.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Course Curriculum</span>
                                                    </a>
                                                </li>
                                                <li class="sidebar-item">
                                                    <a class="sidebar-link" href="{{ route('mapping.index') }}"
                                                        id="get-url" aria-expanded="false">
                                                        <iconify-icon icon="solar:shield-user-line-duotone">
                                                        </iconify-icon>
                                                        <span class="hide-menu">Faculty Topic Mapping</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <span class="sidebar-divider"></span>
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
                            <div class="simplebar-scrollbar"
                                style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</aside>
<!--  Sidebar End -->