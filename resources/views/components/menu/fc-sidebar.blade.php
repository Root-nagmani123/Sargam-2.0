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
                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('frontpage.index') }}" target="_blank">
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
        @endif
@include('components.menu.partials.panel-shell-close')
