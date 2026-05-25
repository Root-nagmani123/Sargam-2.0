@php
    $showHomeEstateMini = hasRole('Admin') || hasRole('Super Admin') || hasRole('Estate');
    $homeEstateMiniSelected = $showHomeEstateMini && request('scope') === 'self' && (
        request()->routeIs('admin.estate.request-for-estate')
        || request()->routeIs('admin.estate.generate-estate-bill*')
    );
@endphp
<nav class="sidebar-nav sargam-menu-flyout simplebar-scrollable-y" id="menu-right-mini-1" data-mini-nav-target="mini-1" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content">

                        {{-- Section Header --}}
                        <div class="sidebar-section-header text-uppercase fw-bold mb-1"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            General
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            {{-- Dashboard --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">dashboard_customize</i>
                                    <span class="hide-menu">Dashboard</span>
                                </a>
                            </li>

                            @auth
                            {{-- Edit Profile --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('member.profile.edit') ? 'active' : '' }}"
                                    href="{{ (Auth::check() && Auth::user()->user_id) ? route('member.profile.edit', Auth::user()->user_id) : '#' }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">person_edit</i>
                                    <span class="hide-menu">Edit Profile</span>
                                </a>
                            </li>
                            @endauth

                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            {{-- Batch Profile --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('admin.dashboard-statistics.*') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard-statistics.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">groups</i>
                                    <span class="hide-menu">Batch Profile</span>
                                </a>
                            </li>
                            @endif
                            @if(hasRole('Admin') || hasRole('Super Admin'))
                            <!-- Notice Notification Route (admin manage list only) -->
                            <li class="sidebar-item"><a class="sidebar-link" href="{{ route('admin.notice.index') }}">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;">notifications</i>
                                    <span class="hide-menu small small-sm-normal text-nowrap">Notice
                                        Notifications</span>
                                </a></li>
                            @endif
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.notice.category-master.*') ? 'active' : '' }}" href="{{ route('admin.notice.category-master.index') }}">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;">category</i>       
                            <span class="hide-menu small small-sm-normal text-nowrap">Notice category master</span>
                                </a></li>
                            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.notice.subcategory-master.*') ? 'active' : '' }}" href="{{ route('admin.notice.subcategory-master.index') }}">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;">topic</i>       
                                    <span class="hide-menu small small-sm-normal text-nowrap">Notice subcategory master</span>
                                </a></li>
                            @endif

                            <!-- Faculty Dashboard Route -->
                            @if(hasRole('Doctor'))
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('student.medical.exemption.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Student Medical Exemption
                                        (Doctor)</span>
                                </a></li>
                            @endif



                            <ul class="sidebar-menu" id="sidebarnav">
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#generalCollapse" role="button"
                                    aria-expanded="false" aria-controls="generalCollapse">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">link</i>
                                        <span class="hide-menu">Quick Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                                <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                    @php
                                    $quickLinks = \App\Models\QuickLink::query()
                                    ->active()
                                    ->orderBy('position')
                                    ->get(['id', 'label', 'url', 'target_blank']);
                                    @endphp

                                @if ($quickLinks->isEmpty())
                                @php
                                $quickLinks = collect([
                                (object) ['id' => null, 'label' => 'E-Office', 'url' =>
                                'https://eoffice.lbsnaa.gov.in/', 'target_blank' => true],
                                (object) ['id' => null, 'label' => 'Medical Center', 'url' =>
                                'http://cghs.lbsnaa.gov.in/', 'target_blank' => true],
                                (object) ['id' => null, 'label' => 'Library', 'url' =>
                                'https://idpbridge.myloft.xyz/simplesaml/module.php/core/loginuserpass?AuthState=_13df360546d97777e748e8ded7bf639c5c8c45d3d7%3Ahttps%3A%2F%2Fidpbridge.myloft.xyz%2Fsimplesaml%2Fmodule.php%2Fsaml%2Fidp%2FsingleSignOnService%3Fspentityid%3Dhttps%253A%252F%252Felibrarylbsnaa.myloft.xyz%26cookieTime%3D1688360911',
                                'target_blank' => true],
                                (object) ['id' => null, 'label' => 'Photo Gallery', 'url' =>
                                'https://rcentre.lbsnaa.gov.in/web/', 'target_blank' => true],
                                ]);
                                @endphp
                                @endif

                                @foreach ($quickLinks as $link)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ trim($link->url) }}"
                                        target="{{ $link->target_blank ? '_blank' : '_self' }}">
                                        <span class="hide-menu">{{ $link->label }}</span>
                                    </a>
                                </li>
                                @endforeach
                            </ul>

                            {{-- ── Useful Links (collapsible) ── --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#usefulLinksCollapse" role="button"
                                    aria-expanded="false" aria-controls="usefulLinksCollapse">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">bookmarks</i>
                                        <span class="hide-menu">Useful Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="usefulLinksCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.directory.ot') }}">
                                        <span class="hide-menu">OT Directory</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.directory.lbsnaa') }}">
                                        <span class="hide-menu">LBSNAA Directory</span>
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
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ $url }}"
                                        target="{{ $link->target_blank ? '_blank' : '_self' }}">
                                        <span class="hide-menu">{{ $link->label }}</span>
                                    </a>
                                </li>
                                @endif
                                @endforeach
                            </ul>

                            {{-- ── Security Requests Links (collapsible) ── --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#securityRequestsCollapse" role="button"
                                    aria-expanded="false" aria-controls="securityRequestsCollapse">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">security</i>
                                        <span class="hide-menu">Security Requests Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="securityRequestsCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.employee_idcard.index') }}">
                                        <span class="hide-menu">Request ID Card</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.duplicate_idcard.index') }}">
                                        <span class="hide-menu">Request Duplicate ID Card</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.security.vehicle_pass.index') }}">
                                        <span class="hide-menu">Vehicle Pass Request</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1" href="{{ route('admin.family_idcard.index') }}">
                                        <span class="hide-menu">Request Family ID Card</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- ── Centcom Links (collapsible) ── --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#centcomCollapse" role="button"
                                    aria-expanded="false" aria-controls="centcomCollapse">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">folder_managed</i>
                                        <span class="hide-menu">Centcom Links</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="centcomCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1"
                                        href="{{ route('admin.issue-management.index') }}">
                                        <span class="hide-menu">All Issues</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1"
                                        href="{{ route('admin.issue-management.centcom') }}">
                                        <span class="hide-menu">CENTCOM - Assigned Complaints</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1"
                                        href="{{ route('admin.issue-management.create') }}">
                                        <span class="hide-menu">Log New Issue</span>
                                    </a>
                                </li>
                            </ul>

                            @if ($showHomeEstateMini)
                            {{-- ── Estate Management (collapsible) ── --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#estateManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateManagementCollapse">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">home_work</i>
                                        <span class="hide-menu">Estate Management</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="estateManagementCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('admin.estate.request-for-estate') && request('scope') === 'self' ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-estate', ['scope' => 'self']) }}">
                                        <span class="hide-menu">Request For Estate</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('admin.estate.generate-estate-bill*') && request('scope') === 'self' ? 'active' : '' }}"
                                        href="{{ route('admin.estate.generate-estate-bill', ['scope' => 'self']) }}">
                                        <span class="hide-menu">My Estate Bill</span>
                                    </a>
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
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
        <div class="simplebar-scrollbar"
            style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);">
        </div>
    </div>
</nav>
