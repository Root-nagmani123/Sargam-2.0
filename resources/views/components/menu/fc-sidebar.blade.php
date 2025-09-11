<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-3" data-simplebar="">
    <ul class="sidebar-menu" id="sidebarnav">

        <!-- ======= GENERAL ======= -->
        <li class="sidebar-item">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseGeneral" role="button" aria-expanded="false" aria-controls="collapseGeneral"
                style="background-color: #af2910 !important; color: white; border-radius: 10px;">
                <span class="fw-bold">General</span>
                <i class="bi bi-chevron-down text-white"></i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseGeneral">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('frontpage.index') }}" target="_blank">
                    <iconify-icon icon="material-symbols:home-outline-rounded"></iconify-icon>
                    <span class="hide-menu">User Landing Page (User)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.frontpage') }}">
                    <iconify-icon icon="mdi:view-dashboard-outline"></iconify-icon>
                    <span class="hide-menu">Landing Page (Admin)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.path.page') }}">
                    <iconify-icon icon="mdi:map-marker-path"></iconify-icon>
                    <span class="hide-menu">Path Page (Admin)</span>
                </a>
            </li>
        </ul>

        <!-- ======= REGISTRATION MANAGEMENT ======= -->
        <li class="sidebar-item mt-2">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseRegistration" role="button" aria-expanded="false" aria-controls="collapseRegistration"
                style="background-color: #af2910 !important; color: white; border-radius: 10px;">
                <span class="fw-bold">Registration Management</span>
                <i class="bi bi-chevron-down text-white"></i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseRegistration">
            <li class="sidebar-item {{ request()->routeIs('forms.*') ? 'active' : '' }}">
                <a class="sidebar-link {{ request()->routeIs('forms.*') ? 'active' : '' }}"
                    href="{{ route('forms.index') }}">
                    <iconify-icon icon="mdi:form-textbox"></iconify-icon>
                    <span class="hide-menu">Registration Forms</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.registration.index') }}">
                    <iconify-icon icon="mdi:database-outline"></iconify-icon>
                    <span class="hide-menu">Registration Master</span>
                </a>
            </li>
        </ul>

        <!-- ======= EXEMPTION ======= -->
        <li class="sidebar-item mt-2">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseExemption" role="button" aria-expanded="false" aria-controls="collapseExemption"
                style="background-color: #af2910 !important; color: white; border-radius: 10px;">
                <span class="fw-bold">Exemption</span>
                <i class="bi bi-chevron-down text-white"></i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseExemption">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.exemptionIndex') }}">
                    <iconify-icon icon="mdi:shield-check-outline"></iconify-icon>
                    <span class="hide-menu">Exemption Categories (Master)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('exemptions.datalist') }}">
                    <iconify-icon icon="mdi:file-document-multiple-outline"></iconify-icon>
                    <span class="hide-menu">Applications (Registration & Exemption)</span>
                </a>
            </li>
        </ul>

        <!-- ======= DATABASE TOOLS ======= -->
        <li class="sidebar-item mt-2">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseDatabase" role="button" aria-expanded="false" aria-controls="collapseDatabase"
                style="background-color: #af2910 !important; color: white; border-radius: 10px;">
                <span class="fw-bold">Database Tools</span>
                <i class="bi bi-chevron-down text-white"></i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseDatabase">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.column.form') }}">
                    <iconify-icon icon="mdi:table-column-plus-after"></iconify-icon>
                    <span class="hide-menu">Manage DB Columns</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('registration-page.create') }}">
                    <iconify-icon icon="mdi:image-outline"></iconify-icon>
                    <span class="hide-menu">Manage Logo</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('students.index') }}">
                    <iconify-icon icon="mdi:image-outline"></iconify-icon>
                    <span class="hide-menu">Data Migration</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('enrollment.create') }}">
                    <iconify-icon icon="mdi:image-outline"></iconify-icon>
                    <span class="hide-menu">Course Enrollment</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('student.courses') }}">
                    <iconify-icon icon="mdi:image-outline"></iconify-icon>
                    <span class="hide-menu">Student Course Mapping</span>
                </a>
            </li>
        </ul>

        <!-- ======= DOCUMENTS ======= -->
        <li class="sidebar-item mt-2">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseDocuments" role="button" aria-expanded="false" aria-controls="collapseDocuments"
                style="background-color: #af2910 !important; color: white; border-radius: 10px;">
                <span class="fw-bold">Documents</span>
                <i class="bi bi-chevron-down text-white"></i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseDocuments">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('fc.joining.index', ['formId' => 30]) }}">
                    <iconify-icon icon="mdi:table-column-plus-after"></iconify-icon>
                    <span class="hide-menu">Joining Documents (User)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.joining-documents.index', ['formId' => 30]) }}">
                    <iconify-icon icon="mdi:table-column-plus-after"></iconify-icon>
                    <span class="hide-menu">Report (Admin Only)</span>
                </a>
            </li>
        </ul>

    </ul>
</nav>
