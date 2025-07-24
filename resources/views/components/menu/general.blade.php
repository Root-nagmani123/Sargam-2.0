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
                            <!-- ---------------------------------- -->
                            <!-- Home -->
                            <!-- ---------------------------------- -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">General</span>
                            </li>
                            <!-- ---------------------------------- -->
                            <!-- Dashboard -->
                            <!-- ---------------------------------- -->
                            <li class="sidebar-item d-none">
                                <a class="sidebar-link" href="{{ route('admin.dashboard') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:notification-unread-bold-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Notification</span>
                                </a>
                            </li>
                            <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
                                <a class="sidebar-link" href="{{ route('admin.dashboard') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:notification-unread-bold-duotone"></iconify-icon>
                                    <span class="hide-menu">Notifications</span>
                                </a>
                            </li>
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Course</span>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('programme.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:mask-happly-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Course Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.course.group.type.index') }}"
                                    id="get-url" aria-expanded="false">
                                    <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                                    <span class="hide-menu">Course Group Type</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('group.mapping.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:calendar-mark-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Group Name Mapping</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('subject-module.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:widget-4-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Subject Module</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('subject.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:speaker-minimalistic-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Subject</span>
                                </a>
                            </li>

                            <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= Exemption SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Exemption</span></li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.exemption.category.master.index') }}"
                                    id="get-url" aria-expanded="false">
                                    <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                                    <span class="hide-menu">Exemption Category</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.exemption.medical.speciality.index') }}"
                                    id="get-url" aria-expanded="false">
                                    <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                                    <span class="hide-menu">Exemption Medical Speciality</span>
                                </a>
                            </li>
                            <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= REGISTRATION SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Memo</span></li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.memo.type.master.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Memo Type Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.memo.conclusion.master.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Memo Conclusion Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('memo.notice.management.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:feed-bold-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Memo Notice Management</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('memo.notice.management.user') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:feed-bold-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Memo Notice Chat (User)</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('course.memo.decision.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Memo Course Mapping</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link"
                                    href="{{ route('admin.courseAttendanceNoticeMap.memo_notice') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Memo / Notice Creation (Admin)</span>
                                </a>

                                <!-- Divider -->
                                <span class="sidebar-divider"></span>
                                <!-- ======= REGISTRATION SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Employee</span></li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.employee.type.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Employee Type</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.employee.group.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Employee Group</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.department.master.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Department Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.designation.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Designation Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.caste.category.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Caste Category</span>
                                </a>
                            </li>


                            <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= REGISTRATION SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Faculty</span></li>

                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.faculty.expertise.index') }}"
                                    id="get-url" aria-expanded="false">
                                    <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                                    <span class="hide-menu">Faculty Expertise</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.faculty.type.master.index') }}"
                                    id="get-url" aria-expanded="false">
                                    <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                                    <span class="hide-menu">Faculty Type</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('faculty.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:document-text-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Faculty</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('mapping.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:map-arrow-up-bold-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Faculty Topic Mapping</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.department.master.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Department Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.designation.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Designation Master</span>
                                </a>
                            </li>
                            <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= User Management SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Exemption Duty</span>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('student.medical.exemption.index') }}"
                                    id="get-url" aria-expanded="false">
                                    <iconify-icon icon="solar:feed-bold-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Student Medical Exemption</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('mdo-escrot-exemption.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:calendar-mark-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">MDO Escrot Exemption</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('master.mdo_duty_type.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                                    <span class="hide-menu">MDO Duty Type</span>
                                </a>
                            </li>

                            <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= Feedback SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Feedback</span>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('feedback.get.feedbackList') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:feed-bold-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Feedback</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('feedback.get.studentFeedback') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:feed-bold-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Student Feedback</span>
                                </a>
                            </li>


                            <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= User Management SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">User Management</span>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('admin.users.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:atom-line-duotone"></iconify-icon>
                                    <span class="hide-menu">Users</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('admin.roles.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:atom-line-duotone"></iconify-icon>
                                    <span class="hide-menu">Roles</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('admin.permissions.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:atom-line-duotone"></iconify-icon>
                                    <span class="hide-menu">Permissions</span>
                                </a>
                            </li>
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