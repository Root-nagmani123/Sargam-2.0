<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-3" data-simplebar="">
    <ul class="sidebar-menu" id="sidebarnav">
        <li class="nav-section" role="listitem">

            <!-- Main Container with Improved Layout -->
            <div class="d-flex align-items-center justify-content-between w-100">

                <!-- Left Side: Collapse Button with Enhanced Accessibility -->
                <div class="d-flex align-items-center">
                    <!-- Collapse Button with ARIA labels and better focus management -->
                    <button class="nav-link sidebartoggler d-flex align-items-center justify-content-center p-2 me-2"
                        id="headerCollapse" aria-label="Toggle sidebar navigation" aria-expanded="true"
                        aria-controls="sidebarContent" data-bs-toggle="tooltip" data-bs-placement="right">

                        <!-- Improved Icon with Animation Class -->
                        <i class="material-icons material-symbols-rounded text-white transition-all"
                            style="font-size: 24px; transition: transform 0.3s ease;" aria-hidden="true">
                            keyboard_arrow_left
                        </i>

                        <!-- Screen Reader Only Text -->
                        <span class="visually-hidden">Toggle sidebar navigation</span>
                    </button>

                    <!-- Section Title with Proper Semantic Markup -->
                    <h2 class="section-title text-white m-0"
                        style="font-size: 1.125rem; font-weight: 600; letter-spacing: 0.25px;">
                        FC Registration
                    </h2>
                </div>
            </div>
        </li>
        <!-- ======= GENERAL ======= -->
        <li class="sidebar-item" style="background: #4077ad;
    border-radius: 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseGeneral" role="button" aria-expanded="false" aria-controls="collapseGeneral">
                <span class="fw-bold">General</span>
                <i class="material-icons menu-icon material-symbols-rounded"
                    style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseGeneral">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('frontpage.index') }}" target="_blank">

                    <span class="hide-menu">User Landing Page (User)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.frontpage') }}">

                    <span class="hide-menu">Landing Page (Admin)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.path.page') }}">
                    <span class="hide-menu">Path Page (Admin)</span>
                </a>
            </li>
        </ul>

        <!-- ======= REGISTRATION MANAGEMENT ======= -->
        <li class="sidebar-item mt-2" style="background: #4077ad;
    border-radius: 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseRegistration" role="button" aria-expanded="false" aria-controls="collapseRegistration">
                <span class="fw-bold">Registration Management</span>
                <i class="material-icons menu-icon material-symbols-rounded"
                    style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseRegistration">
            <li class="sidebar-item {{ request()->routeIs('forms.*') ? 'active' : '' }}">
                <a class="sidebar-link {{ request()->routeIs('forms.*') ? 'active' : '' }}"
                    href="{{ route('forms.index') }}">
                    <span class="hide-menu">Registration Forms</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.registration.index') }}">
                    <span class="hide-menu">Registration Master</span>
                </a>
            </li>
        </ul>

        <!-- ======= EXEMPTION ======= -->
        <li class="sidebar-item mt-2" style="background: #4077ad;
    border-radius: 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseExemption" role="button" aria-expanded="false" aria-controls="collapseExemption">
                <span class="fw-bold">Exemption</span>
                <i class="material-icons menu-icon material-symbols-rounded"
                    style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseExemption">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.exemptionIndex') }}">
                    <span class="hide-menu">Exemption Categories (Master)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('exemptions.datalist') }}">
                    <span class="hide-menu">Applications (Registration & Exemption)</span>
                </a>
            </li>
        </ul>

        <!-- ======= DATABASE TOOLS ======= -->
        <li class="sidebar-item mt-2" style="background: #4077ad;
    border-radius: 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseDatabase" role="button" aria-expanded="false" aria-controls="collapseDatabase">
                <span class="fw-bold">Database Tools</span>
                <i class="material-icons menu-icon material-symbols-rounded"
                    style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseDatabase">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.column.form') }}">
                    <span class="hide-menu">Manage DB Columns</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('registration-page.create') }}">
                    <span class="hide-menu">Manage Logo</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('students.index') }}">
                    <span class="hide-menu">Data Migration</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('enrollment.create') }}">
                    <span class="hide-menu">New Course Enrollment</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('student.courses') }}">
                    <span class="hide-menu">Course wise OT's List</span>
                </a>
            </li>
        </ul>

        <!-- ======= DOCUMENTS ======= -->
        <li class="sidebar-item mt-2" style="background: #4077ad;
    border-radius: 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseDocuments" role="button" aria-expanded="false" aria-controls="collapseDocuments">
                <span class="fw-bold">Documents</span>
                <i class="material-icons menu-icon material-symbols-rounded"
                    style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseDocuments">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('fc.joining.index', ['formId' => 30]) }}">
                    <span class="hide-menu">Joining Documents (User)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.joining-documents.index', ['formId' => 30]) }}">
                    <span class="hide-menu">Report (Admin Only)</span>
                </a>
            </li>
        </ul>

        <!-- ======= PEER EVALUATION ======= -->
        <li class="sidebar-item mt-2" style="background: #4077ad;
    border-radius: 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapsePeerEvaluation" role="button" aria-expanded="false"
                aria-controls="collapsePeerEvaluation">
                <span class="fw-bold">Peer Evaluation</span>
                <i class="material-icons menu-icon material-symbols-rounded"
                    style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapsePeerEvaluation">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.peer.index') }}">
                    <span class="hide-menu">Peer Evaluation (Admin Panel)</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('peer.user_groups') }}">
                    <span class="hide-menu">Peer Evaluation Form (User Panel)</span>
                </a>
            </li>
        </ul>
    </ul>
</nav>