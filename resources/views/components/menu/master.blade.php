<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-2" data-simplebar="">
    <ul class="sidebar-menu" id="sidebarnav">
        <!-- ---------------------------------- -->
        <!-- Home -->
        <!-- ---------------------------------- -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
            <span class="hide-menu">General Master</span>
        </li>
        <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('member.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:shield-user-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Member</span>
                                </a>
                            </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('Venue-Master.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">Venue Master</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.class.session.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">Class Session</span>
            </a>
        </li>
        
        
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('stream.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:widget-4-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Stream</span>
            </a>
        </li>

        <!-- <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('curriculum.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:iphone-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Course Curriculum</span>
            </a>
        </li> -->
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('section.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:calendar-mark-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Section</span>
            </a>
        </li>
        <!-- ======= REGISTRATION SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Hostel</span></li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.hostel.building.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Hostel Building</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.hostel.room.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Hostel Room</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.hostel.floor.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Hostel Floor</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('hostel.building.map.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Hostel Floor Mapping</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('hostel.building.floor.room.map.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Building Floor Room Mapping</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('hostel.building.map.assign.student') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Assign Hostel</span>
            </a>
        </li>
        <!-- Divider -->
        <span class="sidebar-divider"></span>

        <!-- ======= REGISTRATION SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Address</span></li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.country.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Country</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.state.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">State</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.district.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">District</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.city.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">City</span>
            </a>
        </li>
                             <!-- Divider -->
                            <span class="sidebar-divider"></span>
                            <!-- ======= User Management SECTION ======= -->
                            <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
                                style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
                                <span class="hide-menu">Time Table</span></li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('calendar.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:calendar-mark-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Calendar</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('attendance.index') }}" id="get-url"
                                    aria-expanded="false">
                                    <iconify-icon icon="solar:calendar-mark-line-duotone">
                                    </iconify-icon>
                                    <span class="hide-menu">Attendance</span>
                                </a>
                            </li>
    </ul>
</nav>