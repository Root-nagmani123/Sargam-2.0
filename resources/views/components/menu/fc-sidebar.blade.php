<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-3" data-simplebar="">
    <ul class="sidebar-menu" id="sidebarnav">
        <!-- ======= GENERAL SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">General</span>
        </li>
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
        <!-- Divider -->
        <span class="sidebar-divider"></span>
        <!-- ======= REGISTRATION SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px;line-height:10px;"><span
                class="hide-menu">Registration
                Management</span></li>
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
        <!-- Divider -->
        <span class="sidebar-divider"></span>
        <!-- ======= EXEMPTION SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Exemption</span>
        </li>
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
        <!-- Divider -->
        <span class="sidebar-divider"></span>
        <!-- ======= DATABASE MANAGEMENT ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Database
                Tools</span></li>
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
        <!-- ======= Joining Documents ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span class="hide-menu">
                Documents</span></li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('fc.joining.index') }}">
                <iconify-icon icon="mdi:table-column-plus-after"></iconify-icon>
                <span class="hide-menu">Joining Documents(User) </span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('admin.joining-documents.index') }}">
                <iconify-icon icon="mdi:table-column-plus-after"></iconify-icon>
                <span class="hide-menu">Report(Admin Only)</span>
            </a>
        </li>
    </ul>
</nav>
