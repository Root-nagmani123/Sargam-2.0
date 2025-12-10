<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-7" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 0px 0px 20px 24px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            @include('components.profile')
                          
                            <li class="nav-section" role="listitem">
                            @if(hasRole('Admin') || hasRole('Training'))

                                <!-- Main Container with Improved Layout -->
                                <div class="d-flex align-items-center justify-content-between w-100">

                                    <!-- Left Side: Collapse Button with Enhanced Accessibility -->
                                    <div class="d-flex align-items-center">
                                        <!-- Collapse Button with ARIA labels and better focus management -->
                                        <button
                                            class="nav-link sidebartoggler d-flex align-items-center justify-content-center p-2 me-2"
                                            id="headerCollapse" aria-label="Toggle sidebar navigation"
                                            aria-expanded="true" aria-controls="sidebarContent" data-bs-toggle="tooltip"
                                            data-bs-placement="right">

                                            <!-- Improved Icon with Animation Class -->
                                            <i class="material-icons material-symbols-rounded text-white transition-all"
                                                style="font-size: 24px; transition: transform 0.3s ease;"
                                                aria-hidden="true">
                                                keyboard_arrow_left
                                            </i>

                                            <!-- Screen Reader Only Text -->
                                            <span class="visually-hidden">Toggle sidebar navigation</span>
                                        </button>

                                        <!-- Section Title with Proper Semantic Markup -->
                                        <h2 class="section-title text-white m-0"
                                            style="font-size: 1.125rem; font-weight: 600; letter-spacing: 0.25px;">
                                            Master
                                        </h2>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2" style="background: #4077ad;
                                        border-radius: 30px 0px 0px 30px;
                                        width: 100%;
                                        box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                        min-width: 250px;">
                                <a class="text-white d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#generalMasterMenu" role="button"
                                    aria-expanded="false" aria-controls="generalMasterMenu">
                                    <span class="hide-menu">General Master</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                                <div class="collapse show" id="generalMasterMenu">
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('Venue-Master.index') }}">
                                            <span class="hide-menu">Venue
                                                Master</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.class.session.index') }}">
                                            <span class="hide-menu">Class
                                                Session</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('stream.index') }}">
                                            <span class="hide-menu">Stream</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('section.index') }}">
                                            <span class="hide-menu">Section</span>
                                        </a></li>
                                </div>
                            </li>

                            <!-- HOSTEL -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="text-white d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#hostelMenu" role="button" aria-expanded="false"
                                    aria-controls="hostelMenu">
                                    <span class="hide-menu">Hostel</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                                <div class="collapse" id="hostelMenu">
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.hostel.building.index') }}">
                                            <span class="hide-menu">Building Master</span>
                                        </a></li>
                                    {{-- <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.hostel.room.index') }}">
                                    <span class="hide-menu">Hostel
                                        Room</span>
                                    </a></li> --}}
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.hostel.floor.index') }}">
                                            <span class="hide-menu">
                                                Floor</span>
                                        </a></li>
                                    {{-- <li class="sidebar-item"><a class="sidebar-link" href="{{ route('hostel.building.map.index') }}">
                                    <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                        class="hide-menu">Hostel
                                        Floor Mapping</span>
                                    </a></li> --}}
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('hostel.building.floor.room.map.index') }}">
                                            <span class="hide-menu">Building
                                                Floor Room Mapping</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('hostel.building.map.assign.student') }}">
                                            <span class="hide-menu">Assign
                                                Hostel</span>
                                        </a></li>
                                </div>
                            </li>

                            <!-- ADDRESS -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="text-white d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#addressMenu" role="button" aria-expanded="false"
                                    aria-controls="addressMenu">
                                    <span class="hide-menu">Address</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                                <div class="collapse" id="addressMenu">
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.country.index') }}">
                                            <span class="hide-menu">Country</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.state.index') }}">
                                            <span class="hide-menu">State</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.district.index') }}">
                                            <span class="hide-menu">District</span>
                                        </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link"
                                            href="{{ route('master.city.index') }}">
                                            <span class="hide-menu">City</span>
                                        </a></li>
                                </div>
                            </li>
                        </ul>
                    @endif
                     
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