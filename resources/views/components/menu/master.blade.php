<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-2" data-simplebar>
    <ul class="sidebar-menu" id="sidebarnav">

        <!-- GENERAL MASTER -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2"
            >
            <a class="text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#generalMasterMenu" role="button" aria-expanded="false" aria-controls="generalMasterMenu">
                <span class="hide-menu">General Master</span>
                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <div class="collapse show" id="generalMasterMenu">
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('Venue-Master.index') }}">
                    <span class="hide-menu">Venue
                        Master</span>
                </a></li>
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.class.session.index') }}">
                    <span class="hide-menu">Class
                        Session</span>
                </a></li>
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('stream.index') }}">
                    <span
                        class="hide-menu">Stream</span>
                </a></li>
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('section.index') }}">
                    <span
                        class="hide-menu">Section</span>
                </a></li>
        </div>

        <!-- HOSTEL -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2"
            >
            <a class="text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#hostelMenu" role="button" aria-expanded="false" aria-controls="hostelMenu">
                <span class="hide-menu">Hostel</span>
                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <div class="collapse" id="hostelMenu">
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.hostel.building.index') }}">
                    <span class="hide-menu">Building Master</span>
                </a></li>
            {{-- <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.hostel.room.index') }}">
                    <span class="hide-menu">Hostel
                        Room</span>
                </a></li> --}}
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.hostel.floor.index') }}">
                    <span class="hide-menu">
                        Floor</span>
                </a></li>
            {{-- <li class="sidebar-item"><a class="sidebar-link" href="{{ route('hostel.building.map.index') }}">
                    <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span class="hide-menu">Hostel
                        Floor Mapping</span>
                </a></li> --}}
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('hostel.building.floor.room.map.index') }}">
                    <span class="hide-menu">Building
                        Floor Room Mapping</span>
                </a></li>
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('hostel.building.map.assign.student') }}">
                    <span class="hide-menu">Assign
                        Hostel</span>
                </a></li>
        </div>

        <!-- ADDRESS -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2"
            >
            <a class="text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#addressMenu" role="button" aria-expanded="false" aria-controls="addressMenu">
                <span class="hide-menu">Address</span>
                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <div class="collapse" id="addressMenu">
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.country.index') }}">
                    <span
                        class="hide-menu">Country</span>
                </a></li>
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.state.index') }}">
                    <span
                        class="hide-menu">State</span>
                </a></li>
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.district.index') }}">
                    <span
                        class="hide-menu">District</span>
                </a></li>
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.city.index') }}">
                    <span
                        class="hide-menu">City</span>
                </a></li>
        </div>

        <!-- TIME TABLE -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2 mb-2"
            >
            <a class="text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#timeTableMenu" role="button" aria-expanded="false" aria-controls="timeTableMenu">
                <span class="hide-menu">Time Table</span>
                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">keyboard_arrow_down</i>
            </a>
        </li>
        <div class="collapse" id="timeTableMenu">
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('calendar.index') }}">
                    <span
                        class="hide-menu">Calendar</span>
                </a></li>
                @if(hasRole('Training'))
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('attendance.index') }}">
                    <span
                        class="hide-menu">Attendance</span>
                </a></li>
                @endif
                @if(hasRole('Student-OT') || hasRole('Guest Faculty') || hasRole('Internal Faculty'))
            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('attendance.user_attendance.index') }}">
                    <span
                        class="hide-menu">Student Attendance</span>
                </a></li>
                @endif
        </div>

    </ul>
</nav>