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
                          
                            <li class="nav-section" role="listitem">
                                @if(hasRole('Admin') || hasRole('Training-Induction'))

                                <!-- Main Container with Improved Layout -->
                                <div class="d-flex align-items-center justify-content-between w-100 mt-4">

                                    <!-- Left Side: Collapse Button with Enhanced Accessibility -->
                                    <div class="d-flex align-items-center mb-3">
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
                                    <span class="hide-menu small small-sm-normal text-nowrap">General Master</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse" id="generalMasterMenu">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('Venue-Master.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Venue
                                            Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.class.session.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Class
                                            Session</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('stream.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Stream</span>
                                    </a></li>
                            </ul>

                            <!-- HOSTEL -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="text-white d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#hostelMenu" role="button" aria-expanded="false"
                                    aria-controls="hostelMenu">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Hostel</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <div class="collapse" id="hostelMenu">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.hostel.building.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Building Master</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.hostel.room.index') }}">
                                <span class="hide-menu small small-sm-normal text-nowrap">Hostel
                                    Room</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.hostel.floor.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">
                                            Floor</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('hostel.building.map.index') }}">
                                <span
                                    class="hide-menu small small-sm-normal text-nowrap">Hostel
                                    Floor Mapping</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('hostel.building.floor.room.map.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Building
                                            Floor Room Mapping</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('hostel.building.map.assign.student') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Assign
                                            Hostel</span>
                                    </a></li>
                            </div>

                            <!-- ADDRESS -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="text-white d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#addressMenu" role="button" aria-expanded="false"
                                    aria-controls="addressMenu">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Address</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <div class="collapse" id="addressMenu">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.country.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Country</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.state.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">State</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.district.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">District</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.city.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">City</span>
                                    </a></li>
                            </div>
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