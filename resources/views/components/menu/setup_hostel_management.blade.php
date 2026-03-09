<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-11" data-simplebar="">
    <ul class="sidebar-menu" id="sidebarnav">
        <!-- ======= Hostel Management ======= -->
        <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.hostel.dashboard') ? 'active' : '' }}"
                                        href="{{ route('admin.hostel.dashboard') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Hostel Dashboard</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.hostel.hostel-wise-list') ? 'active' : '' }}"
                                        href="{{ route('admin.hostel.hostel-wise-list') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Hostel-wise List</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.hostel.room-allotment') ? 'active' : '' }}"
                                        href="{{ route('admin.hostel.room-allotment') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Room Allotment</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.hostel.room-issues') ? 'active' : '' }}"
                                        href="{{ route('admin.hostel.room-issues') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Room Issues</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.hostel.rooms') ? 'active' : '' }}"
                                        href="{{ route('admin.hostel.rooms') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Room List</span>
                                    </a></li>
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.hostel.building.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Building Master</span>
                                    </a></li>
                                {{-- <li class="sidebar-item"><a class="sidebar-link" href="{{ route('master.hostel.room.index') }}">
                                <span class="hide-menu">Hostel
                                    Room</span>
                                </a></li> --}}
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('master.hostel.floor.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">
                                            Floor</span>
                                    </a></li>
                                {{-- <li class="sidebar-item"><a class="sidebar-link" href="{{ route('hostel.building.map.index') }}">
                                <iconify-icon icon="solar:airbuds-case-line-duotone"></iconify-icon><span
                                    class="hide-menu">Hostel
                                    Floor Mapping</span>
                                </a></li> --}}
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
    </ul>
</nav>