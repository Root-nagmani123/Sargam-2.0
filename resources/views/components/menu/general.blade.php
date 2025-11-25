<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-1" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 0px 20px 24px;">
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
                                            class="hide-menu">Course Group Mapping</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('subject.index') }}">
                                        <span class="hide-menu">Subject Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('subject-module.index') }}">
                                        <span
                                            class="hide-menu">Subject Module Master</span>
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
                            </ul>

                            {{-- EXEMPTION DUTY --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#exemptionDutyCollapse" role="button"
                                    aria-expanded="false" aria-controls="exemptionDutyCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Exemption Duty</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="exemptionDutyCollapse">
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
                                        href="{{ route('memo.notice.management.index') }}">
                                        <span
                                            class="hide-menu">Memo Notice Management</span>
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

                            {{-- EMPLOYEE --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#employeeCollapse" role="button"
                                    aria-expanded="false" aria-controls="employeeCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Employee</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="employeeCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.employee.type.index') }}">
                                        <span
                                            class="hide-menu">Employee Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.employee.group.index') }}">
                                        <span
                                            class="hide-menu">Employee Group</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.department.master.index') }}">
                                        <span
                                            class="hide-menu">Department Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.designation.index') }}">
                                        <span
                                            class="hide-menu">Designation Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.caste.category.index') }}">
                                        <span
                                            class="hide-menu">Caste Category</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('member.index') }}">
                                        <span
                                            class="hide-menu">Member</span>
                                    </a></li>
                            </ul>

                            {{-- FACULTY --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Faculty</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="facultyCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.expertise.index') }}">
                                        <span
                                            class="hide-menu">Faculty Expertise</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.type.master.index') }}">
                                        <span
                                            class="hide-menu">Faculty Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('faculty.index') }}">
                                        <span
                                            class="hide-menu">Faculty</span>
                                    </a></li>
                                <!--<li class="sidebar-item"><a class="sidebar-link" href="{{ route('mapping.index') }}">
                                        <iconify-icon icon="solar:map-arrow-up-bold-duotone"></iconify-icon><span
                                            class="hide-menu">Faculty Topic Mapping</span>
                                    </a></li>-->
                            </ul>

                            {{-- USER MANAGEMENT --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#userManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="userManagementCollapse"
                                    >
                                    <span class="hide-menu fw-bold">User Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="userManagementCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.users.index') }}">
                                        <span
                                            class="hide-menu">Users</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.roles.index') }}">
                                        <span
                                            class="hide-menu">Roles</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.permissions.index') }}">
                                        <span
                                            class="hide-menu">Permissions</span>
                                    </a></li>
                            </ul>
                             {{-- USER Feedback --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#userFeedbackCollapse" role="button"
                                    aria-expanded="false" aria-controls="userFeedbackCollapse"
                                    >
                                    <span class="hide-menu fw-bold">User Feedback</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="userFeedbackCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('feedback.get.feedbackList') }}">
                                        <span
                                            class="hide-menu">Feedback</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('feedback.get.studentFeedback') }}">
                                        <span
                                            class="hide-menu">Student Feedback</span>
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
