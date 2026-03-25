<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-1" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            {{-- GENERAL --}}

                            <!-- Dashboard Link -->
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Dashboard</span>
                                </a>
                            </li>
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <!-- Participant / Dashboard Statistics -->
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('admin.dashboard-statistics.*') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard-statistics.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Batch Profile</span>
                                </a>
                            </li>
                                    @endif
                                      <!-- Notice Notification Route -->
                             <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.notice.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Notice Notifications</span>
                                    </a></li>

                            <!-- Faculty Dashboard Route -->
                            @if(hasRole('Doctor'))
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('student.medical.exemption.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Student Medical Exemption (Doctor)</span>
                                </a></li>
                            @endif



                            <ul class="sidebar-menu" id="sidebarnav">
                                <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center"
                                        data-bs-toggle="collapse" href="#generalCollapse" role="button"
                                        aria-expanded="false" aria-controls="generalCollapse">
                                        <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Quick Links</span>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                    <li class="sidebar-item"><a class="sidebar-link" href="https://eoffice.lbsnaa.gov.in/" target="_blank">
                                            <span class="hide-menu small small-sm-normal text-nowrap">E-Office</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="http://cghs.lbsnaa.gov.in/" target="_blank">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Medical Center</span>
                                        </a></li>
                                    <!-- <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu small small-sm-normal text-nowrap">E-Learning</span>
                                        </a></li> -->
                                    <li class="sidebar-item"><a class="sidebar-link" href="https://idpbridge.myloft.xyz/simplesaml/module.php/core/loginuserpass.php" target="_blank">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Library</span>
                                        </a></li>
                                    <!-- <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu small small-sm-normal text-nowrap">OM & Circular of DOPT</span>
                                        </a></li> -->
                                    <li class="sidebar-item"><a class="sidebar-link" href="https://rcentre.lbsnaa.gov.in/web/" target="_blank">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Photo Gallery</span>
                                        </a></li>
                                    <!-- <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu small small-sm-normal text-nowrap">OT Missconduct Complaint</span>
                                        </a></li> -->
                                </ul>



                                <ul class="sidebar-menu" id="sidebarnav">
                                <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center"
                                        data-bs-toggle="collapse" href="#securityRequestsCollapse" role="button"
                                        aria-expanded="false" aria-controls="securityRequestsCollapse">
                                        <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Security Requests Links</span>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled ps-3" id="securityRequestsCollapse">
                                 <li class="sidebar-item">
                                    <a class="sidebar-link" href="{{ route('admin.employee_idcard.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">ID Card List</span>
                                    </a>
                                </li>
                                
                                     <li class="sidebar-item">
                                        <a class="sidebar-link" href="{{ route('admin.duplicate_idcard.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Request Duplicate ID Card</span>
                                        </a>
                                    </li>
                                  <li class="sidebar-item">
                                        <a class="sidebar-link" href="{{ route('admin.security.vehicle_pass.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Vehicle Pass Request</span>
                                        </a>
                                    </li>

                                    <li class="sidebar-item">
                                        <a class="sidebar-link" href="{{ route('admin.family_idcard.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Request Family ID Card</span>
                                        </a>
                                    </li>
                                    
                                </ul>

                                 <ul class="sidebar-menu" id="sidebarnav">
                                <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center"
                                        data-bs-toggle="collapse" href="#centcomCollapse" role="button"
                                        aria-expanded="false" aria-controls="centcomCollapse">
                                        <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Centcom Links</span>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled ps-3" id="centcomCollapse">
                               
                                     <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-management.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">All Issues</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-management.centcom') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">CENTCOM -  Assigned Complaints</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none" href="{{ route('admin.issue-management.create') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Log New Issue</span>
                                </a>
                            </li>
                                    
                                </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>