@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-mini-3',
    'panelMenuTitle' => 'FC REGISTRATION',
    'panelMenuClass' => 'sidebar-fc-registration-menu',
])
        @if (hasRole('Admin') || hasRole('Training-Induction'))

            <li class="sidebar-item mb-1">
                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                    data-bs-toggle="collapse" href="#collapseGeneral" role="button" aria-expanded="false"
                    aria-controls="collapseGeneral">
                    <span class="d-flex align-items-center gap-2 min-w-0">
                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">public</i>
                        <span class="hide-menu small small-sm-normal text-nowrap">General</span>
                    </span>
                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled mb-2" id="collapseGeneral">
                <li class="sidebar-panel-submenu-tree">
                    <ul class="list-unstyled mb-0">
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('frontpage.index') }}" target="_blank"
                                title="Generic landing page (no programme in URL). For a named programme, copy Landing Page URL from Form Management → Edit form.">
                                <span class="hide-menu small small-sm-normal text-nowrap">User Landing Page (User)</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.frontpage') ? 'active' : '' }}"
                                href="{{ route('admin.frontpage') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Landing Page (Admin)</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.path.page') ? 'active' : '' }}"
                                href="{{ route('admin.path.page') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Path Page (Admin)</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <li class="sidebar-item mb-1">
                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                    data-bs-toggle="collapse" href="#collapseRegistration" role="button" aria-expanded="false"
                    aria-controls="collapseRegistration">
                    <span class="d-flex align-items-center gap-2 min-w-0">
                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">app_registration</i>
                        <span class="hide-menu small small-sm-normal text-nowrap">Registration Management</span>
                    </span>
                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled mb-2" id="collapseRegistration">
                <li class="sidebar-panel-submenu-tree">
                    <ul class="list-unstyled mb-0">
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('forms.*') ? 'active' : '' }}"
                                href="{{ route('forms.index') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Registration Forms</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.registration.*') ? 'active' : '' }}"
                                href="{{ route('admin.registration.index') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Registration Master</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <li class="sidebar-item mb-1">
                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                    data-bs-toggle="collapse" href="#collapseExemption" role="button" aria-expanded="false"
                    aria-controls="collapseExemption">
                    <span class="d-flex align-items-center gap-2 min-w-0">
                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">rule_folder</i>
                        <span class="hide-menu small small-sm-normal text-nowrap">Exemption</span>
                    </span>
                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled mb-2" id="collapseExemption">
                <li class="sidebar-panel-submenu-tree">
                    <ul class="list-unstyled mb-0">
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.exemptionIndex') ? 'active' : '' }}"
                                href="{{ route('admin.exemptionIndex') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Exemption Categories (Master)</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('exemptions.*') ? 'active' : '' }}"
                                href="{{ route('exemptions.datalist') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Applications (Registration & Exemption)</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <li class="sidebar-item mb-1">
                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                    data-bs-toggle="collapse" href="#collapseDatabase" role="button" aria-expanded="false"
                    aria-controls="collapseDatabase">
                    <span class="d-flex align-items-center gap-2 min-w-0">
                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">database</i>
                        <span class="hide-menu small small-sm-normal text-nowrap">Database Tools</span>
                    </span>
                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled mb-2" id="collapseDatabase">
                <li class="sidebar-panel-submenu-tree">
                    <ul class="list-unstyled mb-0">
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.column.form') ? 'active' : '' }}"
                                href="{{ route('admin.column.form') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Manage DB Columns</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('registration-page.*') ? 'active' : '' }}"
                                href="{{ route('registration-page.create') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Manage Logo</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('students.*') ? 'active' : '' }}"
                                href="{{ route('students.index') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Data Migration</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('enrollment.*') ? 'active' : '' }}"
                                href="{{ route('enrollment.create') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">New Course Enrollment</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('student.courses') ? 'active' : '' }}"
                                href="{{ route('student.courses') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Course wise OT's List</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <li class="sidebar-item mb-1">
                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                    data-bs-toggle="collapse" href="#collapseDocuments" role="button" aria-expanded="false"
                    aria-controls="collapseDocuments">
                    <span class="d-flex align-items-center gap-2 min-w-0">
                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">folder_open</i>
                        <span class="hide-menu small small-sm-normal text-nowrap">Documents</span>
                    </span>
                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled mb-2" id="collapseDocuments">
                <li class="sidebar-panel-submenu-tree">
                    <ul class="list-unstyled mb-0">
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('fc.joining.index', ['formId' => 30]) }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Joining Documents (User)</span>
                            </a>
                        </li>
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.joining-documents.*') ? 'active' : '' }}"
                                href="{{ route('admin.joining-documents.index', ['formId' => 30]) }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Report (Admin Only)</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.sample-documents.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.sample-documents.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.sample-documents.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Sample Document Master</span>
                    </a>
                </li>
            </ul>

            <li class="sidebar-item mb-1">
                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                    data-bs-toggle="collapse" href="#collapsePeerEvaluation" role="button" aria-expanded="false"
                    aria-controls="collapsePeerEvaluation">
                    <span class="d-flex align-items-center gap-2 min-w-0">
                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">groups</i>
                        <span class="hide-menu small small-sm-normal text-nowrap">Peer Evaluation</span>
                    </span>
                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled mb-2" id="collapsePeerEvaluation">
                <li class="sidebar-panel-submenu-tree">
                    <ul class="list-unstyled mb-0">
                        <li class="sidebar-item mb-1">
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.peer.*') ? 'active' : '' }}"
                                href="{{ route('admin.peer.index') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Peer Evaluation (Admin Panel)</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- ======= FC REG (routes/fc_registration.php) — Admin ======= -->
            <li class="sidebar-item mt-2"
                style="background: #4077ad;
                border-radius: 30px 0px 0px 30px;
                width: 100%;
                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                min-width: 250px;">
                <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                    href="#collapseFcRegAdmin" role="button" aria-expanded="false" aria-controls="collapseFcRegAdmin">
                    <span class="fw-bold">FC Reg — Admin</span>
                    <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                        style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled ps-3" id="collapseFcRegAdmin" data-sidebar-no-auto-expand="true">
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.form-builder.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.form-builder.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.form-builder.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Form Builder</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.forms.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.forms.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.forms.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Form Management</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activities.*') && !request()->routeIs('fc-reg.admin.activities.reports.*', 'fc-reg.admin.activities.medical.*', 'fc-reg.admin.activities.status.*') && !request()->routeIs('fc-reg.admin.activity-setup.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activities.*') && !request()->routeIs('fc-reg.admin.activities.reports.*', 'fc-reg.admin.activities.medical.*', 'fc-reg.admin.activities.status.*') && !request()->routeIs('fc-reg.admin.activity-setup.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activities.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Activities</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activities.status.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activities.status.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activities.status.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Activity status</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activities.reports.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activities.reports.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activities.reports.summary') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Activity Reports</span>
                    </a>
                </li>
                @if($fcSidebarShowMedical ?? false)
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activities.medical.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activities.medical.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activities.medical.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Activities Medical</span>
                    </a>
                </li>
                @endif
                @if($fcActivityNavCanSetup ?? false)
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activity-setup.departments.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activity-setup.departments.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activity-setup.departments.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Setup — Departments</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activity-setup.masters.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activity-setup.masters.*') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activity-setup.masters.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Setup — Activities master</span>
                    </a>
                </li>
                @endif
                <li class="sidebar-item {{ request()->routeIs('admin.travel.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('admin.travel.*') ? 'active' : '' }}"
                        href="{{ route('admin.travel.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Travel Plans (Admin)</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                        href="{{ route('admin.reports.overview') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">FC Reports</span>
                    </a>
                </li>
            </ul>

            <!-- ======= FC ACTIVITY STATUS (separate from FC Reg — Admin) ======= -->
            <li class="sidebar-item mt-2"
                style="background: #4077ad;
                border-radius: 30px 0px 0px 30px;
                width: 100%;
                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                min-width: 250px;">
                <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                    href="#collapseFcActivityStatus" role="button" aria-expanded="false"
                    aria-controls="collapseFcActivityStatus">
                    <span class="fw-bold">FC Activity Status</span>
                    <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                        style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
                </a>
            </li>
            <ul class="collapse list-unstyled ps-3" id="collapseFcActivityStatus" data-sidebar-no-auto-expand="true">
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activities.status.index') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activities.status.index') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activities.status.index') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">Choose department</span>
                    </a>
                </li>
                @foreach(($fcActivityNavDepartments ?? collect()) as $depNav)
                @php
                    $gridActive = request()->routeIs('fc-reg.admin.activities.status.grid') && request()->segment(6) === $depNav->code;
                @endphp
                <li class="sidebar-item {{ $gridActive ? 'active' : '' }}">
                    <a class="sidebar-link {{ $gridActive ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activities.status.grid', $depNav->code) }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">{{ $depNav->name }}</span>
                    </a>
                </li>
                @endforeach
                <li class="sidebar-item {{ request()->routeIs('fc-reg.admin.activities.status.matrix') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('fc-reg.admin.activities.status.matrix') ? 'active' : '' }}"
                        href="{{ route('fc-reg.admin.activities.status.matrix') }}">
                        <span class="hide-menu small small-sm-normal text-nowrap">All departments (matrix)</span>
                    </a>
                </li>
            </ul>
        @endif
@include('components.menu.partials.panel-shell-close')
