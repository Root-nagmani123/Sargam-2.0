<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-2" data-simplebar="">
    <ul class="sidebar-menu" id="sidebarnav">
        <!-- ---------------------------------- -->
        <!-- Home -->
        <!-- ---------------------------------- -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;">
            <span class="hide-menu">General</span>
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
            <a class="sidebar-link" href="{{ route('subject-module.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:widget-4-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Subject Module</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('group.mapping.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:calendar-mark-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Group Mapping</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.course.group.type.index') }}" id="get-url"
                aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">Course Group Type</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('programme.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:mask-happly-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Course Master</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.mdo_duty_type.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">MDO Duty Type</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.department.master.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Department Master</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.designation.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Designation Master</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.caste.category.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Caste Category</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('expertise.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:airbuds-case-minimalistic-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Area of Expertise</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('stream.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:widget-4-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Stream</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('subject.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:speaker-minimalistic-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Subject</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('curriculum.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:iphone-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Course Curriculum</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('section.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:calendar-mark-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Section</span>
            </a>
        </li>
        <!-- Divider -->
        <span class="sidebar-divider"></span>
        <!-- ======= Exemption SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Exemption</span></li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.exemption.category.master.index') }}" id="get-url"
                aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">Exemption Category</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.exemption.medical.speciality.index') }}" id="get-url"
                aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">Exemption Medical Speciality</span>
            </a>
        </li>
        <!-- Divider -->
        <span class="sidebar-divider"></span>
        <!-- ======= REGISTRATION SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Memo</span></li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.memo.type.master.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Memo Type Master</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.memo.conclusion.master.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Memo Conclusion Master</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('memo.notice.management.index') }}" id="get-url"
                aria-expanded="false">
                <iconify-icon icon="solar:feed-bold-duotone">
                </iconify-icon>
                <span class="hide-menu">Memo Notice Management</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('memo.notice.management.user') }}" id="get-url"
                aria-expanded="false">
                <iconify-icon icon="solar:feed-bold-duotone">
                </iconify-icon>
                <span class="hide-menu">Memo Notice Chat (User)</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('course.memo.decision.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Memo Course Mapping</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('admin.courseAttendanceNoticeMap.memo_notice') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Memo / Notice Creation (Admin)</span>
            </a>

            <!-- Divider -->
            <span class="sidebar-divider"></span>
            <!-- ======= REGISTRATION SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Employee</span></li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.employee.type.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Employee Type</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.employee.group.index') }}">
                <iconify-icon icon="solar:airbuds-case-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Employee Group</span>
            </a>
        </li>


        <!-- Divider -->
        <span class="sidebar-divider"></span>
        <!-- ======= REGISTRATION SECTION ======= -->
        <li class="nav-small-cap fs-2 fw-bold py-2 text-white me-2"
            style="background-color: #af2910 !important;border-radius: 10px; line-height:10px;"><span
                class="hide-menu">Faculty</span></li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.faculty.expertise.index') }}" id="get-url"
                aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">Faculty Expertise</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('master.faculty.type.master.index') }}" id="get-url"
                aria-expanded="false">
                <iconify-icon icon="solar:face-scan-square-broken"></iconify-icon>
                <span class="hide-menu">Faculty Type</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('faculty.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:document-text-line-duotone">
                </iconify-icon>
                <span class="hide-menu">Faculty</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('mapping.index') }}" id="get-url" aria-expanded="false">
                <iconify-icon icon="solar:map-arrow-up-bold-duotone">
                </iconify-icon>
                <span class="hide-menu">Faculty Topic Mapping</span>
            </a>
        </li>
        <!-- Divider -->
        <span class="sidebar-divider"></span>

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
    </ul>
</nav>