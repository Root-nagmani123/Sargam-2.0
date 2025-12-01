<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-mini-4" data-simplebar="init">
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
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" id="get-url" aria-expanded="false">
                                    
                                    <span class="hide-menu">Training</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#coursemasterCollapse" role="button" aria-expanded="false"
                                    aria-controls="coursemasterCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Course Master & Mapping</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="coursemasterCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('programme.index') }}">
                                        <span
                                            class="hide-menu">Course Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.course.group.type.index') }}">
                                        <span
                                            class="hide-menu">Course Group Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('group.mapping.index') }}">
                                        <span
                                            class="hide-menu">Course Group Mapping</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#exemptionmasterCollapse" role="button" aria-expanded="false"
                                    aria-controls="exemptionmasterCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Exemption</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="exemptionmasterCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('student.medical.exemption.index') }}">
                                        <span
                                            class="hide-menu">Student Medical Exemption (Doctor)</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('mdo-escrot-exemption.index') }}">
                                        <span
                                            class="hide-menu">MDO Escort Exemption</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#exemptionCollapse" role="button" aria-expanded="false"
                                    aria-controls="exemptionCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Exemption Master</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="exemptionCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.exemption.category.master.index') }}">
                                        <span
                                            class="hide-menu">Exemption Category</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.exemption.medical.speciality.index') }}">
                                        <span
                                            class="hide-menu">Exemption Medical Speciality</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.mdo_duty_type.index') }}">
                                        <span
                                            class="hide-menu">Duty Type</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#memoCollapse" role="button" aria-expanded="false"
                                    aria-controls="memoCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Memo Master & Mapping</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="memoCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.memo.type.master.index') }}">
                                        <span
                                            class="hide-menu">Memo Type Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.memo.conclusion.master.index') }}">
                                        <span
                                            class="hide-menu">Memo Conclusion Master</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="">
                                            <span
                                                class="hide-menu">Memo Course Mapping</span>
                                        </a></li>
                                        <li class="sidebar-item"><a class="sidebar-link"
                                        href="">
                                        <span
                                            class="hide-menu">Memo & Notice Chat (User)</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#feedbackCollapse" role="button" aria-expanded="false"
                                    aria-controls="feedbackCollapse"
                                    >
                                    <span class="hide-menu fw-bold">User Feedback</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="feedbackCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('feedback.get.feedbackList') }}">
                                        <span
                                            class="hide-menu">Feedback</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('feedback.get.studentFeedback') }}">
                                        <span
                                            class="hide-menu">Student Feedback</span>
                                    </a></li>
                            </ul>

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