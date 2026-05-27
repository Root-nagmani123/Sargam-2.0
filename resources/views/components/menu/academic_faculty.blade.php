<nav class="sidebar-nav sidebar-panel-menu simplebar-scrollable-y" id="menu-right-setup-mini-8" data-simplebar="init">
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
                             @include('components.profile')
                            <!-- ---------------------------------- -->
                            <!-- Home -->
                            <!-- ---------------------------------- -->
                            <!-- ---------------------------------- -->
                            <!-- Academic -->
                            <!-- ---------------------------------- -->
                           @php
                            $roles = session('user_roles', []);
                           @endphp
                          
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#coursemasterCollapse" role="button" aria-expanded="false"
                                    aria-controls="coursemasterCollapse"
                                    >
                                    <span class="hide-menu small small-sm-normal text-nowrap">Course Master & Mapping</span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="coursemasterCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('programme.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Course Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.course.group.type.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Course Group Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('group.mapping.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Course Group Mapping</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#exemptionmasterCollapse" role="button" aria-expanded="false"
                                    aria-controls="exemptionmasterCollapse"
                                    >
                                    <span class="hide-menu small small-sm-normal text-nowrap">Exemption</span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="exemptionmasterCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('student.medical.exemption.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Student Medical Exemption (Doctor)</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Escort/Moderator Duty</span>
                                    </a></li>
                                    <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#exemptionCollapse" role="button" aria-expanded="false"
                                    aria-controls="exemptionCollapse"
                                    >
                                    <span class="hide-menu small small-sm-normal text-nowrap">Exemption Master</span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="exemptionCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.exemption.category.master.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Exemption Category</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.exemption.medical.speciality.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Exemption Medical Speciality</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.mdo_duty_type.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Duty Type</span>
                                    </a></li>
                            </ul>
                            </ul>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#memoCollapse" role="button" aria-expanded="false"
                                    aria-controls="memoCollapse"
                                    >
                                    <span class="hide-menu small small-sm-normal text-nowrap">Memo Master & Mapping</span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="memoCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.memo.type.master.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Memo Type Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.conclusion.master.index') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Memo Conclusion Master</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('course.memo.decision.index') }}">
                                            <span
                                                class="hide-menu small small-sm-normal text-nowrap">Memo Course Mapping</span>
                                        </a></li>
                                        <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.user') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Memo & Notice Chat (User)</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#feedbackCollapse" role="button" aria-expanded="false"
                                    aria-controls="feedbackCollapse"
                                    >
                                    <span class="hide-menu small small-sm-normal text-nowrap">Session Feedback</span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="feedbackCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('feedback.get.feedbackList') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Feedback</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('feedback.get.studentFeedback') }}">
                                        <span
                                            class="hide-menu small small-sm-normal text-nowrap">Student Feedback</span>
                                    </a></li>
                            </ul>


                            <!-- faculty menu start -->
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('calendar.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Time Table</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('timetable-report.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Timetable Session Report</span>
                                </a></li>
                                  <li class="sidebar-item"><a class="sidebar-link" href="{{ route('attendance.user_attendance.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">OT - Attendance</span>
                                </a></li>
                                  <li class="sidebar-item"><a class="sidebar-link" href="{{ route('faculty.mdo.escort.exception.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">OT - MDO / Escort Duty</span>
                                </a></li>
                                  <li class="sidebar-item"><a class="sidebar-link" href="{{ route('medical.exception.faculty.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">OT - Medical Exemption</span>
                                </a></li>
                                  <li class="sidebar-item"><a class="sidebar-link" href="{{ route('faculty.notice.memo.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">OT - Memo / Notice</span>
                                </a></li>
 <li class="sidebar-item"><a class="sidebar-link" href="{{route('feedback.get.feedbackList')}}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Feedback</span>
                                </a></li>
                                @if (is_faculty_portal_user())
                                    @include('components.menu.partials.faculty_session_feedback_report_menu', [
                                        'reportCollapseId' => 'facultySessionFeedbackReportAcademics',
                                    ])
                                @endif

                            <!-- faculty menu end -->

                            <!-- OTs menu start -->
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('calendar.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Time Table</span>
                                </a></li>
                                 <li class="sidebar-item"><a class="sidebar-link" href="{{ route('attendance.user_attendance.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Attendance</span>
                                </a></li>
                                 <li class="sidebar-item"><a class="sidebar-link" href="{{ route('ot.mdo.escrot.exemption.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Session Moderator/Escort Duty</span>
                                </a></li>
                                 <li class="sidebar-item"><a class="sidebar-link" href="{{ route('medical.exception.ot.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Medical Exemption</span>
                                </a></li>
                                 <li class="sidebar-item"><a class="sidebar-link" href="{{ route('ot.notice.memo.view') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Memo/Notice</span>
                                </a></li>
                                 <li class="sidebar-item"><a class="sidebar-link" href="{{ route('feedback.get.studentFeedback') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Session Feedback</span>
                                </a></li>
                                 <li class="sidebar-item"><a class="sidebar-link" href="{{ route('peer.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Peer Evaluation</span>
                                </a></li>
                              
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