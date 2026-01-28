<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-6" data-simplebar="init">
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
                             @if(hasRole('Admin') || hasRole('Training-Induction') ||  hasRole('Training-MCTP'))
                            <li class="nav-section" role="listitem">

                                <!-- Main Container with Improved Layout -->
                                <div class="d-flex align-items-center justify-content-between w-100">

                                    <!-- Left Side: Collapse Button with Enhanced Accessibility -->
                                    <div class="d-flex align-items-center mb-3">
                                        <!-- Section Title with Proper Semantic Markup -->
                                        <h2 class="section-title text-white m-0"
                                            style="font-size: 1.125rem; font-weight: 600; letter-spacing: 0.25px;">
                                            User Management
                                        </h2>
                                    </div>
                                </div>
                            </li>
                            <!-- ---------------------------------- -->
                            <!-- Academic -->
                            <!-- ---------------------------------- -->
                            {{-- EMPLOYEE --}}
                          
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#employeeCollapse" role="button"
                                    aria-expanded="false" aria-controls="employeeCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Employee</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="employeeCollapse">
                               <li class="sidebar-item"><a class="sidebar-link" href="{{ route('member.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Employee Master</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.employee.type.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Employee Type</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.employee.group.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Employee Group</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.department.master.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Department Master</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.designation.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Designation Master</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.caste.category.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Caste Category</span>
                                        </a></li>
                            </ul>

                            {{-- FACULTY --}}

                            <li class="sidebar-item" style="background: #4077ad;
                        border-radius: 30px 0px 0px 30px;
                        width: 100%;
                        box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                        min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#facultyCollapse" role="button"
                                    aria-expanded="false" aria-controls="facultyCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Faculty</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="facultyCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.expertise.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty Expertise</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.faculty.type.master.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('faculty.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Faculty</span>
                                    </a></li>
                            </ul>

                            {{-- USER MANAGEMENT --}}
                            <li class="sidebar-item" style="background: #4077ad;
                            border-radius: 30px 0px 0px 30px;
                            width: 100%;
                            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                            min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#userManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="userManagementCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Roles & Permissions</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="userManagementCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.roles.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Roles</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.users.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">User Permissions</span>
                                    </a></li>

                                {{-- <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.permissions.index') }}">
                                <span class="hide-menu">Permissions</span>
                                </a></li> --}}
                            </ul>
                            <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('course-repository.index') }}">
                                <span class="hide-menu">Course Repository</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.course-repository.user.index') }}">
                                <span class="hide-menu">Course Repository - User</span>
                                </a></li>
                            
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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);">
        </div>
    </div>
</nav>