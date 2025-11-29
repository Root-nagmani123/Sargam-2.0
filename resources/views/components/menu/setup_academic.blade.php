<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-4" data-simplebar="init">
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
                            <!-- ---------------------------------- -->
                            <!-- Academic -->
                            <!-- ---------------------------------- -->
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" id="get-url" aria-expanded="false">
                                    
                                    <span class="hide-menu">User Management</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#employeeCollapse" role="button" aria-expanded="false"
                                    aria-controls="employeeCollapse"
                                    >
                                    <span class="hide-menu fw-bold">Employee</span>
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="employeeCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('admin.setup.employee_type.index') }}">
                                        <span
                                            class="hide-menu">Employee Type</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.setup.employee_group.index') }}">
                                        <span
                                            class="hide-menu">Employee Group</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.setup.department_master.index') }}">
                                        <span
                                            class="hide-menu">Department Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('admin.setup.designation_master.index') }}">
                                        <span class="hide-menu">Designation Master
</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.setup.caste_category.index') }}"><span
                                            class="hide-menu">Caste Category</span>
                                    </a></li>
                                    <li class="sidebar-item">
                                        <a href="{{ route('admin.setup.member.index') }}" class="sidebar-link"><span class="hide-menu">Member</span></a>
                                    </li>
                            </ul>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" aria-expanded="false">
                                    
                                    <span class="hide-menu">Faculty</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" aria-expanded="false">
                                    
                                    <span class="hide-menu">Course</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" aria-expanded="false">
                                    
                                    <span class="hide-menu">Exemption</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" aria-expanded="false">
                                    
                                    <span class="hide-menu">Time Table</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" aria-expanded="false">
                                    
                                    <span class="hide-menu">Memo</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#" aria-expanded="false">
                                    
                                    <span class="hide-menu">User Feedback</span>
                                </a>
                            </li>

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