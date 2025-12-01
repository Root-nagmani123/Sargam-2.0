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
                                            class="hide-menu">Course Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.course.group.type.index') }}">
                                        <span
                                            class="hide-menu">Course Group Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('group.mapping.index') }}">
                                        <span
                                            class="hide-menu">Course Group Mapping</span>
                                    </a></li>
                            </ul>

                            {{-- EXEMPTION --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#exemptionCollapse" role="button"
                                    aria-expanded="false" aria-controls="exemptionCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Exemption</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="exemptionCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.exemption.category.master.index') }}">
                                        <span
                                            class="hide-menu">Exemption Category</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.exemption.medical.speciality.index') }}">
                                        <span
                                            class="hide-menu">Exemption Medical Speciality</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('student.medical.exemption.index') }}">
                                        <span
                                            class="hide-menu">Student Medical Exemption</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span
                                            class="hide-menu">MDO Escrot Exemption</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.mdo_duty_type.index') }}">
                                        <span
                                            class="hide-menu">MDO Duty Type</span>
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
                                        href="{{ route('master.memo.type.master.index') }}">
                                        <span
                                            class="hide-menu">Memo Type Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.conclusion.master.index') }}">
                                        <span
                                            class="hide-menu">Memo Conclusion Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.user') }}">
                                        <span
                                            class="hide-menu">Memo Notice Chat (User)</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('course.memo.decision.index') }}">
                                        <span
                                            class="hide-menu">Memo Course Mapping</span>
                                    </a></li>
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
