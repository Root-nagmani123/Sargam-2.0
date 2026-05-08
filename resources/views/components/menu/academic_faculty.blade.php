<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-8" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">
                            @include('components.profile')

                            <div class="sidebar-section-header text-uppercase fw-bold mb-3"
                                style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                                @php
                                    $roles = session('user_roles', []);
                                @endphp
                                {{ !empty($roles) ? implode(', ', $roles) : '' }}
                            </div>

                            {{-- Course Master & Mapping (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#coursemasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="coursemasterCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">school</i>
                                        <span class="hide-menu">Course Master & Mapping</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="coursemasterCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('programme.index') }}">
                                        <span class="hide-menu">Course Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.course.group.type.index') }}">
                                        <span class="hide-menu">Course Group Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('group.mapping.index') }}">
                                        <span class="hide-menu">Course Group Mapping</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Exemption (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#exemptionmasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="exemptionmasterCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">medical_services</i>
                                        <span class="hide-menu">Exemption</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="exemptionmasterCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('student.medical.exemption.index') }}">
                                        <span class="hide-menu">Student Medical Exemption (Doctor)</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span class="hide-menu">Escort/Moderator Duty</span>
                                    </a>
                                </li>
                                {{-- Exemption Master (nested collapsible) --}}
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                        data-bs-toggle="collapse" href="#exemptionCollapse" role="button"
                                        aria-expanded="false" aria-controls="exemptionCollapse">
                                        <span class="hide-menu">Exemption Master</span>
                                        <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled" id="exemptionCollapse">
                                    <li class="sidebar-item">
                                        <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.exemption.category.master.index') }}">
                                            <span class="hide-menu">Exemption Category</span>
                                        </a>
                                    </li>
                                    <li class="sidebar-item">
                                        <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.exemption.medical.speciality.index') }}">
                                            <span class="hide-menu">Exemption Medical Speciality</span>
                                        </a>
                                    </li>
                                    <li class="sidebar-item">
                                        <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.mdo_duty_type.index') }}">
                                            <span class="hide-menu">Duty Type</span>
                                        </a>
                                    </li>
                                </ul>
                            </ul>

                            {{-- Memo Master & Mapping (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#memoCollapse" role="button"
                                    aria-expanded="false" aria-controls="memoCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">description</i>
                                        <span class="hide-menu">Memo Master & Mapping</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="memoCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.memo.type.master.index') }}">
                                        <span class="hide-menu">Memo Type Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.memo.conclusion.master.index') }}">
                                        <span class="hide-menu">Memo Conclusion Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('course.memo.decision.index') }}">
                                        <span class="hide-menu">Memo Course Mapping</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('memo.notice.management.user') }}">
                                        <span class="hide-menu">Memo & Notice Chat (User)</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Session Feedback (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#feedbackCollapse" role="button"
                                    aria-expanded="false" aria-controls="feedbackCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">rate_review</i>
                                        <span class="hide-menu">Session Feedback</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="feedbackCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('feedback.get.feedbackList') }}">
                                        <span class="hide-menu">Feedback</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('feedback.get.studentFeedback') }}">
                                        <span class="hide-menu">Student Feedback</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Faculty menu items --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('calendar.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">calendar_today</i>
                                    <span class="hide-menu">My Time Table</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('timetable-report.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">summarize</i>
                                    <span class="hide-menu">Timetable Session Report</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('attendance.user_attendance.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">fact_check</i>
                                    <span class="hide-menu">OT - Attendance</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('faculty.mdo.escort.exception.view') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">supervisor_account</i>
                                    <span class="hide-menu">OT - MDO / Escort Duty</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('medical.exception.faculty.view') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">local_hospital</i>
                                    <span class="hide-menu">OT - Medical Exemption</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('faculty.notice.memo.view') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">mail</i>
                                    <span class="hide-menu">OT - Memo / Notice</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{route('feedback.get.feedbackList')}}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">feedback</i>
                                    <span class="hide-menu">My Feedback</span>
                                </a>
                            </li>

                            {{-- OT menu items --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('calendar.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">calendar_today</i>
                                    <span class="hide-menu">My Time Table</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('attendance.user_attendance.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">fact_check</i>
                                    <span class="hide-menu">My Attendance</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('ot.mdo.escrot.exemption.view') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">supervisor_account</i>
                                    <span class="hide-menu">Session Moderator/Escort Duty</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('medical.exception.ot.view') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">local_hospital</i>
                                    <span class="hide-menu">Medical Exemption</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('ot.notice.memo.view') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">mail</i>
                                    <span class="hide-menu">Memo/Notice</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('feedback.get.studentFeedback') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">rate_review</i>
                                    <span class="hide-menu">Session Feedback</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('peer.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">group_work</i>
                                    <span class="hide-menu">Peer Evaluation</span>
                                </a>
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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
    </div>
</nav>
