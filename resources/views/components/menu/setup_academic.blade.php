<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-setup-mini-4" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 20px 24px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            <!-- ---------------------------------- -->
                            <!-- Home -->
                            <!-- ---------------------------------- -->
                            <!-- ---------------------------------- -->
                            <!-- Academic -->
                            <!-- ---------------------------------- -->

                            <!-- Main Container with Improved Layout -->

                            @if(hasRole('Admin') || hasRole('Training'))


                            <li class="nav-section" role="listitem">

                                <!-- Main Container with Improved Layout -->
                                <div class="d-flex align-items-center justify-content-between w-100">

                                    <!-- Left Side: Collapse Button with Enhanced Accessibility -->
                                    <div class="d-flex align-items-center mb-3">

                                        <!-- Section Title with Proper Semantic Markup -->
                                        <h2 class="section-title text-white m-0"
                                            style="font-size: 1.125rem; font-weight: 600; letter-spacing: 0.25px;">
                                            @php
                                            $roles = session('user_roles', []);

                                            @endphp
                                            {{ !empty($roles) ? implode(', ', $roles) : '' }}
                                        </h2>
                                    </div>
                                </div>
                            </li>

                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#coursemasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="coursemasterCollapse">
                                    <span class="hide-menu fw-bold">Course Master & Mapping</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="coursemasterCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('programme.index') }}">
                                        <span class="hide-menu">Course Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.course.group.type.index') }}">
                                        <span class="hide-menu">Course Group Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('group.mapping.index') }}">
                                        <span class="hide-menu">Course Group Mapping</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item" style="background: #4077ad;
                            border-radius: 30px 0px 0px 30px;
                            width: 100%;
                            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                            min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#exemptionmasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="exemptionmasterCollapse">
                                    <span class="hide-menu fw-bold">Exemption</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="exemptionmasterCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('student.medical.exemption.index') }}">
                                        <span class="hide-menu">Student Medical Exemption (Doctor)</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span class="hide-menu">MDO Escort Exemption</span>
                                    </a></li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center"
                                        data-bs-toggle="collapse" href="#exemptionCollapse" role="button"
                                        aria-expanded="false" aria-controls="exemptionCollapse">
                                        <span class="hide-menu fw-bold">Exemption Master</span>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">keyboard_arrow_down</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled ps-3" id="exemptionCollapse">
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.exemption.category.master.index') }}">
                                            <span class="hide-menu">Exemption Category</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.exemption.medical.speciality.index') }}">
                                            <span class="hide-menu">Exemption Medical Speciality</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.mdo_duty_type.index') }}">
                                            <span class="hide-menu">Duty Type</span>
                                        </a></li>
                                </ul>
                            </ul>
                            <li class="sidebar-item" style="background: #4077ad;
    border-radius: 30px 0px 0px 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#memoCollapse" role="button" aria-expanded="false"
                                    aria-controls="memoCollapse">
                                    <span class="hide-menu fw-bold">Memo Master & Mapping</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="memoCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.type.master.index') }}">
                                        <span class="hide-menu">Memo Type Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.conclusion.master.index') }}">
                                        <span class="hide-menu">Memo Conclusion Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('course.memo.decision.index') }}">
                                        <span class="hide-menu">Memo Course Mapping</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.discipline.index') }}">
                                        <span class="hide-menu">Discipline Master</span>
                                    </a></li>

                                {{-- <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.user') }}">
                                <span class="hide-menu">Memo & Notice Chat (User).</span>
                                </a></li> --}}
                            </ul>
                            <li class="sidebar-item" style="background: #4077ad;
    border-radius: 30px 0px 0px 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#reportCollapse" role="button" aria-expanded="false"
                                    aria-controls="reportCollapse">
                                    <span class="hide-menu fw-bold">Session Feedback Report</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="reportCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.feedback.feedback_details') }}">
                                        <span class="hide-menu">Faculty Feedback with Comments All Details</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.feedback.faculty_view') }}">
                                        <span class="hide-menu">Average Rating - Course / Topic wise</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('feedback.average') }}">
                                        <span class="hide-menu">Faculty Feedback Average</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.feedback.database') }}">
                                        <span class="hide-menu">Faculty Feedback Database</span>
                                    </a></li>

                            </ul>
                            @endif

                            <!-- faculty menu start -->
                            @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin'))
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse">
                                    <span class="hide-menu fw-bold">Faculty View</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="facultyCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('calendar.index') }}">
                                        <span class="hide-menu">My Time Table</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('attendance.user_attendance.index') }}">
                                        <span class="hide-menu">OT - Attendance</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('faculty.mdo.escort.exception.view') }}">
                                        <span class="hide-menu">OT - MDO / Escort Duty</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('medical.exception.faculty.view') }}">
                                        <span class="hide-menu">OT - Medical Exemption</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('faculty.notice.memo.view') }}">
                                        <span class="hide-menu">OT - Memo / Notice</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{route('feedback.get.feedbackList')}}">
                                        <span class="hide-menu">My Feedback</span>
                                    </a></li>
                            </ul>
                            @endif
                            <!-- faculty menu end -->
                            <!-- medical exemption for OTs -->
                            @if(hasRole('Doctor'))
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('medical.exception.faculty.view') }}">
                                    <span class="hide-menu">OT - Medical Exemption</span>
                                </a></li>
                            @endif

                            <!-- OTs menu start -->
                            @if(hasRole('Student-OT') || hasRole('Admin'))
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#otCollapse" role="button" aria-expanded="false"
                                    aria-controls="otCollapse">
                                    <span class="hide-menu fw-bold">OT View</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="otCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('calendar.index') }}">
                                        <span class="hide-menu">My Time Table</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('attendance.user_attendance.index') }}">
                                        <span class="hide-menu">My Attendance</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('ot.mdo.escrot.exemption.view') }}">
                                        <span class="hide-menu">MDO/Escort Duty</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('medical.exception.ot.view') }}">
                                        <span class="hide-menu">Medical Exemption</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.discipline.index') }}">
                                        <span class="hide-menu">Displine Memo Action</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('ot.notice.memo.view') }}">
                                        <span class="hide-menu">Memo/Notice activety</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('memo.notice.management.user') }}">
                                        <span class="hide-menu">Memo/Notice action</span>
                                    </a></li>

                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('feedback.get.studentFeedback') }}">
                                        <span class="hide-menu">Session Feedback</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('peer.index') }}">
                                        <span class="hide-menu">Peer Evaluation</span>
                                    </a></li>
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