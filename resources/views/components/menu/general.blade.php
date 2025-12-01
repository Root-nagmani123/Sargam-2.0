<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-1" data-simplebar="init">
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
<li class="nav-section" role="listitem">

                                <!-- Main Container with Improved Layout -->
                                <div class="d-flex align-items-center justify-content-between w-100">

                                    <!-- Left Side: Collapse Button with Enhanced Accessibility -->
                                    <div class="d-flex align-items-center">
                                        <!-- Collapse Button with ARIA labels and better focus management -->
                                        <button
                                            class="nav-link sidebartoggler d-flex align-items-center justify-content-center p-2 me-2"
                                            id="headerCollapse" aria-label="Toggle sidebar navigation"
                                            aria-expanded="true" aria-controls="sidebarContent" data-bs-toggle="tooltip"
                                            data-bs-placement="right">

                                            <!-- Improved Icon with Animation Class -->
                                            <i class="material-icons material-symbols-rounded text-white transition-all"
                                                style="font-size: 24px; transition: transform 0.3s ease;"
                                                aria-hidden="true">
                                                keyboard_arrow_left
                                            </i>

                                            <!-- Screen Reader Only Text -->
                                            <span class="visually-hidden">Toggle sidebar navigation</span>
                                        </button>

                                        <!-- Section Title with Proper Semantic Markup -->
                                        <h2 class="section-title text-white m-0"
                                            style="font-size: 1.125rem; font-weight: 600; letter-spacing: 0.25px;">
                                            General
                                        </h2>
                                    </div>
                                </div>
                            </li>
                            {{-- GENERAL --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#generalCollapse" role="button"
                                    aria-expanded="false" aria-controls="generalCollapse"
                                    >
                                    <span class="hide-menu fw-bold">General</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                <li class="sidebar-item d-none">
                                    <a class="sidebar-link" href="{{ route('admin.dashboard') }}">
                                        <iconify-icon icon="solar:notification-unread-bold-duotone"></iconify-icon>
                                        <span class="hide-menu">Dashboard</span>
                                    </a>
                                </li>
                                <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
                                    <a class="sidebar-link" href="{{ route('admin.dashboard') }}">
                                        <span class="hide-menu">Dashboard</span>
                                    </a>
                                </li>
                               <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.notice.index') }}">
                                        <span
                                            class="hide-menu">Notices</span>
                                    </a></li>
                            </ul>

                            {{-- COURSE --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#courseCollapse" role="button" aria-expanded="false"
                                    aria-controls="courseCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Course</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="courseCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('programme.index') }}">
                                        <span
                                            class="hide-menu">Notices</span>
                                    </a></li>
                            </ul>

                            {{-- MEMO --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#memoCollapse" role="button" aria-expanded="false"
                                    aria-controls="memoCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Memo</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="memoCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.courseAttendanceNoticeMap.memo_notice') }}">
                                        <span
                                            class="hide-menu">Memo / Notice Creation (Admin)</span>
                                    </a></li>
                            </ul>

                        </ul>

                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>
