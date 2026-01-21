<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-setup-mini-5" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 20px 24px">
                        <ul class="sidebar-menu" id="sidebarnav">
                            <!-- ---------------------------------- -->
                            <!-- Home -->
                            <!-- ---------------------------------- -->
                             @if(hasRole('Admin') || hasRole('Training-Induction') ||  hasRole('Training-MCTP'))
                            <li class="nav-section" role="listitem">

                                <!-- Main Container with Improved Layout -->
                                <div class="d-flex align-items-center justify-content-between w-100">

                                    <!-- Left Side: Collapse Button with Enhanced Accessibility -->
                                    <div class="d-flex align-items-center mb-3">

                                        <!-- Section Title with Proper Semantic Markup -->
                                        <h2 class="section-title text-white m-0"
                                            style="font-size: 1.125rem; font-weight: 600; letter-spacing: 0.25px;">
                                            Time Table
                                        </h2>
                                    </div>
                                </div>
                            </li>

                            <!-- ---------------------------------- -->
                            <!-- Academic -->
                            <!-- ---------------------------------- -->
                            
                            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('calendar.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Calendar Creation</span>
                                </a></li>
                            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('attendance.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Attendance</span>
                                </a></li>
                            @if(hasRole('Training-MCTP'))
                             <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Escort/Moderator Duty</span>
                                    </a></li>
                            @endif
                            
                              
                                @if(! hasRole('Training-MCTP'))
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('send.notice.management.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Send Direct Notice</span>
                                </a></li>
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('memo.notice.management.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Send Memo / Notice</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('memo.discipline.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Send Discipline Memo</span>
                                </a></li>
                               
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('admin.memo-notice.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Memo / Notice Template</span>
                                </a></li>
                                @endif
                            {{-- USER Feedback --}}
                              @if(! hasRole('Training-MCTP'))
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#userFeedbackCollapse" role="button"
                                    aria-expanded="false" aria-controls="userFeedbackCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Session Feedback</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="userFeedbackCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('feedback.get.feedbackList') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Feedback History</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('feedback.get.studentFeedback') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Session Feedback</span>
                                    </a></li>

                            </ul>
                           
                            <li class="sidebar-item" style="background: #4077ad;
                                    border-radius: 30px 0px 0px 30px;
                                    width: 100%;
                                    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                    min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#subjectCollapse" role="button"
                                    aria-expanded="false" aria-controls="subjectCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Subject & Module Master</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="subjectCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('subject.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Subject Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('subject-module.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Subject Module Master</span>
                                    </a></li>
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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);">
        </div>
    </div>
</nav>