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
                                    <iconify-icon icon="solar:shield-user-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Dashboard</span>
                                </a>
                            </li>
                            <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
                                <a class="sidebar-link" href="{{ route('admin.dashboard') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:atom-line-duotone"></iconify-icon>
                                    <span class="hide-menu">Dashboard</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('member.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:shield-user-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Member</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('expertise.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:airbuds-case-minimalistic-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Area of Expertise</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('stream.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:widget-4-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Stream</span>
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
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('curriculum.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:iphone-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Course Curriculum</span>
                                </a>
                            </li>

                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('calendar.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:calendar-mark-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Calendar</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('section.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:calendar-mark-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Section</span>
                                </a>
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
                                <a class="sidebar-link" href="{{ route('attendance.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:calendar-mark-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Attendance</span>
                                </a>
                            </li>
                            
                            <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= Feedback SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span class="hide-menu">Feedback</span></li>
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
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span class="hide-menu">User Management</span></li>
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