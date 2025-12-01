<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-6" data-simplebar="init">
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
                            <li class="nav-small-cap">
                                <span class="hide-menu fw-bold text-white">User Management</span>
                            </li>
                            <!-- ---------------------------------- -->
                            <!-- Academic -->
                            <!-- ---------------------------------- -->
                            {{-- EMPLOYEE --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#employeeCollapse" role="button"
                                    aria-expanded="false" aria-controls="employeeCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Employee</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="employeeCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.employee.type.index') }}">
                                        <span
                                            class="hide-menu">Employee Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.employee.group.index') }}">
                                        <span
                                            class="hide-menu">Employee Group</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.department.master.index') }}">
                                        <span
                                            class="hide-menu">Department Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.designation.index') }}">
                                        <span
                                            class="hide-menu">Designation Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.caste.category.index') }}">
                                        <span
                                            class="hide-menu">Caste Category</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('member.index') }}">
                                        <span
                                            class="hide-menu">Member</span>
                                    </a></li>
                            </ul>

                            {{-- FACULTY --}}
                          
                            @if(hasRole('GUEST FACULTY'))
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Faculty</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="facultyCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.expertise.index') }}">
                                        <span
                                            class="hide-menu">Faculty Expertise</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.type.master.index') }}">
                                        <span
                                            class="hide-menu">Faculty Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('faculty.index') }}">
                                        <span
                                            class="hide-menu">Faculty</span>
                                    </a></li>
                            </ul>
                            @endif

                            {{-- USER MANAGEMENT --}}
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#userManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="userManagementCollapse"
                                    >
                                    <span class="hide-menu fw-bold">User Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="userManagementCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.users.index') }}">
                                        <span
                                            class="hide-menu">Users</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.roles.index') }}">
                                        <span
                                            class="hide-menu">Roles</span>
                                    </a></li>
                                {{-- <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.permissions.index') }}">
                                        <span
                                            class="hide-menu">Permissions</span>
                                    </a></li> --}}
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