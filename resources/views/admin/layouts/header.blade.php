<header class="topbar">
    <!-- Skip to Content (GIGW Mandatory) -->
<a href="#main-content" class="visually-hidden-focusable skip-link">
    Skip to main content
</a>

<header class="bg-dark text-white border-bottom border-primary" style="height: 40px;">
    <div class="px-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap py-1">

            <!-- Left: Government Identity -->
            <div class="d-flex align-items-center gap-2">
                <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/330px-Flag_of_India.svg.png"
                     alt="Emblem of India" style="height:20px;">
                <span class="fw-semibold small">
                    भारत सरकार | Government of India
                </span>
            </div>

            <!-- Right: Utilities -->
            <nav aria-label="Utility Navigation">
                <ul class="list-inline mb-0 d-flex align-items-center gap-3 small">

                    <!-- Skip to Content -->
                    <li class="list-inline-item">
                        <a href="#main-content" class="text-white text-decoration-none">
                            Skip to content
                        </a>
                    </li>

                    <!-- Font Size Controls -->
                    <li class="list-inline-item d-flex align-items-center gap-1"
                        aria-label="Font size controls">
                        <a href="javascript:void(0)"class="text-white px-2"
                                aria-label="Decrease font size">A-</a>
                        <a href="javascript:void(0)"class="text-white px-2"
                                aria-label="Normal font size">A</a>
                        <a href="javascript:void(0)"class="text-white px-2"
                                aria-label="Increase font size">A+</a>
                    </li>

                    <!-- Language Switcher -->
                    <li class="list-inline-item">
                        <select class="form-select form-select-sm bg-dark text-white border-0"
                                aria-label="Select Language">
                            <option selected>English</option>
                            <option>हिन्दी</option>
                        </select>
                    </li>

                </ul>
            </nav>
        </div>
    </div>
</header>

<main id="main-content" tabindex="-1"></main>

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

            <div class=" py-9 py-xl-0">
                    <img src="{{ asset('images/ashoka.webp') }}" alt="ashoka emblem" style="height: 40px;"> | 
                    <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="logo">
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
                        <ul class="navbar-nav px-4 py-2 gap-2 align-items-center" style="border-radius: 20px; height: 60px; background: #f2f2f2; 
                       border: 1px solid rgba(0, 0, 0, 0.05);" role="menubar" aria-label="Main navigation">

                            <!-- Home -->
                            <li class="nav-item" role="none">
                                <a href="#home"
                                    class="nav-link active rounded-pill px-4 py-2 d-flex align-items-center gap-2"
                                    data-bs-toggle="tab" role="tab" aria-selected="true" aria-controls="home-panel"
                                    id="home-tab">
                                    <span>Home</span>
                                </a>
                            </li>

                            <!-- Setup -->
                            <li class="nav-item" role="none">
                                <a href="#tab-setup"
                                    class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 hover-lift"
                                    data-bs-toggle="tab" role="tab" aria-selected="false" aria-controls="setup-panel"
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
                                <a href="#"
                                    class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 hover-lift"
                                    data-bs-toggle="tab" role="tab" aria-selected="false"
                                    aria-controls="communications-panel" id="communications-tab">
                                    <span>Communications</span>
                                </a>
                            </li>

                            <!-- Academics -->
                            <!-- <li class="nav-item" role="none">
                                <a href="#tab-academics"
                                    class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 hover-lift"
                                    data-bs-toggle="tab" role="tab" aria-selected="false"
                                    aria-controls="academics-panel" id="academics-tab">
                                    <span>Academics</span>
                                </a>
                            </li> -->

                            <!-- Material Management -->
                            <li class="nav-item" role="none">
                                <a href="#"
                                    class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 hover-lift"
                                    data-bs-toggle="tab" role="tab" aria-selected="false"
                                    aria-controls="material-management-panel" id="material-management-tab">
                                    <span>Material Management</span>
                                </a>
                            </li>

                            <!-- Financial Dropdown - Enhanced -->
                            <li class="nav-item dropdown" role="none">
                                <a class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 hover-lift dropdown-toggle-custom"
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

                            <!-- Search with Enhanced UI -->
                            <li class="nav-item" role="none">
                                <button class="nav-link rounded-circle px-2 py-2 search-trigger hover-lift"
                                    style="width: 40px; height: 40px;" aria-label="Open search" aria-expanded="false"
                                    aria-controls="searchModal">
                                    <i class="material-icons material-symbols-rounded text-dark"
                                        style="font-size: 20px;" aria-hidden="true">search</i>
                                </button>
                            </li>
                        </ul>
                    </div>

                </div>

                <!-- Right Side Actions - Enhanced -->
                <div class="d-flex align-items-center ms-auto gap-2" style="margin-right: 56px;">
                    <!-- Notification Icon -->
                    <div class="dropdown position-relative">
                        <button type="button"
                            class="btn btn-outline-light border-0 p-2 rounded-circle hover-lift position-relative"
                            id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="Notifications" data-bs-placement="bottom" title="Notifications">
                            <i class="material-icons material-symbols-rounded" style="font-size: 30px; color: #475569;"
                                aria-hidden="true">notifications_active</i>
                            @php
                            $unreadCount = notification()->getUnreadCount(Auth::user()->user_id ?? 0);
                            @endphp
                            @if($unreadCount > 0)
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 10px;">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-xl p-2"
                            style="min-width: 350px; max-height: 400px; overflow-y: auto;"
                            aria-labelledby="notificationDropdown">
                            <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                                <span class="fw-semibold">Notifications</span>
                                @if($unreadCount > 0)
                                <button type="button" class="btn btn-sm btn-link text-primary p-0"
                                    onclick="markAllAsRead()">
                                    Mark all as read
                                </button>
                                @endif
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <div id="notificationList">
                                @php
                                $notifications = notification()->getNotifications(Auth::user()->user_id ?? 0, 10, false);
                                @endphp
                                @if($notifications->count() > 0)
                                @foreach($notifications as $notification)
                                <li>
                                    <a class="dropdown-item px-3 py-2 rounded-lg {{ $notification->is_read ? '' : 'bg-light' }}"
                                        href="javascript:void(0)" onclick="markAsRead({{ $notification->pk }})">
                                        <div class="d-flex flex-column">
                                            <div class="fw-semibold small">{{ $notification->title ?? 'Notification' }}
                                            </div>
                                            <div class="text-muted small mt-1">
                                                {{ Str::limit($notification->message ?? '', 50) }}</div>
                                            <div class="text-muted" style="font-size: 10px; margin-top: 4px;">
                                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                @endforeach
                                @else
                                <li class="px-3 py-4 text-center text-muted">
                                    <i class="material-icons material-symbols-rounded"
                                        style="font-size: 48px; opacity: 0.3;">notifications_none</i>
                                    <div class="mt-2">No notifications</div>
                                </li>
                                @endif
                            </div>
                        </ul>
                    </div>

<<<<<<< HEAD
                    <!-- Logout Button - Enhanced -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 d-inline" role="form">
                        @csrf
                        <button type="submit"
                            class="btn btn-outline-light border-0 p-2 rounded-circle hover-lift position-relative"
                            aria-label="Sign out from system" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            title="Sign Out">
                            <i class="material-icons material-symbols-rounded" style="font-size: 30px; color: #475569;"
                                aria-hidden="true">logout</i>
                            <span class="tooltip-text visually-hidden">Sign out from system</span>
                        </button>
                    </form>
=======
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
            class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 px-3 rounded-1 shadow-sm"
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
>>>>>>> ccdab091 (request for other)

                    <!-- Last Login - Enhanced -->
                    <div class="d-flex flex-column align-items-end">
                        <div class="text-muted small d-flex align-items-center gap-1"
                            style="font-size: 11px; line-height: 14px;">
                            <i class="material-icons material-symbols-rounded" style="font-size: 14px;"
                                aria-hidden="true">schedule</i>
                            <span class="fw-medium">Last login:</span>
                        </div>
                        @php
                        $lastLogin = Auth::user()->last_login ?? null;
                        if ($lastLogin) {
                        $date = \Carbon\Carbon::parse($lastLogin);
                        $formattedDate = $date->format('d-m-Y H:i:s');
                        $isoDate = $date->toIso8601String();
                        } else {
                        $formattedDate = 'Never';
                        $isoDate = '';
                        }
                        @endphp
                        <time id="myTime" datetime="{{ $isoDate }}" class="text-dark fw-semibold"
                            style="font-size: 13px; line-height: 16px;" aria-live="polite">
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
                        <a href="#home" class="nav-link active mobile-tab-link"
                            data-bs-toggle="tab" role="tab" aria-selected="true" aria-controls="home-panel"
                            id="home-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">home</i>
                            <span>Home</span>
                        </a>
                    </li>

                    <!-- Setup -->
                    <li class="nav-item" role="none">
                        <a href="#tab-setup" class="nav-link mobile-tab-link"
                            data-bs-toggle="tab" role="tab" aria-selected="false" aria-controls="setup-panel"
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
                        <a href="#" class="nav-link mobile-tab-link" data-bs-toggle="tab" role="tab"
                            aria-selected="false" aria-controls="communications-panel"
                            id="communications-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">forum</i>
                            <span>Comms</span>
                        </a>
                    </li>

                    <!-- Material Management -->
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link mobile-tab-link" data-bs-toggle="tab" role="tab"
                            aria-selected="false" aria-controls="material-management-panel"
                            id="material-management-tab-mobile">
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

                    <!-- Search -->
                    <li class="nav-item" role="none">
                        <button class="nav-link mobile-tab-link search-trigger"
                            aria-label="Open search" aria-expanded="false" aria-controls="searchModal">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">search</i>
                            <span>Search</span>
                        </button>
                    </li>
                </ul>
            </div>

            <style>
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

.skip-link:focus {
    top: 10px;
}

/* Improve focus visibility (GIGW) */
:focus-visible {
    outline: 3px solid #ffbf47;
    outline-offset: 2px;
}

            @media (max-width: 991.98px) {
                body {
                    padding-bottom: 64px !important;
                }

                /* Hide sidebar by default on mobile */
                .left-sidebar,
                .side-mini-panel,
                aside.side-mini-panel,
                aside.side-mini-panel.with-vertical {
                    position: fixed !important;
                    top: 0 !important;
                    left: -100% !important;
                    width: 350px !important;
                    height: 100vh !important;
                    z-index: 1060 !important;
                    background: #fff !important;
                    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1) !important;
                    transition: left 0.3s ease-in-out !important;
                    display: block !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                    overflow-y: auto !important;
                }

                /* Sidebar mini panel specific width */
                .side-mini-panel {
                    width: 70px !important;
                    left: -70px !important;
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
                
                // Ensure collapse/expand functionality works on mobile
                const collapseInitialized = new WeakSet();
                
                function initializeCollapseOnMobile() {
                    if (window.innerWidth >= 992) return; // Only on mobile
                    
                    // Find all collapse elements in sidebar tab content (even when hidden)
                    const sidebarTabContent = document.getElementById('sidebarTabContent');
                    if (!sidebarTabContent) return;
                    
                    // Find collapse buttons in all sidebar panes
                    const collapseButtons = sidebarTabContent.querySelectorAll('[data-bs-toggle="collapse"]');
                    
                    collapseButtons.forEach(collapseBtn => {
                        // Skip if already initialized
                        if (collapseInitialized.has(collapseBtn)) return;
                        collapseInitialized.add(collapseBtn);
                        
                        // Add click handler for mobile collapse
                        collapseBtn.addEventListener('click', function(e) {
                            const targetId = this.getAttribute('data-bs-target') || this.getAttribute('href');
                            if (!targetId) return;
                            
                            const targetElement = document.querySelector(targetId);
                            if (!targetElement) return;
                            
                            // Toggle sidebar visibility when clicking collapse/expand buttons
                            const sidebar = getActiveSidebar();
                            if (sidebar && window.innerWidth < 992) {
                                // On mobile: toggle show-sidebar class when clicking collapse/expand
                                sidebar.classList.toggle('show-sidebar');
                                updateSidebarState();
                            }
                            
                            // Don't prevent default - let Bootstrap handle collapse
                            // But ensure Bootstrap collapse is initialized
                            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                                let bsCollapse = bootstrap.Collapse.getInstance(targetElement);
                                if (!bsCollapse) {
                                    bsCollapse = new bootstrap.Collapse(targetElement, {
                                        toggle: false
                                    });
                                }
                            }
                        }, { once: false });
                    });
                }
                
                // Initialize collapse on mobile immediately
                if (window.innerWidth < 992) {
                    setTimeout(initializeCollapseOnMobile, 100);
                }
                
                // Re-initialize when sidebar becomes visible
                const sidebar = getActiveSidebar();
                if (sidebar) {
                    const sidebarVisibilityObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.attributeName === 'class') {
                                if (sidebar.classList.contains('show-sidebar')) {
                                    setTimeout(initializeCollapseOnMobile, 150);
                                }
                            }
                        });
                    });
                    
                    sidebarVisibilityObserver.observe(sidebar, {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                }
                
                // Re-initialize when tabs change
                document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                    tab.addEventListener('shown.bs.tab', function() {
                        setTimeout(initializeCollapseOnMobile, 200);
                    });
                });
             

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

                // Search trigger functionality
                const searchTriggers = document.querySelectorAll('.search-trigger');
                if (searchTriggers.length) {
                    searchTriggers.forEach(trigger => {
                        trigger.addEventListener('click', function() {
                            // Open search modal or expand search bar
                            this.setAttribute('aria-expanded', 'true');
                            // Add your search functionality here
                            console.log('Search triggered');
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

    // Open/close search
    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        searchBox.classList.toggle('show');
        if (searchBox.classList.contains('show')) {
            searchInput.focus();
        } else {
            searchInput.value = '';
        }
    });

    // Close via X button
    closeBtn.addEventListener('click', () => {
        searchBox.classList.remove('show');
        searchInput.value = '';
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!searchBox.contains(e.target) && !toggleBtn.contains(e.target)) {
            searchBox.classList.remove('show');
        }
    });

    // Close on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            searchBox.classList.remove('show');
        }
    });
});
</script>

<!-- Fallback Tab Switcher (if Bootstrap JS not active) -->
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

    // Determine initial tab based on current route
    // BUT: respect fresh_login flag - always show home after login
    const isFromLogin = getCookie('fresh_login');
    let initial;
    
    if (isFromLogin) {
        console.log('Fresh login detected - forcing home tab');
        initial = '#home';
        deleteCookie('fresh_login'); // Clear the flag
        localStorage.removeItem('activeMainTab'); // Clear saved tab
    } else {
        const routeTab = detectRouteTab();
        const savedTab = localStorage.getItem('activeMainTab') || routeTab;
        initial = savedTab || '#home';
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