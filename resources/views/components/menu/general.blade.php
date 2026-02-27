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
                                    <span class="hide-menu">Dashboard</span>
                                </a>
                            </li>
                             <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.notice.index') }}">
                                        <span class="hide-menu">Notice Notifications</span>
                                    </a></li>

                            <!-- Faculty Dashboard Route -->
                            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('admin.dashboard') }}">
                                    <span class="hide-menu">Dashbaord</span>
                                </a></li>
                            @if(hasRole('Doctor'))
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('student.medical.exemption.index') }}">
                                    <span class="hide-menu">Student Medical Exemption (Doctor)</span>
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
                                        <span class="hide-menu fw-bold">Quick Links</span>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">keyboard_arrow_down</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">E-Office</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">Medical Center</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">E-Learning</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">Library</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">OM & Circular of DOPT</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">Photo Gallery</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">OT Missconduct Complaint</span>
                                        </a></li>
                                </ul>

                                {{-- COURSE --}}
                                <li class="sidebar-item" style="background: #4077ad;
    border-radius: 30px 0px 0px 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center"
                                        data-bs-toggle="collapse" href="#courseCollapse" role="button"
                                        aria-expanded="false" aria-controls="courseCollapse">
                                        <span class="hide-menu fw-bold">Usefull Links</span>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">keyboard_arrow_down</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled ps-3" id="courseCollapse">
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">About Academy</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">About Mussoorie</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">SOP</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">Required Items Mess</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">LBSNAA Telephone Director</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">Organisation Structure</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                            <span class="hide-menu">LBSNAA Website</span>
                                        </a></li>
                                </ul>
                            </ul>
                            <!-- OTs Dashboard Route -->
                            <ul class="sidebar-menu" id="sidebarnav">

                            </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>