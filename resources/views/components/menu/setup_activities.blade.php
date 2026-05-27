@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-setup-mini-6',
    'panelMenuTitle' => 'USER MANAGEMENT',
    'panelMenuClass' => 'sidebar-setup-user-management-menu',
])
                            @php
                                $showUserManagement = hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST');
                                $estateSelfServiceRoles = hasRole('Staff') || hasRole('Student-OT') || hasRole('Doctor') || hasRole('Guest Faculty') || hasRole('Internal Faculty');
                                $isPermanentEstateEmployee = false;
                                $user = Auth::user();
                                if ($user && $estateSelfServiceRoles && \Illuminate\Support\Facades\Schema::hasTable('employee_master')) {
                                    $empIdCandidates = array_values(array_filter([
                                        $user->user_id ?? null,
                                        $user->pk ?? null,
                                    ], fn ($v) => $v !== null && $v !== ''));
                                    if (!empty($empIdCandidates)) {
                                        $empQuery = \Illuminate\Support\Facades\DB::table('employee_master');
                                        $empQuery->whereIn('pk', $empIdCandidates);
                                        if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old')) {
                                            $empQuery->orWhereIn('pk_old', $empIdCandidates);
                                        }
                                        $empRow = $empQuery->select('payroll')->first();
                                        if ($empRow && (int) ($empRow->payroll ?? 0) === 0) {
                                            $isPermanentEstateEmployee = true;
                                        }
                                    }
                                }
                                $showEstateSection = $showUserManagement || hasRole('Estate') || hasRole('Super Admin') || hasRole('HAC Person') || $estateSelfServiceRoles;
                                $isEstateAdmin = hasRole('Estate') || hasRole('Super Admin');
                                $isHACPerson = hasRole('HAC Person');
                            @endphp
                            @if($showUserManagement)

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#employeeCollapse" role="button"
                                    aria-expanded="false" aria-controls="employeeCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">badge</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Employee</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="employeeCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('member.*') ? 'active' : '' }}"
                                                href="{{ route('member.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Employee Master</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.employee.type.*') ? 'active' : '' }}"
                                                href="{{ route('master.employee.type.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Employee Type</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.employee.group.*') ? 'active' : '' }}"
                                                href="{{ route('master.employee.group.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Employee Group</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.department.master.*') ? 'active' : '' }}"
                                                href="{{ route('master.department.master.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Department Master</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.designation.*') ? 'active' : '' }}"
                                                href="{{ route('master.designation.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Designation Master</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.caste.category.*') ? 'active' : '' }}"
                                                href="{{ route('master.caste.category.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Caste Category</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">school</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="facultyCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.appellation.*') ? 'active' : '' }}"
                                                href="{{ route('master.appellation.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Appellation Master</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.faculty.expertise.*') ? 'active' : '' }}"
                                                href="{{ route('master.faculty.expertise.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Faculty Expertise</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('master.faculty.type.master.*') ? 'active' : '' }}"
                                                href="{{ route('master.faculty.type.master.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Faculty Type</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('faculty.*') ? 'active' : '' }}"
                                                href="{{ route('faculty.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Faculty</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.faculty.whos-who') ? 'active' : '' }}"
                                                href="{{ route('admin.faculty.whos-who') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Who's Who</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#userManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="userManagementCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">admin_panel_settings</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Roles &amp; Permissions</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="userManagementCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                                                href="{{ route('admin.roles.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">Roles</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                                                href="{{ route('admin.users.index') }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">User Permissions</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            @if (hasRole('Admin') || hasRole('Super Admin'))
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.setup.quick_links.*') ? 'active' : '' }}"
                                        href="{{ route('admin.setup.quick_links.index') }}">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">link</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Quick Links Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.setup.useful_links.*') ? 'active' : '' }}"
                                        href="{{ route('admin.setup.useful_links.index') }}">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">bookmark</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Useful Links Master</span>
                                    </a>
                                </li>
                            @endif

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('course-repository.*') ? 'active' : '' }}"
                                    href="{{ route('course-repository.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">folder_special</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Course Repository</span>
                                </a>
                            </li>
                            @endif
@include('components.menu.partials.panel-shell-close')
