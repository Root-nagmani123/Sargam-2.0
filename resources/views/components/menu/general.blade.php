@php
    $showHomeEstateMini = hasRole('Admin') || hasRole('Super Admin') || hasRole('Estate');
    $securityRequestsOpen = request()->routeIs('admin.employee_idcard.*')
        || request()->routeIs('admin.duplicate_idcard.*')
        || request()->routeIs('admin.security.vehicle_pass.*')
        || request()->routeIs('admin.family_idcard.*');
    $centcomOpen = request()->routeIs('admin.issue-management.*');
    $estateManagementOpen = $showHomeEstateMini && (
        (request()->routeIs('admin.estate.request-for-estate') && request('scope') === 'self')
        || (request()->routeIs('admin.estate.generate-estate-bill*') && request('scope') === 'self')
    );
    $usefulLinksOpen = request()->routeIs('admin.directory.ot')
        || request()->routeIs('admin.directory.lbsnaa');
@endphp
<nav class="sidebar-nav sidebar-panel-menu sidebar-general-menu d-block simplebar-scrollable-y" id="menu-right-mini-1" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content sidebar-panel-menu__content">
                        <p class="sidebar-panel-menu__title text-uppercase text-secondary small fw-semibold mb-3 px-1">
                            GENERAL
                        </p>

                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">dashboard</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Dashboard</span>
                                </a>
                            </li>

                            @auth
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('member.profile.edit') ? 'active' : '' }}"
                                    href="{{ (Auth::check() && Auth::user()->user_id) ? route('member.profile.edit', Auth::user()->user_id) : '#' }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">person</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Edit Profile</span>
                                </a>
                            </li>
                            @endauth

                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.dashboard-statistics.*') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard-statistics.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">groups</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Batch Profile</span>
                                </a>
                            </li>
                            @endif

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.notice.index') ? 'active' : '' }}"
                                    href="{{ route('admin.notice.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">notifications</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Notice Notifications</span>
                                </a>
                            </li>

                            @if(hasRole('Doctor'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('student.medical.exemption.index') ? 'active' : '' }}"
                                    href="{{ route('student.medical.exemption.index') }}">
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">medical_services</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Student Medical Exemption (Doctor)</span>
                                </a>
                            </li>
                            @endif

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#generalCollapse" role="button"
                                    aria-expanded="false" aria-controls="generalCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">link</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Quick Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="generalCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                @php
                                    $quickLinks = \App\Models\QuickLink::query()
                                        ->active()
                                        ->orderBy('position')
                                        ->get(['id', 'label', 'url', 'target_blank']);
                                @endphp
                                @if ($quickLinks->isEmpty())
                                    @php
                                        $quickLinks = collect([
                                            (object) ['id' => null, 'label' => 'E-Office', 'url' => 'https://eoffice.lbsnaa.gov.in/', 'target_blank' => true],
                                            (object) ['id' => null, 'label' => 'Medical Center', 'url' => 'http://cghs.lbsnaa.gov.in/', 'target_blank' => true],
                                            (object) ['id' => null, 'label' => 'Library', 'url' => 'https://idpbridge.myloft.xyz/simplesaml/module.php/core/loginuserpass?AuthState=_13df360546d97777e748e8ded7bf639c5c8c45d3d7%3Ahttps%3A%2F%2Fidpbridge.myloft.xyz%2Fsimplesaml%2Fmodule.php%2Fsaml%2Fidp%2FsingleSignOnService%3Fspentityid%3Dhttps%253A%252F%252Felibrarylbsnaa.myloft.xyz%26cookieTime%3D1688360911', 'target_blank' => true],
                                            (object) ['id' => null, 'label' => 'Photo Gallery', 'url' => 'https://rcentre.lbsnaa.gov.in/web/', 'target_blank' => true],
                                        ]);
                                    @endphp
                                @endif
                                @foreach ($quickLinks as $link)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2"
                                            href="{{ trim($link->url) }}"
                                            target="{{ $link->target_blank ? '_blank' : '_self' }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">{{ $link->label }}</span>
                                        </a>
                                    </li>
                                @endforeach
                                </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#usefulLinksCollapse" role="button"
                                    aria-expanded="false" aria-controls="usefulLinksCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">bookmark</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Useful Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="usefulLinksCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.directory.ot') ? 'active' : '' }}"
                                        href="{{ route('admin.directory.ot') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">OT Directory</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.directory.lbsnaa') ? 'active' : '' }}"
                                        href="{{ route('admin.directory.lbsnaa') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">LBSNAA Directory</span>
                                    </a>
                                </li>
                                @php
                                    $usefulLinks = \App\Models\UsefulLink::query()
                                        ->active()
                                        ->orderBy('position')
                                        ->get(['id', 'label', 'url', 'file_path', 'target_blank']);
                                @endphp
                                @foreach ($usefulLinks as $link)
                                    @php
                                        $url = $link->url ? trim($link->url) : null;
                                        if (!$url && !empty($link->file_path)) {
                                            $url = asset('storage/' . $link->file_path);
                                        }
                                    @endphp
                                    @if ($url)
                                        <li class="sidebar-item mb-1">
                                            <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2"
                                                href="{{ $url }}"
                                                target="{{ $link->target_blank ? '_blank' : '_self' }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">{{ $link->label }}</span>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                                </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#securityRequestsCollapse" role="button"
                                    aria-expanded="false" aria-controls="securityRequestsCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">shield</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Security Requests Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="securityRequestsCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.employee_idcard.index') ? 'active' : '' }}"
                                        href="{{ route('admin.employee_idcard.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request ID Card</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.duplicate_idcard.index') ? 'active' : '' }}"
                                        href="{{ route('admin.duplicate_idcard.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request Duplicate ID Card</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.security.vehicle_pass.index') ? 'active' : '' }}"
                                        href="{{ route('admin.security.vehicle_pass.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Vehicle Pass Request</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.family_idcard.index') ? 'active' : '' }}"
                                        href="{{ route('admin.family_idcard.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request Family ID Card</span>
                                    </a>
                                </li>
                                </ul>
                                </li>
                            </ul>

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#centcomCollapse" role="button"
                                    aria-expanded="false" aria-controls="centcomCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">hub</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Centcom Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="centcomCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.issue-management.index') ? 'active' : '' }}"
                                        href="{{ route('admin.issue-management.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">All Issues</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.issue-management.centcom') ? 'active' : '' }}"
                                        href="{{ route('admin.issue-management.centcom') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">CENTCOM - Assigned Complaints</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.issue-management.create') ? 'active' : '' }}"
                                        href="{{ route('admin.issue-management.create') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Log New Issue</span>
                                    </a>
                                </li>
                                </ul>
                                </li>
                            </ul>

                            @if ($showHomeEstateMini)
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#estateManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateManagementCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">home_work</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Management</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="estateManagementCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.estate.request-for-estate') && request('scope') === 'self' ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-estate', ['scope' => 'self']) }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request For Estate</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.estate.generate-estate-bill*') && request('scope') === 'self' ? 'active' : '' }}"
                                        href="{{ route('admin.estate.generate-estate-bill', ['scope' => 'self']) }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">My Estate Bill</span>
                                    </a>
                                </li>
                                </ul>
                                </li>
                            </ul>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>

