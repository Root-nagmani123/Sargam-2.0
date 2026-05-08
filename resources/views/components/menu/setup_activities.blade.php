<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-6" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">

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

                        <div class="sidebar-section-header text-uppercase fw-bold mb-3"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            User Management
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            {{-- Employee (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#employeeCollapse" role="button"
                                    aria-expanded="false" aria-controls="employeeCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">work</i>
                                        <span class="hide-menu">Employee</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="employeeCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('member.index') }}">
                                        <span class="hide-menu">Employee Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.employee.type.index') }}">
                                        <span class="hide-menu">Employee Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.employee.group.index') }}">
                                        <span class="hide-menu">Employee Group</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.department.master.index') }}">
                                        <span class="hide-menu">Department Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.designation.index') }}">
                                        <span class="hide-menu">Designation Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.caste.category.index') }}">
                                        <span class="hide-menu">Caste Category</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Faculty (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">school</i>
                                        <span class="hide-menu">Faculty</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="facultyCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('master.appellation.*') ? 'active' : '' }}"
                                        href="{{ route('master.appellation.index') }}">
                                        <span class="hide-menu">Appellation Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.faculty.expertise.index') }}">
                                        <span class="hide-menu">Faculty Expertise</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('master.faculty.type.master.index') }}">
                                        <span class="hide-menu">Faculty Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('faculty.index') }}">
                                        <span class="hide-menu">Faculty</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.faculty.whos-who') ? 'active' : '' }}"
                                        href="{{ route('admin.faculty.whos-who') }}">
                                        <span class="hide-menu">Who's Who</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Roles & Permissions (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#userManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="userManagementCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">admin_panel_settings</i>
                                        <span class="hide-menu">Roles & Permissions</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="userManagementCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.roles.index') }}">
                                        <span class="hide-menu">Roles</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.users.index') }}">
                                        <span class="hide-menu">User Permissions</span>
                                    </a>
                                </li>
                            </ul>

                            @if (hasRole('Admin') || hasRole('Super Admin'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.setup.quick_links.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">link</i>
                                    <span class="hide-menu">Quick Links Master</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.setup.useful_links.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">bookmarks</i>
                                    <span class="hide-menu">Useful Links Master</span>
                                </a>
                            </li>
                            @endif

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('course-repository.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">folder_copy</i>
                                    <span class="hide-menu">Course Repository</span>
                                </a>
                            </li>

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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
    </div>
</nav>
