@php
    $sendDirectNoticeMenuActive = request()->routeIs('send.notice.management.*')
        || request()->routeIs('attendance.send_notice')
        || request()->routeIs('notice.direct.save');
    $attendanceMenuActive = request()->routeIs('attendance.*') && ! request()->routeIs('attendance.send_notice');
@endphp
@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-setup-mini-5',
    'panelMenuTitle' => 'TIME TABLE',
    'panelMenuClass' => 'sidebar-setup-timetable-menu',
])
                            @if(hasRole('Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('calendar.*') ? 'active' : '' }}"
                                    href="{{ route('calendar.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">calendar_month</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Calendar Creation</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ $attendanceMenuActive ? 'active' : '' }}"
                                    href="{{ route('attendance.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">event_available</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Attendance</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('timetable-report.*') ? 'active' : '' }}"
                                    href="{{ route('timetable-report.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">summarize</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Timetable Session Report</span>
                                </a>
                            </li>
                            @if(hasRole('Training-MCTP') || hasRole('IST'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('mdo-escrot-exemption.*') ? 'active' : '' }}"
                                    href="{{ route('mdo-escrot-exemption.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">verified_user</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Escort/Moderator Duty</span>
                                </a>
                            </li>
                            @endif

                            @if(! hasRole('Training-MCTP') && ! hasRole('IST'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ $sendDirectNoticeMenuActive ? 'active' : '' }}"
                                    href="{{ route('send.notice.management.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">campaign</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Send Direct Notice</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('memo.notice.management.*') ? 'active' : '' }}"
                                    href="{{ route('memo.notice.management.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">note_alt</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Send Memo / Notice</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('memo.discipline.*') ? 'active' : '' }}"
                                    href="{{ route('memo.discipline.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">gavel</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Send Discipline Memo</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.memo-notice.*') ? 'active' : '' }}"
                                    href="{{ route('admin.memo-notice.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">description</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Memo / Notice Template</span>
                                </a>
                            </li>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#userFeedbackCollapse" role="button"
                                    aria-expanded="false" aria-controls="userFeedbackCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">feedback</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Session Feedback</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="userFeedbackCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2"
                                                href="{{ route('feedback.get.feedbackList') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Feedback History</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2"
                                                href="{{ route('feedback.get.studentFeedback') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Session Feedback</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#subjectCollapse" role="button"
                                    aria-expanded="false" aria-controls="subjectCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">menu_book</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Subject &amp; Module Master</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="subjectCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('subject.*') ? 'active' : '' }}"
                                                href="{{ route('subject.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Subject Master</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('subject-module.*') ? 'active' : '' }}"
                                                href="{{ route('subject-module.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Subject Module Master</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            @endif
                            @endif
@include('components.menu.partials.panel-shell-close')
