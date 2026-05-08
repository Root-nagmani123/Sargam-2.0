<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-5" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">

                        @if(hasRole('Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST'))

                        <div class="sidebar-section-header text-uppercase fw-bold mb-3"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Time Table
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('calendar.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">edit_calendar</i>
                                    <span class="hide-menu">Calendar Creation</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('attendance.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">fact_check</i>
                                    <span class="hide-menu">Attendance</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('timetable-report.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">summarize</i>
                                    <span class="hide-menu">Timetable Session Report</span>
                                </a>
                            </li>

                            @if(hasRole('Training-MCTP') || hasRole('IST'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('mdo-escrot-exemption.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">supervisor_account</i>
                                    <span class="hide-menu">Escort/Moderator Duty</span>
                                </a>
                            </li>
                            @endif

                            @if(! hasRole('Training-MCTP') && ! hasRole('IST'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('send.notice.management.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">send</i>
                                    <span class="hide-menu">Send Direct Notice</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('memo.notice.management.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">mail</i>
                                    <span class="hide-menu">Send Memo / Notice</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('memo.discipline.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">gavel</i>
                                    <span class="hide-menu">Send Discipline Memo</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.memo-notice.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">article</i>
                                    <span class="hide-menu">Memo / Notice Template</span>
                                </a>
                            </li>
                            @endif

                            {{-- Session Feedback --}}
                            @if(! hasRole('Training-MCTP') && ! hasRole('IST'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#userFeedbackCollapse" role="button"
                                    aria-expanded="false" aria-controls="userFeedbackCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">rate_review</i>
                                        <span class="hide-menu">Session Feedback</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="userFeedbackCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('feedback.get.feedbackList') }}">
                                        <span class="hide-menu">Feedback History</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('feedback.get.studentFeedback') }}">
                                        <span class="hide-menu">Session Feedback</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Subject & Module Master --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#subjectCollapse" role="button"
                                    aria-expanded="false" aria-controls="subjectCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">auto_stories</i>
                                        <span class="hide-menu">Subject & Module Master</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="subjectCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('subject.index') }}">
                                        <span class="hide-menu">Subject Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('subject-module.index') }}">
                                        <span class="hide-menu">Subject Module Master</span>
                                    </a>
                                </li>
                            </ul>
                            @endif

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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
    </div>
</nav>
