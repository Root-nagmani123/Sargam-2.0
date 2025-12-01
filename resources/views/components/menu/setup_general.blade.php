<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-setup-mini-5" data-simplebar="init">
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
                            <!-- Home -->
                            <!-- ---------------------------------- -->
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
                                            Time Table
                                        </h2>
                                    </div>
                                </div>
                            </li>

                            <!-- ---------------------------------- -->
                            <!-- Academic -->
                            <!-- ---------------------------------- -->
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#calendarCollapse" role="button"
                                    aria-expanded="false" aria-controls="calendarCollapse">
                                    <span class="hide-menu fw-bold">Calendar Creation</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="calendarCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('attendance.index') }}">
                                        <span class="hide-menu">Attendance</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.index') }}">
                                        <span class="hide-menu">Send Memo / Notice</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#subjectCollapse" role="button"
                                    aria-expanded="false" aria-controls="subjectCollapse">
                                    <span class="hide-menu fw-bold">Subject & Module Master</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="subjectCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('subject.index') }}">
                                        <span class="hide-menu">Subject Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('subject-module.index') }}">
                                        <span class="hide-menu">Subject Module Master</span>
                                    </a></li>
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