@php
    $coursemasterOpen = request()->routeIs('programme.*')
        || request()->routeIs('master.course.group.type.*')
        || request()->routeIs('group.mapping.*');
    $exemptionmasterOpen = request()->routeIs('student.medical.exemption.*')
        || request()->routeIs('mdo-escrot-exemption.*')
        || request()->routeIs('mdo-escort-exemption.*')
        || request()->routeIs('master.exemption.*')
        || request()->routeIs('master.mdo_duty_type.*');
    $memoOpen = request()->routeIs('master.memo.*')
        || request()->routeIs('course.memo.*')
        || request()->routeIs('master.discipline.*');
    $reportOpen = request()->routeIs('admin.feedback.*')
        || request()->routeIs('feedback.*');
    $facultyOpen = request()->routeIs('calendar.*')
        || request()->routeIs('attendance.user_attendance.*')
        || request()->routeIs('faculty.*')
        || request()->routeIs('medical.exception.faculty.*')
        || request()->routeIs('feedback.get.feedbackList');
    $otOpen = request()->routeIs('calendar.*')
        || request()->routeIs('attendance.user_attendance.*')
        || request()->routeIs('ot.*')
        || request()->routeIs('medical.exception.ot.*')
        || request()->routeIs('memo.*')
        || request()->routeIs('peer.*')
        || request()->routeIs('admin.course-repository.user.*');
@endphp
<nav class="sidebar-nav sidebar-panel-menu simplebar-scrollable-y" id="menu-right-setup-mini-4" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content sidebar-panel-menu__content">
                        <p class="sidebar-panel-menu__title text-uppercase text-secondary small fw-semibold mb-3 px-1">
                            ACADEMICS
                        </p>
                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">

                            @if (hasRole('Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') ||
                            hasRole('IST'))

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#coursemasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="coursemasterCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">menu_book</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Course Master & Mapping</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="coursemasterCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('programme.*') ? 'active' : '' }}"
                                        href="{{ route('programme.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Course Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.course.group.type.*') ? 'active' : '' }}"
                                        href="{{ route('master.course.group.type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Course Group Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('group.mapping.*') ? 'active' : '' }}"
                                        href="{{ route('group.mapping.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Course Group Mapping</span>
                                    </a>
                                </li>
                                </ul>
                                </li>
                            </ul>



                            @if (!hasRole('Training-MCTP') && !hasRole('IST'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#exemptionmasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="exemptionmasterCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">fact_check</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Exemption</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>

                            <ul class="collapse list-unstyled mb-2" id="exemptionmasterCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('student.medical.exemption.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Student
                                            Medical Exemption (Doctor)</span>
                                    </a></li>
                                @if (hasRole('Training-MCTP') || hasRole('IST'))
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Escort/Moderator
                                            Duty</span>
                                    </a></li>
                                @endif
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Escort/Moderator
                                            Duty</span>
                                    </a></li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                        data-bs-toggle="collapse" href="#exemptionCollapse" role="button"
                                        aria-expanded="false" aria-controls="exemptionCollapse">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Exemption Master</span>
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled mb-2" id="exemptionCollapse">
                                    <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                    <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2"
                                            href="{{ route('master.exemption.category.master.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Exemption
                                                Category</span>
                                        </a></li>
                                    <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2"
                                            href="{{ route('master.exemption.medical.speciality.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Exemption
                                                Medical Speciality</span>
                                        </a></li>
                                    <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2"
                                            href="{{ route('master.mdo_duty_type.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Duty
                                                Type</span>
                                        </a></li>
                                    </ul>
                                    </li>
                                </ul>
                                </ul>
                                </li>
                            </ul>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#memoCollapse" role="button"
                                    aria-expanded="false" aria-controls="memoCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">sticky_note_2</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Memo Master & Mapping</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="memoCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.type.master.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Memo Type
                                            Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.conclusion.master.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Memo
                                            Conclusion Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('course.memo.decision.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Memo Course
                                            Mapping</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.discipline.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Discipline
                                            Master</span>
                                    </a></li>

                                {{-- <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.user') }}">
                                <span class="hide-menu">Memo & Notice Chat (User).</span>
                                </a></li> --}}
                                </ul>
                                </li>
                            </ul>
                            @endif
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#reportCollapse" role="button"
                                    aria-expanded="false" aria-controls="reportCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">analytics</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Session Feedback Report</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="reportCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.feedback.feedback_details') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty Feedback
                                            with OT Details</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.feedback.faculty_view') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty Feedback with
                                            Comments</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('feedback.average') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty Feedback
                                            Average</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.feedback.database') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty Feedback
                                            Database</span>
                                    </a></li>

                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.feedback.pending.students') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Pending Feedback
                                            Details (Course Wise)</span>
                                    </a>
                                </li>
                               {{-- <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.feedback.pending.summary') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Pending Feedback
                                            Summary (Student Count Wise)</span>
                                    </a>
                                </li> --}}

                                {{-- <li class="sidebar-item">
                                        <a class="sidebar-link" href="{{ route('admin.feedback.pending') }}">
                                <span class="hide-menu">Pending Feedback</span>
                                </a>
                                </li> --}}

                                </ul>
                                </li>
                            </ul>
                            @endif

                            <!-- faculty menu start -->
                            @if (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">groups</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty View</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="facultyCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('calendar.*') ? 'active' : '' }}"
                                        href="{{ route('calendar.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">My Time Table</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('attendance.user_attendance.*') ? 'active' : '' }}"
                                        href="{{ route('attendance.user_attendance.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">OT - Attendance</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('faculty.mdo.escort.exception.*') ? 'active' : '' }}"
                                        href="{{ route('faculty.mdo.escort.exception.view') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">OT - MDO / Escort Duty</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('medical.exception.faculty.*') ? 'active' : '' }}"
                                        href="{{ route('medical.exception.faculty.view') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">OT - Medical Exemption</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('faculty.notice.memo.*') ? 'active' : '' }}"
                                        href="{{ route('faculty.notice.memo.view') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">OT - Memo / Notice</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('feedback.get.feedbackList') ? 'active' : '' }}"
                                        href="{{ route('feedback.get.feedbackList') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">My Feedback</span>
                                    </a>
                                </li>
                                </ul>
                                </li>
                            </ul>
                            @if (is_faculty_portal_user())
                                @include('components.menu.partials.faculty_session_feedback_report_menu', [
                                    'reportCollapseId' => 'facultySessionFeedbackReportSetup',
                                    'linkLabelClass' => 'hide-menu small small-sm-normal text-nowrap',
                                ])
                            @endif
                            @endif
                            <!-- faculty menu end -->
                            @if (hasRole('Doctor'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('medical.exception.faculty.*') ? 'active' : '' }}"
                                    href="{{ route('medical.exception.faculty.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">OT - Medical Exemption</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('faculty.notice.memo.*') ? 'active' : '' }}"
                                    href="{{ route('faculty.notice.memo.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">OT - Memo / Notice</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.feedback.feedback_details') ? 'active' : '' }}"
                                    href="{{ route('admin.feedback.feedback_details') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Feedback</span>
                                </a>
                            </li>
                            @endif

                        <!-- OTs menu start -->
                        @if (hasRole('Student-OT') || hasRole('Admin'))
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                data-bs-toggle="collapse" href="#otCollapse" role="button"
                                aria-expanded="false" aria-controls="otCollapse">
                                <span class="d-flex align-items-center gap-2 min-w-0">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">school</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">OT View</span>
                                </span>
                                <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                            </a>
                        </li>
                        <ul class="collapse list-unstyled mb-2" id="otCollapse">
                            <li class="sidebar-panel-submenu-tree">
                            <ul class="list-unstyled mb-0">
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('calendar.*') ? 'active' : '' }}"
                                    href="{{ route('calendar.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Time Table</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('attendance.user_attendance.*') ? 'active' : '' }}"
                                    href="{{ route('attendance.user_attendance.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Attendance</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('ot.mdo.escrot.exemption.*') ? 'active' : '' }}"
                                    href="{{ route('ot.mdo.escrot.exemption.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Session Moderator/Escort Duty</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('medical.exception.ot.*') ? 'active' : '' }}"
                                    href="{{ route('medical.exception.ot.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Medical Exemption</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('memo.discipline.*') ? 'active' : '' }}"
                                    href="{{ route('memo.discipline.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Displine Memo Action</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('memo.notice.management.user') ? 'active' : '' }}"
                                    href="{{ route('memo.notice.management.user') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Memo/Notice action</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('ot.notice.memo.*') ? 'active' : '' }}"
                                    href="{{ route('ot.notice.memo.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Memo / Notice History</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('feedback.get.studentFeedback') ? 'active' : '' }}"
                                    href="{{ route('feedback.get.studentFeedback') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Session Feedback</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('peer.user_groups') ? 'active' : '' }}"
                                    href="{{ route('peer.user_groups') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Peer Evaluation</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.course-repository.user.*') ? 'active' : '' }}"
                                    href="{{ route('admin.course-repository.user.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Course Repository - User</span>
                                </a>
                            </li>
                            </ul>
                            </li>
                        </ul>
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
