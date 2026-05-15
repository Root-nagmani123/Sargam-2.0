<nav class="sidebar-nav sargam-menu-flyout simplebar-scrollable-y" id="menu-right-mini-3" data-mini-nav-target="mini-3" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content">

                        @if (hasRole('Admin') || hasRole('Training-Induction'))

                        <div class="sidebar-section-header text-uppercase fw-bold mb-1"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            FC Registration
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            {{-- General (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseGeneral" role="button"
                                    aria-expanded="false" aria-controls="collapseGeneral">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">settings</i>
                                        <span class="hide-menu">General</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseGeneral">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('frontpage.index') }}" target="_blank">
                                        <span class="hide-menu">User Landing Page (User)</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.frontpage') }}">
                                        <span class="hide-menu">Landing Page (Admin)</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.path.page') }}">
                                        <span class="hide-menu">Path Page (Admin)</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Registration Management (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseRegistration" role="button"
                                    aria-expanded="false" aria-controls="collapseRegistration">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">app_registration</i>
                                        <span class="hide-menu">Registration Management</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseRegistration">
                                <li class="sidebar-item {{ request()->routeIs('forms.*') ? 'active' : '' }}">
                                    <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('forms.*') ? 'active' : '' }}"
                                        href="{{ route('forms.index') }}">
                                        <span class="hide-menu">Registration Forms</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.registration.index') }}">
                                        <span class="hide-menu">Registration Master</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Exemption (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseExemption" role="button"
                                    aria-expanded="false" aria-controls="collapseExemption">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">medical_services</i>
                                        <span class="hide-menu">Exemption</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseExemption">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.exemptionIndex') }}">
                                        <span class="hide-menu">Exemption Categories (Master)</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('exemptions.datalist') }}">
                                        <span class="hide-menu">Applications (Registration & Exemption)</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Database Tools (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseDatabase" role="button"
                                    aria-expanded="false" aria-controls="collapseDatabase">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">database</i>
                                        <span class="hide-menu">Database Tools</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseDatabase">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.column.form') }}">
                                        <span class="hide-menu">Manage DB Columns</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('registration-page.create') }}">
                                        <span class="hide-menu">Manage Logo</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('students.index') }}">
                                        <span class="hide-menu">Data Migration</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('enrollment.create') }}">
                                        <span class="hide-menu">New Course Enrollment</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('student.courses') }}">
                                        <span class="hide-menu">Course wise OT's List</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Documents (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseDocuments" role="button"
                                    aria-expanded="false" aria-controls="collapseDocuments">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">description</i>
                                        <span class="hide-menu">Documents</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseDocuments">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('fc.joining.index', ['formId' => 30]) }}">
                                        <span class="hide-menu">Joining Documents (User)</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.joining-documents.index', ['formId' => 30]) }}">
                                        <span class="hide-menu">Report (Admin Only)</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Peer Evaluation (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapsePeerEvaluation" role="button"
                                    aria-expanded="false" aria-controls="collapsePeerEvaluation">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">group_work</i>
                                        <span class="hide-menu">Peer Evaluation</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapsePeerEvaluation">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.peer.index') }}">
                                        <span class="hide-menu">Peer Evaluation (Admin Panel)</span>
                                    </a>
                                </li>
                            </ul>

                        </ul>

                        @endif

                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: auto; height: 0px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
        <div class="simplebar-scrollbar"
            style="height: 25px; transform: translate3d(0px, 0px, 0px); display: block;"></div>
    </div>
</nav>
