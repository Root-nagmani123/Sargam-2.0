@php
    // Determine active tab based on current route/path
    $activeNavTab = '#home';
    $path = request()->path();
    $routeName = request()->route()?->getName() ?? '';
    if (request()->routeIs('admin.dashboard') || request()->routeIs('admin.dashboard.*') || request()->routeIs('calendar.index')) {
        $activeNavTab = '#home';
    } elseif (
        request()->routeIs('admin.employee_idcard.*') || request()->routeIs('admin.issue-management*') ||
        request()->routeIs('member.*') || request()->routeIs('faculty.*') || request()->routeIs('programme.*') ||
        request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*') ||
        str_starts_with($path, 'setup/') || str_starts_with($path, 'admin/setup') ||
        str_starts_with($path, 'admin/employee-idcard') || str_starts_with($path, 'admin/issue-management') ||
        str_starts_with($path, 'courseAttendanceNoticeMap') || str_starts_with($path, 'course_memo') ||
        str_starts_with($path, 'building_floor') || str_starts_with($path, 'group_mapping') ||
        str_starts_with($path, 'course-repository') || str_starts_with($path, 'feedback') ||
        str_starts_with($path, 'admin/notice') || str_starts_with($path, 'attendance') ||
        str_starts_with($path, 'security') || str_starts_with($path, 'ot_notice') ||
        str_starts_with($path, 'forms') || str_starts_with($path, 'registration') ||
        str_starts_with($path, 'mdo_escrot') || str_starts_with($path, 'student_medical') ||
        str_starts_with($path, 'medical_exception') || str_starts_with($path, 'memo_discipline') ||
        str_starts_with($path, 'country') || str_starts_with($path, 'state') || str_starts_with($path, 'city') ||
        str_starts_with($path, 'stream') || str_starts_with($path, 'subject') || str_starts_with($path, 'Venue-Master') ||
        str_starts_with($path, 'batch') || str_starts_with($path, 'curriculum') || str_starts_with($path, 'mapping') ||
        str_starts_with($path, 'admin/master') || str_contains($path, 'breadcrumb-showcase') || str_starts_with($path, 'password') ||
        str_starts_with($path, 'expertise') || str_starts_with($path, 'faculty_notice') || str_starts_with($path, 'faculty_mdo')
    ) {
        $activeNavTab = '#tab-setup';
    } elseif (str_starts_with($path, 'communications') || request()->routeIs('*communications*')) {
        $activeNavTab = '#tab-communications';
    } elseif (str_starts_with($path, 'academics') || request()->routeIs('*academics*')) {
        $activeNavTab = '#tab-academics';
    } elseif (str_starts_with($path, 'material') || request()->routeIs('*material*')) {
        $activeNavTab = '#tab-material-management';
    }
@endphp
<header class="topbar">
    <!-- Skip to Content (GIGW Mandatory) -->
<a href="#main-content" class="visually-hidden-focusable skip-link">
    Skip to main content
</a>

    <header class="header-top-bar d-none d-lg-block">
    <div class="d-flex align-items-center justify-content-between flex-wrap py-1">

    <!-- Left: Government Identity -->
    <div class="d-flex align-items-center gap-2">
        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/330px-Flag_of_India.svg.png"
            alt="Emblem of India" class="header-flag-icon">
        <span class="fw-semibold small text-white">
            भारत सरकार | Government of India
        </span>
    </div>

    <!-- Right: Utilities with vertical separators -->
    <nav aria-label="Utility Navigation">
        <ul class="list-inline mb-0 d-flex align-items-center gap-0 small header-utility-nav">
            <li class="list-inline-item">
                <a href="#main-content" class="text-white text-decoration-none px-2">Skip to content</a>
            </li>
            <li class="header-utility-sep" aria-hidden="true"></li>
            <li class="list-inline-item d-flex align-items-center gap-1" aria-label="Font size controls">
                <a href="javascript:void(0)" class="text-white px-2 header-font-btn" aria-label="Decrease font size">A-</a>
                <a href="javascript:void(0)" class="text-white px-2 header-font-btn" aria-label="Normal font size">A</a>
                <a href="javascript:void(0)" class="text-white px-2 header-font-btn" aria-label="Increase font size">A+</a>
            </li>
            <li class="header-utility-sep" aria-hidden="true"></li>
            <li class="list-inline-item">
                <div class="header-lang-dropdown">
                    <i class="material-icons material-symbols-rounded header-globe-icon">language</i>
                    <select class="form-select form-select-sm header-lang-select" aria-label="Select Language">
                        <option selected>English</option>
                        <option>हिन्दी</option>
                    </select>
                </div>
            </li>
        </ul>
    </nav>
    </div>
    </header>

    <div class="with-vertical">
        <nav class="navbar navbar-expand-lg p-0">
            <ul class="navbar-nav">
                <li class="nav-item d-flex d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle sidebartoggler" id="headerCollapse"
                        href="javascript:void(0)">
                        <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                </li>
            </ul>

            <div class="header-brand d-flex align-items-center gap-2 py-2">
                <img src="{{ asset('images/ashoka.webp') }}" alt="ashoka emblem" class="header-logo-emblem">
                <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="logo" class="header-logo">
            </div>

            <a class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <iconify-icon icon="solar:menu-dots-bold-duotone" class="fs-6"></iconify-icon>
            </a>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">
                    <!-- Enhanced Navigation Container (Desktop) -->
                    <div class="nav-container position-relative d-none d-lg-block">
                        <ul class="navbar-nav header-main-nav px-4 py-2 gap-1 align-items-center" role="menubar" aria-label="Main navigation">

                            <!-- Home -->
                            <li class="nav-item" role="none">
                                <a href="#home"
                                    class="nav-link header-nav-link px-3 py-2 {{ $activeNavTab === '#home' ? 'active' : '' }}"
                                    data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#home' ? 'true' : 'false' }}" aria-controls="home-panel"
                                    id="home-tab">
                                    <span>Home</span>
                                </a>
                            </li>

                            <!-- Setup -->
                            <li class="nav-item" role="none">
                                <a href="#tab-setup"
                                    class="nav-link header-nav-link px-3 py-2 {{ $activeNavTab === '#tab-setup' ? 'active' : '' }}"
                                    data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-setup' ? 'true' : 'false' }}" aria-controls="setup-panel"
                                    id="setup-tab">

                                    @if(hasRole('Admin') || hasRole('Training-Induction') ||  hasRole('Staff') || hasRole('IST'))
                                    <span>Setup</span>
                                    @elseif(hasRole('Internal Faculty') || hasRole('Guest Faculty') ||
                                    hasRole('Student-OT'))
                                    <span>Academics</span>
                                    @endif
                                </a>
                            </li>

                            <!-- Communications -->
                            <li class="nav-item" role="none">
                                <a href="#tab-communications"
                                    class="nav-link header-nav-link px-3 py-2 {{ $activeNavTab === '#tab-communications' ? 'active' : '' }}"
                                    data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-communications' ? 'true' : 'false' }}"
                                    aria-controls="communications-panel" id="communications-tab">
                                    <span>Communications</span>
                                </a>
                            </li>

                            <!-- Academics -->
                            <li class="nav-item" role="none">
                                <a href="#tab-academics"
                                    class="nav-link header-nav-link px-3 py-2 {{ $activeNavTab === '#tab-academics' ? 'active' : '' }}"
                                    data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-academics' ? 'true' : 'false' }}"
                                    aria-controls="academics-panel" id="academics-tab">
                                    <span>Academics</span>
                                </a>
                            </li>

                            <!-- Material Management -->
                            <li class="nav-item" role="none">
                                <a href="#tab-material-management"
                                    class="nav-link header-nav-link px-3 py-2 {{ $activeNavTab === '#tab-material-management' ? 'active' : '' }}"
                                    data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-material-management' ? 'true' : 'false' }}"
                                    aria-controls="material-management-panel" id="material-management-tab">
                                    <span>Material Management</span>
                                </a>
                            </li>

                            <!-- Financial Dropdown -->
                            <li class="nav-item dropdown" role="none">
                                <a class="nav-link header-nav-link px-3 py-2 d-flex align-items-center gap-1 dropdown-toggle-custom"
                                    href="#" id="financialDropdown" role="menuitem" aria-haspopup="true"
                                    aria-expanded="false" data-bs-toggle="dropdown">
                                    <span>Financial</span>
                                    <i class="material-icons material-symbols-rounded fs-6 dropdown-arrow transition-all"
                                        aria-hidden="true">expand_more</i>
                                </a>

                                <ul class="dropdown-menu shadow-lg border-0 rounded-xl p-2 mt-1"
                                    style="min-width: 180px; border: 1px solid rgba(0, 0, 0, 0.08);"
                                    aria-labelledby="financialDropdown" role="menu">
                                    <li role="none">
                                        <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg hover-lift"
                                            href="#" role="menuitem">
                                            <span>Budget</span>
                                        </a>
                                    </li>
                                    <li role="none">
                                        <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg hover-lift"
                                            href="#" role="menuitem">
                                            <span>Accounts</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Search -->
                            <li class="nav-item" role="none">
                                <button type="button" class="nav-link header-search-btn search-trigger"
                                    aria-label="Open search" aria-expanded="false" aria-controls="searchModal">
                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">search</i>
                                </button>
                            </li>
                        </ul>
                    </div>

                </div>

                <!-- Right Side: Logout + Last Login -->
                <div class="d-flex align-items-center ms-auto gap-3 header-right-actions">

    <!-- Notifications (visible on both desktop and mobile) -->
    <div class="dropdown position-relative d-none d-lg-block">
        <button type="button"
            class="btn btn-light rounded-1 p-2 position-relative shadow-sm notification-btn "
            id="notificationDropdown"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Notifications">

            <i class="material-icons material-symbols-rounded fs-5">
                notifications
            </i>

            @php
                $unreadCount = notification()->getUnreadCount(Auth::user()->user_id ?? 0);
            @endphp

            @if($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <!-- Dropdown -->
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-0 notification-dropdown"
            aria-labelledby="notificationDropdown">

            <!-- Header -->
            <li class="dropdown-header sticky-top bg-white d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <span class="fw-semibold">Notifications</span>
                @if($unreadCount > 0)
                    <button type="button"
                        class="btn btn-sm btn-link text-primary p-0"
                        onclick="markAllAsRead()">
                        Mark all as read
                    </button>
                @endif
            </li>

            <div id="notificationList" class="notification-list">
                @php
                    $notifications = notification()->getNotifications(Auth::user()->user_id ?? 0, 10, false);
                @endphp

                @if($notifications->count() > 0)
                    @foreach($notifications as $notification)
                        <li>
                            <a class="dropdown-item px-3 py-3 d-flex gap-2 notification-item
                                {{ $notification->is_read ? '' : 'unread' }}"
                               href="javascript:void(0)"
                               onclick="markAsRead({{ $notification->pk }})">

                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">
                                        {{ $notification->title ?? 'Notification' }}
                                    </div>
                                    <div class="text-muted small mt-1">
                                        {{ Str::limit($notification->message ?? '', 60) }}
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 11px;">
                                        {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                @else
                    <li class="text-center px-4 py-5 text-muted">
                        <i class="material-icons material-symbols-rounded fs-1 opacity-25">
                            notifications_none
                        </i>
                        <div class="mt-2 small">No notifications</div>
                    </li>
                @endif
            </div>
        </ul>
    </div>

    <!-- Logout -->
    <form action="{{ route('logout') }}" method="POST" class="m-0">
        @csrf
        <button type="submit"
            class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 px-3 rounded-1 shadow-sm"
            aria-label="Sign out">
            <i class="material-icons material-symbols-rounded fs-6">logout</i>
            <span class="small fw-medium">Logout</span>
        </button>
    </form>

    <!-- Last Login -->
    <div class="d-flex align-items-center gap-1 small">
        <i class="material-icons material-symbols-rounded fs-6">
            history
        </i>
        <span class="fw-semibold">Last login:</span>

        @php
            $lastLogin = Auth::user()->last_login ?? null;
            if ($lastLogin) {
                $date = \Carbon\Carbon::parse($lastLogin);
                $formattedDate = $date->format('Y-m-d H:i:s');
                $isoDate = $date->toIso8601String();
            } else {
                $formattedDate = 'Never';
                $isoDate = '';
            }
        @endphp

        <time datetime="{{ $isoDate }}" title="{{ $formattedDate }}" class="fw-medium">
            {{ $formattedDate }}
        </time>
    </div>
</div>

            </div>

            <!-- Mobile Navigation Container (FB/Instagram-style) -->
            <div class="nav-container d-lg-none">
                <ul class="navbar-nav mobile-tabbar" role="menubar" aria-label="Main navigation mobile">
                    <!-- Home -->
                    <li class="nav-item" role="none">
                        <a href="#home" class="nav-link mobile-tab-link {{ $activeNavTab === '#home' ? 'active' : '' }}"
                            data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#home' ? 'true' : 'false' }}" aria-controls="home-panel"
                            id="home-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">home</i>
                            <span>Home</span>
                        </a>
                    </li>

                    <!-- Setup -->
                    <li class="nav-item" role="none">
                        <a href="#tab-setup" class="nav-link mobile-tab-link {{ $activeNavTab === '#tab-setup' ? 'active' : '' }}"
                            data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-setup' ? 'true' : 'false' }}" aria-controls="setup-panel"
                            id="setup-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">settings</i>
                            @if(hasRole('Admin') || hasRole('Training-Induction') ||  hasRole('Staff'))
                            <span>Setup</span>
                            @elseif(hasRole('Internal Faculty') || hasRole('Guest Faculty') ||
                            hasRole('Student-OT'))
                            <span>Academics</span>
                            @endif
                        </a>
                    </li>

                    <!-- Communications -->
                    <li class="nav-item" role="none">
                        <a href="#tab-communications" class="nav-link mobile-tab-link {{ $activeNavTab === '#tab-communications' ? 'active' : '' }}"
                            data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-communications' ? 'true' : 'false' }}"
                            aria-controls="communications-panel" id="communications-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">forum</i>
                            <span>Comms</span>
                        </a>
                    </li>

                    <!-- Material Management -->
                    <li class="nav-item" role="none">
                        <a href="#tab-material-management" class="nav-link mobile-tab-link {{ $activeNavTab === '#tab-material-management' ? 'active' : '' }}"
                            data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-material-management' ? 'true' : 'false' }}"
                            aria-controls="material-management-panel" id="material-management-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">inventory_2</i>
                            <span>Material</span>
                        </a>
                    </li>

                    <!-- Financial Dropdown -->
                    <li class="nav-item dropup" role="none">
                        <a class="nav-link mobile-tab-link dropdown-toggle-custom" href="#"
                            id="financialDropdownMobile" role="menuitem" aria-haspopup="true"
                            aria-expanded="false" data-bs-toggle="dropdown">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">account_balance_wallet</i>
                            <span>Finance</span>
                        </a>

                        <ul class="dropdown-menu shadow-lg border-0 rounded-xl p-2 mt-1"
                            style="min-width: 180px; border: 1px solid rgba(0, 0, 0, 0.08);"
                            aria-labelledby="financialDropdownMobile" role="menu">
                            <li role="none">
                                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg hover-lift"
                                    href="#" role="menuitem">
                                    <span>Budget</span>
                                </a>
                            </li>
                            <li role="none">
                                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg hover-lift"
                                    href="#" role="menuitem">
                                    <span>Accounts</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Notifications (Offcanvas on mobile for reliable display) -->
                    <li class="nav-item" role="none">
                        <button type="button"
                            class="nav-link mobile-tab-link border-0 bg-transparent p-0 position-relative"
                            id="notificationBtnMobile" data-bs-toggle="offcanvas" data-bs-target="#notificationOffcanvasMobile"
                            aria-controls="notificationOffcanvasMobile" aria-label="Notifications" title="Notifications">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">notifications_active</i>
                            @php
                            $unreadCountMobile = notification()->getUnreadCount(Auth::user()->user_id ?? 0);
                            @endphp
                            @if($unreadCountMobile > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 9px;">
                                {{ $unreadCountMobile > 99 ? '99+' : $unreadCountMobile }}
                            </span>
                            @endif
                            <span>Notifications</span>
                        </button>
                    </li>

                    <!-- Search -->
                    <li class="nav-item" role="none">
                        <button type="button" class="nav-link mobile-tab-link search-trigger"
                            aria-label="Open search" aria-expanded="false" aria-controls="searchModal">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">search</i>
                            <span>Search</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Mobile Notifications Offcanvas (slides up from bottom) -->
            <div class="offcanvas offcanvas-bottom d-lg-none" tabindex="-1" id="notificationOffcanvasMobile"
                aria-labelledby="notificationOffcanvasMobileLabel" style="max-height: 70vh; border-radius: 16px 16px 0 0;">
                <div class="offcanvas-header border-bottom py-3">
                    <h5 class="offcanvas-title fw-semibold" id="notificationOffcanvasMobileLabel">Notifications</h5>
                    @if($unreadCountMobile > 0)
                    <button type="button" class="btn btn-sm btn-link text-primary p-0" onclick="markAllAsRead()">
                        Mark all as read
                    </button>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0 overflow-y-auto" style="max-height: calc(70vh - 60px);">
                    <div id="notificationListMobile">
                        @php
                        $notificationsMobile = notification()->getNotifications(Auth::user()->user_id ?? 0, 10, false);
                        @endphp
                        @if($notificationsMobile->count() > 0)
                        @foreach($notificationsMobile as $notification)
                        <a class="d-block px-3 py-3 border-bottom text-decoration-none text-dark {{ $notification->is_read ? '' : 'bg-light' }}"
                            href="javascript:void(0)" onclick="markAsRead({{ $notification->pk }})">
                            <div class="fw-semibold small">{{ $notification->title ?? 'Notification' }}</div>
                            <div class="text-muted small mt-1">{{ Str::limit($notification->message ?? '', 80) }}</div>
                            <div class="text-muted" style="font-size: 10px; margin-top: 4px;">
                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                            </div>
                        </a>
                        @endforeach
                        @else
                        <div class="px-3 py-5 text-center text-muted">
                            <i class="material-icons material-symbols-rounded" style="font-size: 48px; opacity: 0.3;">notifications_none</i>
                            <div class="mt-2">No notifications</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <style>
                .notification-btn:hover {
    background-color: var(--bs-light);
    transform: translateY(-1px);
}

.notification-badge {
    font-size: 10px;
    padding: 4px 6px;
}

.notification-dropdown {
    width: 360px;
    max-height: 420px;
    overflow: hidden;
}

.notification-list {
    max-height: 360px;
    overflow-y: auto;
}

.notification-item {
    border-left: 3px solid transparent;
    transition: background-color 0.2s ease;
}

.notification-item.unread {
    background-color: var(--bs-light);
    border-left-color: var(--bs-primary);
}

.notification-item:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

                /* Skip link visibility */
.skip-link {
    position: absolute;
    top: -40px;
    left: 10px;
    background: #0d6efd;
    color: #fff;
    padding: 6px 12px;
    z-index: 1000;
    border-radius: 4px;
}
.skip-link:focus { top: 10px; }
:focus-visible { outline: 3px solid #ffbf47; outline-offset: 2px; }

/* Header - Match reference design */
.header-top-bar {
    background: #122442;
    height: 40px;
    border: none;
}
.header-flag-icon { height: 20px; }
.header-utility-nav .header-utility-sep {
    width: 1px;
    height: 16px;
    background: rgba(255,255,255,0.5);
    margin: 0 8px;
    display: inline-block;
}
.header-font-btn { text-decoration: none !important; }
.header-lang-dropdown {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.15);
    border-radius: 6px;
    padding: 4px 10px;
}
.header-globe-icon { font-size: 18px !important; color: #fff !important; }
.header-lang-select {
    background: transparent !important;
    border: none !important;
    color: #fff !important;
    font-size: 0.875rem;
    padding: 2px 4px;
    min-width: 80px;
}
.header-lang-select option { background: #122442; color: #fff; }

/* Main nav bar - white background */
.with-vertical .navbar { background: #fff !important }
.header-brand { gap: 10px !important; }
.header-logo-emblem { height: 40px; object-fit: contain; }
.header-logo { height: 32px; object-fit: contain; }
/* Desktop: larger, more prominent logo */
@media (min-width: 992px) {
    .header-brand { gap: 12px !important; }
    .header-logo-emblem { height: 52px !important; }
    .header-logo { height: 44px !important; }
}
.header-app-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #004a93;
}

/* Nav container - light grey pill */
.header-main-nav {
    background: #f0f0f0 !important;
    border-radius: 12px;
    height: 48px;
    border: 1px solid rgba(0,0,0,0.05);
}
.header-nav-link {
    color: #6c757d !important;
    border-radius: 8px;
    text-decoration: none !important;
    border-bottom: 2px solid transparent;
    transition: color 0.2s, border-color 0.2s;
}
.header-nav-link:hover { color: #495057 !important; }
.header-nav-link.active {
    color: #004a93 !important;
    border-bottom-color: #004a93;
    font-weight: 500;
}
.header-search-btn {
    background: transparent !important;
    border: none !important;
    color: #6c757d !important;
    padding: 6px 10px !important;
    border-radius: 8px;
}
.header-search-btn:hover { color: #004a93 !important; }

/* Right side */
.header-right-actions { margin-right: 1rem; }
.header-icon-sm { font-size: 24px !important; }
.header-logout-icon { font-size: 22px !important; }
.header-last-login { font-size: 0.8125rem; }

/* Divider before logout */
.header-logout-divider {
    width: 1px;
    height: 28px;
    background: rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
}

/* Logout button - enhanced */
.header-logout-btn {
    gap: 3px;
    min-width: 52px;
    padding: 6px 10px !important;
    border-radius: 10px;
    color: #6c757d !important;
    border: 1px solid transparent;
    transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, transform 0.15s ease;
}
.header-logout-btn:hover {
    color: #004a93 !important;
    background-color: rgba(0, 74, 147, 0.08) !important;
    border-color: rgba(0, 74, 147, 0.12);
}
.header-logout-btn:active {
    transform: scale(0.97);
}

/* Notification dropdown: end-align on large screens, start-align on smaller for proper view */
.dropdown-menu-end-lg[data-bs-popper] {
    left: 0;
    right: auto;
}
@media (min-width: 992px) {
    .dropdown-menu-end-lg[data-bs-popper] {
        left: auto;
        right: 0;
    }
}

            @media (max-width: 991.98px) {
                body {
                    padding-bottom: 64px !important;
                }

                /* Mobile: Right-align logout and header actions */
                .header-right-actions {
                    width: 100%;
                    justify-content: flex-end !important;
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                    padding: 0.5rem 0;
                }

                /* Hide sidebar by default on mobile - responsive width */
                .left-sidebar,
                .side-mini-panel,
                aside.side-mini-panel,
                aside.side-mini-panel.with-vertical {
                    position: fixed !important;
                    top: 0 !important;
                    left: -100% !important;
                    width: min(320px, 88vw) !important;
                    max-width: 320px !important;
                    height: 100vh !important;
                    z-index: 1060 !important;
                    background: transparent !important;
                    transition: left 0.3s ease-in-out !important;
                    display: block !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                    overflow-y: auto !important;
                }

                /* Sidebar mini panel specific - compact when hidden */
                .side-mini-panel {
                    width: 64px !important;
                    left: -64px !important;
                }

                /* Hide sidebar tab content by default on mobile */
                #sidebarTabContent {
                    display: none !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                }

                .sidebar-overlay {
                    z-index: 1050 !important;
                }

                /* Ensure sidebar toggle button is accessible */
                #headerCollapse {
                    z-index: 1040 !important;
                    position: relative !important;
                    pointer-events: auto !important;
                }

                /* Show sidebar when toggled - handle all sidebar instances */
                .left-sidebar.show-sidebar,
                .side-mini-panel.show-sidebar,
                aside.side-mini-panel.show-sidebar,
                aside.side-mini-panel.with-vertical.show-sidebar,
                #sidebarTabContent .tab-pane.show.active .side-mini-panel.show-sidebar,
                #sidebarTabContent .tab-pane .side-mini-panel.show-sidebar {
                    left: 0 !important;
                    transform: translateX(0) !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    display: block !important;
                    background: transparent !important;
                    pointer-events: auto !important;
                }

                /* Expand side-mini-panel to responsive width on mobile when open - so child module (sidebar-nav) is visible */
                .side-mini-panel.show-sidebar,
                aside.side-mini-panel.show-sidebar,
                aside.side-mini-panel.with-vertical.show-sidebar {
                    width: min(320px, 88vw) !important;
                    max-width: 320px !important;
                }

                /* Show sidebar tab content when sidebar is open */
                body.sidebar-open #sidebarTabContent {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }
                
                /* Ensure active sidebar tab pane is visible */
                body.sidebar-open #sidebarTabContent .tab-pane.show.active {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }

                .nav-container.d-lg-none {
                    position: fixed !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    width: 100% !important;
                    z-index: 1030 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    pointer-events: none !important;
                    overflow: visible !important;
                }

                .nav-container.d-lg-none .mobile-tabbar {
                    pointer-events: auto !important;
                }

                .mobile-tabbar {
                    position: fixed !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    width: 100% !important;
                    z-index: 1030 !important;
                    display: flex !important;
                    flex-direction: row !important;
                    justify-content: space-around !important;
                    align-items: center !important;
                    gap: 2px !important;
                    padding: 6px 4px !important;
                    margin: 0 !important;
                    background: #ffffff !important;
                    border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
                    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.06) !important;
                    list-style: none !important;
                    height: 64px !important;
                    pointer-events: auto !important;
                    overflow: visible !important;
                }

                /* Hide mobile tab bar when sidebar is open - handled by JS */
                body.sidebar-open .mobile-tabbar {
                    display: none !important;
                }

                .mobile-tabbar .nav-item {
                    flex: 1 1 0 !important;
                    text-align: center !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }


                .mobile-tab-link {
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 2px !important;
                    padding: 6px 4px !important;
                    font-size: 11px !important;
                    color: #475569 !important;
                    border-radius: 10px !important;
                    text-decoration: none !important;
                    width: 100% !important;
                    height: 100% !important;
                    border: none !important;
                    background: transparent !important;
                    cursor: pointer !important;
                    pointer-events: auto !important;
                    -webkit-tap-highlight-color: rgba(29, 78, 216, 0.1) !important;
                    touch-action: manipulation !important;
                }

                .mobile-tab-link:hover,
                .mobile-tab-link:focus {
                    color: #1d4ed8 !important;
                    background: rgba(29, 78, 216, 0.05) !important;
                }

                .mobile-tab-link i {
                    font-size: 22px !important;
                    line-height: 22px !important;
                    display: block !important;
                }

                .mobile-tab-link span {
                    font-size: 10px !important;
                    line-height: 1.2 !important;
                    white-space: nowrap !important;
                }

                .mobile-tab-link.active {
                    color: #1d4ed8 !important;
                    background: rgba(29, 78, 216, 0.08) !important;
                }

                .mobile-tab-link.active i {
                    color: #1d4ed8 !important;
                }
            }

            /* Very small phones - narrower sidebar */
            @media (max-width: 375px) {
                .left-sidebar,
                .side-mini-panel,
                aside.side-mini-panel,
                aside.side-mini-panel.with-vertical {
                    width: min(280px, 92vw) !important;
                    max-width: 280px !important;
                }
                .side-mini-panel.show-sidebar,
                aside.side-mini-panel.show-sidebar,
                aside.side-mini-panel.with-vertical.show-sidebar {
                    width: min(280px, 92vw) !important;
                    max-width: 280px !important;
                }
            }

            @media (max-width: 991.98px) and (orientation: landscape) {
                .mobile-tabbar {
                    height: 56px !important;
                }

                body {
                    padding-bottom: 56px !important;
                }

                .mobile-tab-link {
                    padding: 4px 2px !important;
                }

                .mobile-tab-link i {
                    font-size: 20px !important;
                }

                .mobile-tab-link span {
                    font-size: 9px !important;
                }
            }

            /* Mobile notifications offcanvas - ensure it appears above tabbar */
            @media (max-width: 991.98px) {
                #notificationOffcanvasMobile {
                    z-index: 1100 !important;
                }
            }

            /* Desktop styles - ensure sidebar is visible */
            @media (min-width: 992px) {
                /* Reset any mobile-specific styles on desktop */
                .left-sidebar,
                .side-mini-panel,
                aside.side-mini-panel,
                aside.side-mini-panel.with-vertical {
                    position: fixed !important;
                    left: 0 !important;
                    top: 0 !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    display: block !important;
                }

                /* Ensure sidebar tab content is visible on desktop */
                #sidebarTabContent {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }

                /* Ensure active sidebar tab pane is visible */
                #sidebarTabContent .tab-pane.show.active {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }

                /* Remove overlay on desktop */
                .sidebar-overlay {
                    display: none !important;
                }

                /* Ensure body doesn't have mobile padding on desktop */
                body {
                    padding-bottom: 0 !important;
                }

                /* Reset any inline styles that might hide sidebar on desktop */
                .left-sidebar[style*="left: -"],
                .side-mini-panel[style*="left: -"],
                aside.side-mini-panel[style*="left: -"] {
                    left: 0 !important;
                }
            }
            </style>
<script>
    const root = document.documentElement;
    let fontSize = 100;

    document.querySelectorAll('[aria-label]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.textContent === 'A+') fontSize += 10;
            if (btn.textContent === 'A-') fontSize -= 10;
            if (btn.textContent === 'A') fontSize = 100;
            root.style.fontSize = fontSize + '%';
        });
    });
</script>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Sidebar toggle handler for mobile
                const sidebarToggler = document.getElementById('headerCollapse');
                const mobileTabbar = document.querySelector('.mobile-tabbar');
                
                // Function to get the currently active sidebar (from active tab)
                function getActiveSidebar() {
                    // First, try to find sidebar in the active sidebar tab pane
                    const activeSidebarPane = document.querySelector('#sidebarTabContent .tab-pane.show.active');
                    if (activeSidebarPane) {
                        const sidebarInPane = activeSidebarPane.querySelector('.side-mini-panel');
                        if (sidebarInPane) return sidebarInPane;
                    }
                    
                    // Fallback: find any visible sidebar
                    const visibleSidebar = document.querySelector('.side-mini-panel:not([style*="display: none"])');
                    if (visibleSidebar) return visibleSidebar;
                    
                    // Last resort: find any sidebar
                    return document.querySelector('.left-sidebar') || document.querySelector('.side-mini-panel');
                }
                
                function updateSidebarState() {
                    // Only apply mobile-specific styles on mobile
                    if (window.innerWidth >= 992) {
                        // On desktop, don't interfere with sidebar visibility
                        // Let desktop CSS handle it
                        return;
                    }
                    
                    const sidebar = getActiveSidebar();
                    if (!sidebar) return;
                    
                    const isOpen = sidebar.classList.contains('show-sidebar');
                    const sidebarTabContent = document.getElementById('sidebarTabContent');
                    
                    if (isOpen) {
                        document.body.classList.add('sidebar-open');
                        
                        // Ensure sidebar and its content are visible (mobile only)
                        sidebar.style.left = '0';
                        sidebar.style.visibility = 'visible';
                        sidebar.style.opacity = '1';
                        sidebar.style.display = 'block';
                        
                        // Ensure sidebar tab content is visible
                        if (sidebarTabContent) {
                            sidebarTabContent.style.display = 'block';
                            sidebarTabContent.style.visibility = 'visible';
                            sidebarTabContent.style.opacity = '1';
                        }
                        
                        // Ensure overlay is visible
                        let overlay = document.querySelector('.sidebar-overlay');
                        if (!overlay) {
                            overlay = document.createElement('div');
                            overlay.className = 'sidebar-overlay';
                            document.body.appendChild(overlay);
                        }
                        overlay.classList.add('active');
                        
                        // Prevent body scroll when sidebar is open
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.classList.remove('sidebar-open');
                        
                        // Hide sidebar (mobile only)
                        const sidebarWidth = sidebar.classList.contains('side-mini-panel') ? '-70px' : '-100%';
                        sidebar.style.left = sidebarWidth;
                        sidebar.style.visibility = 'hidden';
                        sidebar.style.opacity = '0';
                        
                        // Hide sidebar tab content
                        if (sidebarTabContent) {
                            sidebarTabContent.style.display = 'none';
                            sidebarTabContent.style.visibility = 'hidden';
                            sidebarTabContent.style.opacity = '0';
                        }
                        
                        // Hide overlay
                        const overlay = document.querySelector('.sidebar-overlay');
                        if (overlay) {
                            overlay.classList.remove('active');
                        }
                        
                        // Restore body scroll
                        document.body.style.overflow = '';
                    }
                }
                
                // Initialize: Hide sidebar by default on mobile only
                if (window.innerWidth < 992) {
                    const sidebar = getActiveSidebar();
                    if (sidebar) {
                        sidebar.classList.remove('show-sidebar');
                        const sidebarTabContent = document.getElementById('sidebarTabContent');
                        if (sidebarTabContent) {
                            sidebarTabContent.style.display = 'none';
                            sidebarTabContent.style.visibility = 'hidden';
                            sidebarTabContent.style.opacity = '0';
                        }
                    }
                } else {
                    // On desktop: ensure sidebar is visible and remove any mobile styles
                    const sidebar = getActiveSidebar();
                    if (sidebar) {
                        // Remove mobile-specific inline styles
                        sidebar.style.left = '';
                        sidebar.style.visibility = '';
                        sidebar.style.opacity = '';
                        sidebar.style.display = '';
                        
                        // Ensure sidebar tab content is visible
                        const sidebarTabContent = document.getElementById('sidebarTabContent');
                        if (sidebarTabContent) {
                            sidebarTabContent.style.display = '';
                            sidebarTabContent.style.visibility = '';
                            sidebarTabContent.style.opacity = '';
                        }
                    }
                }
                
                // Observe all sidebar elements for state changes
                function observeSidebars() {
                    const allSidebars = document.querySelectorAll('.side-mini-panel, .left-sidebar');
                    allSidebars.forEach(sidebar => {
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.attributeName === 'class') {
                                    updateSidebarState();
                                }
                            });
                        });
                        
                        observer.observe(sidebar, {
                            attributes: true,
                            attributeFilter: ['class']
                        });
                    });
                }
                
                observeSidebars();
                
                // Re-observe when tabs change (new sidebar content might be added)
                document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                    tab.addEventListener('shown.bs.tab', function() {
                        setTimeout(observeSidebars, 100);
                        updateSidebarState();
                    });
                });
                
                // Handle window resize - switch between mobile and desktop
                let resizeTimeout;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(function() {
                        if (window.innerWidth >= 992) {
                            // Switched to desktop - ensure sidebar is visible
                            const sidebar = getActiveSidebar();
                            if (sidebar) {
                                // Remove mobile-specific inline styles
                                sidebar.style.left = '';
                                sidebar.style.visibility = '';
                                sidebar.style.opacity = '';
                                sidebar.style.display = '';
                                
                                // Ensure sidebar tab content is visible
                                const sidebarTabContent = document.getElementById('sidebarTabContent');
                                if (sidebarTabContent) {
                                    sidebarTabContent.style.display = '';
                                    sidebarTabContent.style.visibility = '';
                                    sidebarTabContent.style.opacity = '';
                                }
                                
                                // Remove overlay
                                const overlay = document.querySelector('.sidebar-overlay');
                                if (overlay) {
                                    overlay.classList.remove('active');
                                }
                                
                                // Restore body scroll
                                document.body.style.overflow = '';
                                document.body.classList.remove('sidebar-open');
                            }
                        } else {
                            // Switched to mobile - apply mobile styles
                            updateSidebarState();
                        }
                    }, 150);
                });
                
                // Check initial state
                updateSidebarState();
                
                // Handle overlay clicks
                document.addEventListener('click', function(e) {
                    const overlay = document.querySelector('.sidebar-overlay');
                    if (overlay && e.target === overlay && overlay.classList.contains('active')) {
                        const sidebar = getActiveSidebar();
                        if (sidebar) {
                            sidebar.classList.remove('show-sidebar');
                            updateSidebarState();
                        }
                    }
                });
                
                // Ensure sidebar toggle button works
                if (sidebarToggler) {
                    sidebarToggler.addEventListener('click', function(e) {
                        // On desktop, let the default sidebar toggle behavior work
                        if (window.innerWidth >= 992) {
                            // Don't prevent default on desktop - let existing sidebar toggle handle it
                            return;
                        }
                        
                        // On mobile, handle toggle
                        e.preventDefault();
                        e.stopPropagation();
                        const sidebar = getActiveSidebar();
                        if (sidebar) {
                            sidebar.classList.toggle('show-sidebar');
                            updateSidebarState();
                        }
                    });
                }
                
                // Mobile collapse: document-level delegation (capture phase)
                let collapseHandledAt = 0;
                function handleMobileCollapse(e) {
                    if (window.innerWidth >= 992) return;
                    
                    const trigger = e.target.closest('[data-bs-toggle="collapse"]');
                    if (!trigger) return;
                    
                    const sidebarTabContent = document.getElementById('sidebarTabContent');
                    if (!sidebarTabContent || !sidebarTabContent.contains(trigger)) return;
                    
                    if (!document.querySelector('.side-mini-panel.show-sidebar')) return;
                    
                    // Prevent double-fire from pointerup + click on touch devices
                    const now = Date.now();
                    if (now - collapseHandledAt < 400) return;
                    collapseHandledAt = now;
                    
                    const targetId = (trigger.getAttribute('data-bs-target') || trigger.getAttribute('href') || '').replace(/^#/, '');
                    if (!targetId) return;
                    
                    const targetElement = document.getElementById(targetId);
                    if (!targetElement) return;
                    
                    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                        let bsCollapse = bootstrap.Collapse.getInstance(targetElement);
                        if (!bsCollapse) {
                            bsCollapse = new bootstrap.Collapse(targetElement, { toggle: false });
                        }
                        bsCollapse.toggle();
                        // Accordion: close other collapses in same sidebar-nav
                        const parentNav = trigger.closest('.sidebar-nav');
                        if (parentNav) {
                            parentNav.querySelectorAll('.collapse').forEach(c => {
                                if (c !== targetElement && c.classList.contains('show')) {
                                    const other = bootstrap.Collapse.getInstance(c);
                                    if (other) other.hide();
                                }
                            });
                        }
                        // Rotate arrow icon
                        const icon = trigger.querySelector('.material-icons');
                        if (icon) {
                            setTimeout(() => {
                                icon.textContent = targetElement.classList.contains('show') ? 'keyboard_arrow_up' : 'keyboard_arrow_down';
                            }, 350);
                        }
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
                
                document.addEventListener('pointerup', handleMobileCollapse, true);
                document.addEventListener('click', handleMobileCollapse, true);
             

                // Time format is already set in PHP, no need to override

                // Active tab indicator animation
                const activeTab = document.querySelector('.nav-link.active');
                const indicator = document.querySelector('.active-tab-indicator');

                if (activeTab && indicator) {
                    updateIndicatorPosition(activeTab);

                    // Listen for tab changes
                    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                        tab.addEventListener('shown.bs.tab', function(e) {
                            updateIndicatorPosition(e.target);

                            // Keep active state in sync between desktop and mobile tabs
                            const targetId = e.target.getAttribute('href');
                            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(link => {
                                if (link.getAttribute('href') === targetId) {
                                    link.classList.add('active');
                                    link.setAttribute('aria-selected', 'true');
                                } else {
                                    link.classList.remove('active');
                                    link.setAttribute('aria-selected', 'false');
                                }
                            });
                        });
                    });
                }

                function updateIndicatorPosition(element) {
                    const rect = element.getBoundingClientRect();
                    const parentRect = element.closest('.nav-container').getBoundingClientRect();

                    indicator.style.width = `${rect.width}px`;
                    indicator.style.transform = `translateX(${rect.left - parentRect.left}px)`;
                }

                // Enhanced dropdown interaction
                const financialDropdown = document.getElementById('financialDropdown');
                if (financialDropdown) {
                    financialDropdown.addEventListener('focus', function() {
                        this.setAttribute('aria-expanded', 'true');
                    });

                    financialDropdown.addEventListener('blur', function(e) {
                        if (!this.parentElement.contains(e.relatedTarget)) {
                            this.setAttribute('aria-expanded', 'false');
                        }
                    });
                }

                // Search trigger functionality - scroll to DataTables search or focus search input
                const searchTriggers = document.querySelectorAll('.search-trigger');
                if (searchTriggers.length) {
                    searchTriggers.forEach(trigger => {
                        trigger.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.setAttribute('aria-expanded', 'true');
                            // Find DataTables search input on current page
                            const dtSearchInput = document.querySelector('.dataTables_filter input');
                            if (dtSearchInput) {
                                dtSearchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                dtSearchInput.focus();
                            }
                        });
                    });
                }

                // Keyboard navigation enhancement
                document.querySelectorAll('.nav-link, .dropdown-item').forEach(item => {
                    item.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.click();
                        }
                    });
                });

                // Mobile tab handling - ensure Bootstrap tabs work
                const mobileTabs = document.querySelectorAll('.mobile-tab-link[data-bs-toggle="tab"]');
                mobileTabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        const href = this.getAttribute('href');
                        if (!href || href === '#') {
                            e.preventDefault();
                            return;
                        }
                        
                        // Find corresponding desktop tab and trigger it
                        const desktopTab = document.querySelector(`#mainNavbar .nav-link[href="${href}"]`);
                        if (desktopTab) {
                            e.preventDefault();
                            desktopTab.click();
                        } else {
                            // If no desktop tab found, try Bootstrap tab directly
                            if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                                try {
                                    const tabElement = new bootstrap.Tab(this);
                                    tabElement.show();
                                } catch(err) {
                                    console.log('Bootstrap tab error:', err);
                                }
                            }
                        }
                    });
                });
            });

            // Notification functions
            function markAsRead(notificationId) {
                fetch('/admin/notifications/mark-read-redirect/' + notificationId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Controller Response:', data)
                        if (data.success && data.redirect_url) {
                            // Redirect to the appropriate module view
                            window.location.href = data.redirect_url;
                        } else if (data.success) {
                            // Fallback: reload if no redirect URL
                            location.reload();
                        } else {
                            console.error('Failed to mark notification as read');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Fallback to dashboard on error
                        window.location.href = '{{ route("admin.dashboard") }}';
                    });
            }

            function markAllAsRead() {
                fetch('/admin/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
            </script>
        </nav>
    </div>
</header>



<!-- 🧠 Search Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll("time").forEach(function(el) {
        const dt = new Date(el.getAttribute("datetime"));

        const day = String(dt.getDate()).padStart(2, '0');
        const month = String(dt.getMonth() + 1).padStart(2, '0'); // JS months start at 0
        const year = dt.getFullYear();

        const hours = String(dt.getHours()).padStart(2, '0');
        const minutes = String(dt.getMinutes()).padStart(2, '0');
        const seconds = String(dt.getSeconds()).padStart(2, '0');

        el.textContent = `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('searchToggleBtn');
    const searchBox = document.getElementById('searchContainer');
    const closeBtn = document.getElementById('closeSearchBtn');
    const searchInput = document.getElementById('tableSearchInput');

    if (toggleBtn && searchBox) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            searchBox.classList.toggle('show');
            if (searchBox.classList.contains('show') && searchInput) {
                searchInput.focus();
            } else if (searchInput) {
                searchInput.value = '';
            }
        });
    }
    if (closeBtn && searchBox && searchInput) {
        closeBtn.addEventListener('click', () => {
            searchBox.classList.remove('show');
            searchInput.value = '';
        });
    }
    if (searchBox && toggleBtn) {
        document.addEventListener('click', (e) => {
            if (!searchBox.contains(e.target) && !toggleBtn.contains(e.target)) {
                searchBox.classList.remove('show');
            }
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && searchBox) {
            searchBox.classList.remove('show');
        }
    });
});

</script>

<!-- Fallback Tab Switcher (if Bootstrap JS not active) -->
<script>
    // Server-computed active tab (from PHP) - used for route-based tab highlighting
    window.SARGAM_ACTIVE_NAV_TAB = '{{ $activeNavTab }}';
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Include both desktop and mobile tabs
    const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
    const panes = document.querySelectorAll('#mainNavbarContent .tab-pane');

    function showPane(targetId) {
        if (!targetId || targetId === '#') return; // Skip empty hrefs
        
        panes.forEach(p => {
            if ('#' + p.id === targetId) {
                p.classList.add('show', 'active');
            } else {
                p.classList.remove('show', 'active');
            }
        });
        
        // Update all tabs (desktop and mobile)
        tabLinks.forEach(l => {
            const href = l.getAttribute('href');
            if (href === targetId) {
                l.classList.add('active');
                l.setAttribute('aria-selected', 'true');
            } else {
                l.classList.remove('active');
                l.setAttribute('aria-selected', 'false');
            }
        });
        
        // Save the active tab to localStorage
        localStorage.setItem('activeMainTab', targetId);
    }

    // Handle clicks on all tabs (desktop and mobile)
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = this.getAttribute('href');
            if (!target || target === '#') {
                e.preventDefault();
                return; // Skip tabs without proper href
            }
            
            e.preventDefault();
            showPane(target);
            history.replaceState(null, '', target);
            
            // Ensure default content within the activated tab
            if (typeof activateDefaultSubmenuForPane === 'function') {
                activateDefaultSubmenuForPane(target);
            }
        });
    });

    // Helper function to get cookie value
    function getCookie(name) {
        const nameEQ = name + '=';
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.indexOf(nameEQ) === 0) {
                return cookie.substring(nameEQ.length);
            }
        }
        return null;
    }

    // Helper function to delete cookie
    function deleteCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 UTC; path=/;';
    }

    // Determine initial tab: fresh login -> home; otherwise use route-based or saved tab
    const isFromLogin = getCookie('fresh_login');
    let initial;
    
    if (isFromLogin) {
        console.log('Fresh login detected - forcing home tab');
        initial = '#home';
        deleteCookie('fresh_login');
        localStorage.removeItem('activeMainTab');
    } else {
        const routeTab = window.SARGAM_ACTIVE_NAV_TAB || '#home';
        const savedTab = localStorage.getItem('activeMainTab');
        initial = savedTab || routeTab || '#home';
        console.log('Initial tab:', initial);
    }
    
    showPane(initial);
    
    // Sync mobile tabs with initial state
    const allTabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    allTabs.forEach(tab => {
        const href = tab.getAttribute('href');
        if (href === initial) {
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
        } else {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        }
    });
    
    // Apply default submenu/content for initial tab
    if (typeof activateDefaultSubmenuForPane === 'function') {
        activateDefaultSubmenuForPane(initial);
    }
});
</script>
<!-- 🌟 Header End -->