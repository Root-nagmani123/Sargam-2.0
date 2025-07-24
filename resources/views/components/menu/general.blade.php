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
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">General</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                <li class="sidebar-item d-none">
                                    <a class="sidebar-link" href="{{ route('admin.dashboard') }}">
                                        <iconify-icon icon="solar:notification-unread-bold-duotone"></iconify-icon>
                                        <span class="hide-menu">Notification</span>
                                    </a>
                                </li>
                                <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
                                    <a class="sidebar-link" href="{{ route('admin.dashboard') }}">
                                        <iconify-icon icon="solar:notification-unread-bold-duotone"></iconify-icon>
                                        <span class="hide-menu">Notifications</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- COURSE --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#courseCollapse" role="button" aria-expanded="false"
                                    aria-controls="courseCollapse"
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">Course</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="courseCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('programme.index') }}">
                                        <iconify-icon icon="solar:mask-happly-line-duotone"></iconify-icon><span
                                            class="hide-menu">Course Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.course.group.type.index') }}">
                                        <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon><span
                                            class="hide-menu">Course Group Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('group.mapping.index') }}">
                                        <iconify-icon icon="solar:calendar-mark-line-duotone"></iconify-icon><span
                                            class="hide-menu">Group Name Mapping</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('subject-module.index') }}">
                                        <iconify-icon icon="solar:widget-4-line-duotone"></iconify-icon><span
                                            class="hide-menu">Subject Module</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('subject.index') }}">
                                        <iconify-icon icon="solar:speaker-minimalistic-line-duotone"></iconify-icon>
                                        <span class="hide-menu">Subject</span>
                                    </a></li>
                            </ul>

                            {{-- EXEMPTION --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#exemptionCollapse" role="button"
                                    aria-expanded="false" aria-controls="exemptionCollapse"
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">Exemption</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="exemptionCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.exemption.category.master.index') }}">
                                        <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon><span
                                            class="hide-menu">Exemption Category</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.exemption.medical.speciality.index') }}">
                                        <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon><span
                                            class="hide-menu">Exemption Medical Speciality</span>
                                    </a></li>
                            </ul>

                            {{-- EXEMPTION DUTY --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#exemptionDutyCollapse" role="button"
                                    aria-expanded="false" aria-controls="exemptionDutyCollapse"
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">Exemption Duty</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="exemptionDutyCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('student.medical.exemption.index') }}">
                                        <iconify-icon icon="solar:feed-bold-duotone"></iconify-icon><span
                                            class="hide-menu">Student Medical Exemption</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <iconify-icon icon="solar:calendar-mark-line-duotone"></iconify-icon><span
                                            class="hide-menu">MDO Escrot Exemption</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.mdo_duty_type.index') }}">
                                        <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon><span
                                            class="hide-menu">MDO Duty Type</span>
                                    </a></li>
                            </ul>

                            {{-- MEMO --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#memoCollapse" role="button" aria-expanded="false"
                                    aria-controls="memoCollapse"
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">Memo</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="memoCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.type.master.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Memo Type Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.conclusion.master.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Memo Conclusion Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.index') }}">
                                        <iconify-icon icon="solar:feed-bold-duotone"></iconify-icon><span
                                            class="hide-menu">Memo Notice Management</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.user') }}">
                                        <iconify-icon icon="solar:feed-bold-duotone"></iconify-icon><span
                                            class="hide-menu">Memo Notice Chat (User)</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('course.memo.decision.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Memo Course Mapping</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.courseAttendanceNoticeMap.memo_notice') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Memo / Notice Creation (Admin)</span>
                                    </a></li>
                            </ul>

                            {{-- EMPLOYEE --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#employeeCollapse" role="button"
                                    aria-expanded="false" aria-controls="employeeCollapse"
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">Employee</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="employeeCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.employee.type.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Employee Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.employee.group.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Employee Group</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.department.master.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Department Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.designation.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Designation Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.caste.category.index') }}">
                                        <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                            class="hide-menu">Caste Category</span>
                                    </a></li>
                            </ul>

                            {{-- FACULTY --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse"
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">Faculty</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="facultyCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.expertise.index') }}">
                                        <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon><span
                                            class="hide-menu">Faculty Expertise</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.type.master.index') }}">
                                        <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon><span
                                            class="hide-menu">Faculty Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('faculty.index') }}">
                                        <iconify-icon icon="solar:document-text-line-duotone"></iconify-icon><span
                                            class="hide-menu">Faculty</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mapping.index') }}">
                                        <iconify-icon icon="solar:map-arrow-up-bold-duotone"></iconify-icon><span
                                            class="hide-menu">Faculty Topic Mapping</span>
                                    </a></li>
                            </ul>

                            {{-- USER MANAGEMENT --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#userManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="userManagementCollapse"
                                    style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                    <span class="hide-menu fw-bold">User Management</span>
                                    <i class="bi bi-chevron-down ms-2 text-white"></i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="userManagementCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.users.index') }}">
                                        <iconify-icon icon="solar:atom-line-duotone"></iconify-icon><span
                                            class="hide-menu">Users</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.roles.index') }}">
                                        <iconify-icon icon="solar:atom-line-duotone"></iconify-icon><span
                                            class="hide-menu">Roles</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.permissions.index') }}">
                                        <iconify-icon icon="solar:atom-line-duotone"></iconify-icon><span
                                            class="hide-menu">Permissions</span>
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