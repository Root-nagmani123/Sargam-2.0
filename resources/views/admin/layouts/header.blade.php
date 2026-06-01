{{-- $activeNavTab is set in admin.layouts.master before this include --}}
<header class="topbar">
    <!-- Skip to Content (GIGW Mandatory) -->
<a href="#main-content" class="visually-hidden-focusable skip-link">
    Skip to main content
</a>

    <header class="header-top-bar d-none d-lg-block">
    <div class="container-fluid p-1 px-2">
    <div class="d-flex align-items-center justify-content-between flex-nowrap header-top-inner">

    <!-- Left: Government Identity -->
    <div class="d-flex align-items-center gap-2 text-nowrap header-govt-wrap">
        <span class="header-flag-wrap d-inline-flex align-items-center justify-content-center rounded-2 bg-white border border-light-subtle">
            <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/330px-Flag_of_India.svg.png"
                alt="Flag of India" class="header-flag-icon">
        </span>
        <span class="small text-white header-govt-text">
            भारत सरकार | Government of India
        </span>
    </div>

    <!-- Right: Utilities with vertical separators -->
    <nav aria-label="Utility Navigation" class="ms-auto">
        <ul class="list-inline mb-0 d-flex align-items-center gap-0 small header-utility-nav text-nowrap">
            <li class="list-inline-item">
                <a href="#main-content" class="text-white text-decoration-none px-2 header-utility-link">Skip to content</a>
            </li>
            <li class="header-utility-sep" aria-hidden="true"></li>
            <li class="list-inline-item d-flex align-items-center gap-1" aria-label="Font size controls">
                <a href="javascript:void(0)" class="text-white px-2 header-font-btn" aria-label="Increase font size">A+</a>
                <a href="javascript:void(0)" class="text-white px-2 header-font-btn" aria-label="Normal font size">A</a>
                <a href="javascript:void(0)" class="text-white px-2 header-font-btn" aria-label="Decrease font size">A-</a>
            </li>
            <li class="header-utility-sep" aria-hidden="true"></li>
            <li class="list-inline-item">
                <div class="header-lang-dropdown">
                    <i class="bi bi-globe2 header-globe-icon" aria-hidden="true"></i>
                    <select class="form-select form-select-sm header-lang-select" aria-label="Select Language">
                        <option selected>English</option>
                        <option>हिन्दी</option>
                    </select>
                    <i class="bi bi-chevron-down header-lang-caret" aria-hidden="true"></i>
                </div>
            </li>
        </ul>
    </nav>
    </div>
    </div>
    </header>

    <div class="with-vertical">
        <nav class="navbar navbar-expand-lg p-0 header-main-navbar">
            <div class="d-flex align-items-center flex-shrink-0 header-brand-block">
                <a class="nav-link nav-icon-hover-bg rounded-circle sidebartoggler d-lg-none me-1" id="headerCollapse"
                    href="javascript:void(0)" aria-label="Open menu">
                    <i class="material-icons material-symbols-rounded fs-6">menu</i>
                </a>
                <div class="header-brand d-flex align-items-center gap-2 py-2 px-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="" class="header-logo-emblem" width="44" height="44">
                    <span class="header-brand-divider" aria-hidden="true"></span>
                    <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="Sargam 2.0" class="header-logo">
                </div>
            </div>

            <button class="navbar-toggler p-0 border-0 d-lg-none ms-auto" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="material-icons material-symbols-rounded fs-6">more_vert</i>
            </button>

            <div class="collapse navbar-collapse flex-grow-1" id="navbarNav">
                <div class="header-nav-center d-none d-lg-flex flex-grow-1 justify-content-center px-2">
                    <div class="header-nav-scroll-wrap rounded-1">
                        <button type="button" class="header-nav-scroll-btn header-nav-scroll-btn--prev" aria-label="Scroll navigation left" title="Previous" hidden>
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">chevron_left</i>
                        </button>
                        <div class="header-nav-scroll" tabindex="0">
                            <ul class="navbar-nav header-main-nav align-items-center mb-0" id="mainNavbar" role="menubar" aria-label="Main navigation">
                                <li class="nav-item flex-shrink-0" role="none">
                                    <a href="#home"
                                        class="nav-link header-nav-link rounded-1 {{ $activeNavTab === '#home' ? 'active' : '' }}"
                                        data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#home' ? 'true' : 'false' }}"
                                        aria-controls="home-panel" id="home-tab">Home</a>
                                </li>
                                <li class="nav-item flex-shrink-0" role="none">
                                    <a href="#tab-setup"
                                        class="nav-link header-nav-link rounded-1 {{ $activeNavTab === '#tab-setup' ? 'active' : '' }}"
                                        data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-setup' ? 'true' : 'false' }}"
                                        aria-controls="setup-panel" id="setup-tab">
                                        @if(hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess-Staff') || hasRole('Training-Induction') || hasRole('IST'))
                                            Setup
                                        @elseif(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Student-OT'))
                                            Academics
                                        @else
                                            Setup
                                        @endif
                                    </a>
                                </li>
                                <li class="nav-item flex-shrink-0" role="none">
                                    <a href="#tab-communications"
                                        class="nav-link header-nav-link rounded-1 {{ $activeNavTab === '#tab-communications' ? 'active' : '' }}"
                                        data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-communications' ? 'true' : 'false' }}"
                                        aria-controls="tab-communications" id="communications-tab">Communications</a>
                                </li>
                                @if(hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess-Staff') || hasRole('Training-Induction') || hasRole('IST') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
                                <li class="nav-item flex-shrink-0" role="none">
                                    <a href="#tab-academics"
                                        class="nav-link header-nav-link rounded-1 {{ $activeNavTab === '#tab-academics' ? 'active' : '' }}"
                                        data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-academics' ? 'true' : 'false' }}"
                                        aria-controls="academics-panel" id="academics-tab">Academics</a>
                                </li>
                                @endif
                                @if(hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess-Staff'))
                                <li class="nav-item flex-shrink-0" role="none">
                                    <a href="#tab-material-management"
                                        class="nav-link header-nav-link rounded-1 {{ $activeNavTab === '#tab-material-management' ? 'active' : '' }}"
                                        data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-material-management' ? 'true' : 'false' }}"
                                        aria-controls="material-management-panel" id="material-management-tab">Material Management</a>
                                </li>
                                @endif
                            </ul>
                        </div>
                        <button type="button" class="header-nav-scroll-btn header-nav-scroll-btn--next" aria-label="Scroll navigation right" title="Next" hidden>
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">chevron_right</i>
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center ms-auto flex-shrink-0 gap-2 header-right-actions">

    <!-- Notifications (visible on both desktop and mobile) -->
    <div class="dropdown position-relative d-none d-lg-block">
        <button type="button"
            class="btn btn-light rounded-1 p-2 position-relative shadow-sm notification-btn "
            id="notificationDropdown"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Notifications">

            @php
                $unreadCount = (Auth::user() && Auth::user()->user_id)
                    ? notification()->getUnreadCount(
                        Auth::user()->user_id,
                        hasRole('Admin') ? 10 : null
                    )
                    : 0;
            @endphp

            <i class="material-icons material-symbols-rounded fs-5 header-notification-bell {{ $unreadCount > 0 ? 'header-notification-bell--ring' : '' }}"
                aria-hidden="true">notifications</i>

            @if($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <!-- Dropdown -->
        <ul class="dropdown-menu dropdown-menu-end shadow border rounded-3 p-0 bg-white notification-dropdown"
            aria-labelledby="notificationDropdown">

            <li class="notification-dropdown-header d-flex justify-content-between align-items-center px-3 py-3 border-bottom bg-white">
                <span class="fw-bold text-dark mb-0">Notifications</span>
                @if($unreadCount > 0)
                    <button type="button"
                        class="btn btn-link link-primary p-0 text-decoration-underline small notification-mark-all-btn"
                        onclick="markAllAsRead()">
                        Mark all as read
                    </button>
                @endif
            </li>

            <li class="list-unstyled mb-0">
                <div id="notificationList" class="notification-list px-3 py-2">
                    @php
                        $notifications = (Auth::user() && Auth::user()->user_id)
                            ? notification()->getNotifications(
                                Auth::user()->user_id,
                                10,
                                false,
                                hasRole('Admin') ? 10 : null
                            )
                            : collect();
                    @endphp

                    @include('admin.layouts.partials.notification-list-desktop', ['notifications' => $notifications])
                </div>
            </li>

            <li class="notification-dropdown-footer border-top text-center py-3 bg-white">
                <a href="{{ route('admin.dashboard.feed', ['tab' => 'notifications']) }}" class="link-primary text-decoration-underline small notification-view-all-link">
                    Check all notifications
                </a>
            </li>
        </ul>
    </div>

    @php
        $authUser = Auth::user();
        $displayName = trim(($authUser->first_name ?? '') . ' ' . ($authUser->last_name ?? ''));
        if ($displayName === '') {
            $displayName = $authUser->name ?? $authUser->full_name ?? $authUser->user_name ?? 'User';
        }
        $profileHref = 'javascript:void(0)';
        if (\Illuminate\Support\Facades\Route::has('member.profile.edit') && !empty($authUser->user_id)) {
            $profileHref = route('member.profile.edit', $authUser->user_id);
        }
        $avatarInitial = strtoupper(mb_substr(trim($displayName), 0, 1));
        $profilePic = function_exists('get_profile_pic') ? get_profile_pic() : '';
        $headerRoles = session('user_roles', []);
        if (in_array('Student-OT', $headerRoles, true) && function_exists('service_find')) {
            $headerRoleLabel = 'Student-OT (' . service_find() . ')';
        } elseif (!in_array('Student-OT', $headerRoles, true) && $authUser && ($authUser->user_category ?? '') === 'E') {
            $headerRoleLabel = 'Employee (' . implode(', ', $headerRoles) . ')';
        } else {
            $headerRoleLabel = !empty($headerRoles) ? implode(', ', $headerRoles) : 'Staff';
        }
        $profileDropdownRole = !empty($headerRoles) ? $headerRoles[0] : 'Staff';
        $showProfileMenuActions = !hasRole('Student-OT') && $authUser && !empty($authUser->user_id);
    @endphp

    <div class="dropdown d-none d-lg-block header-profile-dropdown-wrap">
        <button type="button"
            class="header-profile-chip btn btn-link d-inline-flex align-items-center gap-2 text-decoration-none text-dark border-0 p-0 shadow-none"
            id="headerProfileDropdown"
            data-bs-toggle="dropdown"
            data-bs-auto-close="true"
            aria-expanded="false"
            aria-label="Open profile menu">
            <span class="header-user-avatar flex-shrink-0">
                <img src="{{ $profilePic ?: asset('images/dummypic.jpeg') }}"
                    alt="{{ $displayName }}"
                    class="rounded-circle object-fit-cover header-user-avatar-img"
                    width="44"
                    height="44"
                    onerror="this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none');this.nextElementSibling.classList.add('d-inline-flex');">
                <span class="header-user-avatar-fallback rounded-circle bg-light text-dark fw-semibold d-none align-items-center justify-content-center">
                    {{ $avatarInitial }}
                </span>
            </span>
            <span class="d-flex flex-column lh-sm min-w-0 text-start header-profile-meta">
                <span class="fw-semibold text-dark text-truncate header-profile-name">{{ $displayName }}</span>
                <small class="text-muted text-truncate header-profile-role">{{ $headerRoleLabel }}</small>
            </span>
            <i class="material-icons material-symbols-rounded header-profile-chevron text-secondary flex-shrink-0" aria-hidden="true">expand_more</i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end border-0 rounded-3 p-2 header-profile-dropdown"
            aria-labelledby="headerProfileDropdown">
            <li class="px-1">
                <div class="rounded-2 p-3 d-flex align-items-center gap-3 header-profile-dropdown-header">
                    <span class="header-user-avatar flex-shrink-0">
                        <img src="{{ $profilePic ?: asset('images/dummypic.jpeg') }}"
                            alt="{{ $displayName }}"
                            class="rounded-circle object-fit-cover header-user-avatar-img"
                            width="44"
                            height="44"
                            onerror="this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none');this.nextElementSibling.classList.add('d-inline-flex');">
                        <span class="header-user-avatar-fallback rounded-circle bg-white text-dark fw-semibold d-none align-items-center justify-content-center">
                            {{ $avatarInitial }}
                        </span>
                    </span>
                    <span class="d-flex flex-column lh-sm min-w-0">
                        <span class="fw-bold text-dark text-truncate mb-0">{{ $displayName }}</span>
                        <small class="text-body-secondary">{{ $profileDropdownRole }}</small>
                    </span>
                </div>
            </li>
            <li class="header-profile-menu-list pt-2 px-1">
                @if($showProfileMenuActions)
                <a class="dropdown-item rounded-1 d-flex align-items-center gap-3 py-2 px-2"
                    href="{{ $profileHref }}">
                    <i class="material-icons material-symbols-rounded header-profile-menu-icon" aria-hidden="true">edit</i>
                    <span>Edit Profile</span>
                </a>
                <a class="dropdown-item rounded-1 d-flex align-items-center gap-3 py-2 px-2"
                    href="{{ route('admin.password.change_password') }}">
                    <i class="material-icons material-symbols-rounded header-profile-menu-icon" aria-hidden="true">lock_reset</i>
                    <span>Change Password</span>
                </a>
                @endif
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit"
                        class="dropdown-item rounded-1 d-flex align-items-center gap-3 py-2 px-2 w-100 border-0 bg-transparent header-profile-logout-item">
                        <i class="material-icons material-symbols-rounded header-profile-menu-icon" aria-hidden="true">logout</i>
                        <span>Log out</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>

    <form action="{{ route('logout') }}" method="POST" class="m-0 header-logout-form d-lg-none">
        @csrf
        <button type="submit"
            class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center rounded-3 header-logout-icon-btn"
            aria-label="Sign out">
            <i class="material-icons material-symbols-rounded fs-6">logout</i>
        </button>
    </form>
</div>

                </div>

                <!-- Mobile Navigation Container (FB/Instagram-style) -->
                <div class="nav-container d-lg-none">
                    <ul class="navbar-nav mobile-tabbar" role="menubar" aria-label="Main navigation mobile">
                        <!-- Home -->
                        <li class="nav-item" role="none">
                            <a href="#home" class="nav-link mobile-tab-link {{ $activeNavTab === '#home' ? 'active' : '' }}"
                                data-bs-toggle="tab" role="tab"
                                aria-selected="{{ $activeNavTab === '#home' ? 'true' : 'false' }}"
                                aria-controls="home-panel" id="home-tab-mobile">
                                <i class="material-icons material-symbols-rounded" aria-hidden="true">home</i>
                                <span>Home</span>
                            </a>
                        </li>

                        <!-- Setup -->
                        <li class="nav-item" role="none">
                            <a href="#tab-setup"
                                class="nav-link mobile-tab-link {{ $activeNavTab === '#tab-setup' ? 'active' : '' }}"
                                data-bs-toggle="tab" role="tab"
                                aria-selected="{{ $activeNavTab === '#tab-setup' ? 'true' : 'false' }}"
                                aria-controls="setup-panel" id="setup-tab-mobile">
                                <i class="material-icons material-symbols-rounded" aria-hidden="true">settings</i>
                                @if(hasRole('Admin') || hasRole('Training-Induction') || hasRole('Staff'))
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
                            data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-communications' ? 'true' : 'false' }}" aria-controls="tab-communications"
                            id="communications-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">forum</i>
                            <span>Communications</span>
                        </a>
                    </li>

                    @if(hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess-Staff') || hasRole('Training-Induction') || hasRole('IST') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
                    <li class="nav-item" role="none">
                        <a href="#tab-academics" class="nav-link mobile-tab-link {{ $activeNavTab === '#tab-academics' ? 'active' : '' }}"
                            data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-academics' ? 'true' : 'false' }}"
                            aria-controls="academics-panel" id="academics-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">school</i>
                            <span>Academics</span>
                        </a>
                    </li>
                    @endif

                    @if(hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess-Staff'))
                    <li class="nav-item" role="none">
                        <a href="#tab-material-management" class="nav-link mobile-tab-link {{ $activeNavTab === '#tab-material-management' ? 'active' : '' }}"
                            data-bs-toggle="tab" role="tab" aria-selected="{{ $activeNavTab === '#tab-material-management' ? 'true' : 'false' }}"
                            aria-controls="material-management-panel" id="material-management-tab-mobile">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">inventory_2</i>
                            <span>Material</span>
                        </a>
                    </li>
                    @endif

                    <!-- Notifications (Offcanvas on mobile for reliable display) -->
                    <li class="nav-item" role="none">
                        <button type="button"
                            class="nav-link mobile-tab-link border-0 bg-transparent p-0 position-relative"
                            id="notificationBtnMobile" data-bs-toggle="offcanvas" data-bs-target="#notificationOffcanvasMobile"
                            aria-controls="notificationOffcanvasMobile" aria-label="Notifications" title="Notifications">
                            @php
                            $unreadCountMobile = (Auth::user() && Auth::user()->user_id)
                                ? notification()->getUnreadCount(
                                    Auth::user()->user_id,
                                    hasRole('Admin') ? 10 : null
                                )
                                : 0;
                            @endphp
                            <i class="material-icons material-symbols-rounded header-notification-bell {{ $unreadCountMobile > 0 ? 'header-notification-bell--ring' : '' }}"
                                aria-hidden="true">notifications_active</i>
                            @if($unreadCountMobile > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge-mobile" style="font-size: 9px;">
                                {{ $unreadCountMobile > 99 ? '99+' : $unreadCountMobile }}
                            </span>
                            @endif
                            <span>Notifications</span>
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
                    <button type="button" class="btn btn-sm btn-link text-primary p-0 notification-mark-all-btn" onclick="markAllAsRead()">
                        Mark all as read
                    </button>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0 overflow-y-auto notification-mobile-list" style="max-height: calc(70vh - 60px);">
                    <div id="notificationListMobile">
                        @php
                        $notificationsMobile = (Auth::user() && Auth::user()->user_id)
                            ? notification()->getNotifications(
                            Auth::user()->user_id,
                            10,
                            false,
                            hasRole('Admin') ? 10 : null
                            )
                            : collect();
                            @endphp
                            @include('admin.layouts.partials.notification-list-mobile', ['notifications' => $notificationsMobile])
                        </div>
                    </div>
                </div>

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
                        // Accordion: close sibling collapses only (keep nested/parent open)
                        if (typeof window.sargamCloseSiblingSidebarCollapses === 'function') {
                            window.sargamCloseSiblingSidebarCollapses(targetElement);
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
                                    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(
                                        link => {
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
                                    const dtSearchInput = document.querySelector(
                                        '.dataTables_filter input');
                                    if (dtSearchInput) {
                                        dtSearchInput.scrollIntoView({
                                            behavior: 'smooth',
                                            block: 'center'
                                        });
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
            const notificationMarkReadUrlTemplate = '{{ route("admin.notifications.mark-read-redirect", ["id" => "__ID__"]) }}';
            const notificationMarkAllReadUrl = '{{ route("admin.notifications.mark-all-read") }}';
            const notificationPanelsUrl = '{{ route("admin.notifications.panels") }}';
            function markAsRead(notificationId) {
                console.log('[Notification][Step 1] markAsRead called', { notificationId, currentUrl: window.location.href });
                const endpoint = '/admin/notifications/mark-read-redirect/' + notificationId;
                console.log('[Notification][Step 2] Calling endpoint', { endpoint });
                fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => {
                        console.log('[Notification][Step 3] HTTP response', {
                            status: response.status,
                            ok: response.ok,
                            redirected: response.redirected,
                            responseUrl: response.url
                        });
                        return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
                    })
                    .then(({ ok, status, data }) => {
                        console.log('[Notification][Step 4] Controller JSON payload', { ok, status, data });
                        if (data.success && data.redirect_url) {
                            // Redirect to the appropriate module view
                            console.log('[Notification][Step 5] Redirecting to redirect_url', { redirectUrl: data.redirect_url });
                            window.location.href = data.redirect_url;
                        } else if (data.success) {
                            // Fallback: reload if no redirect URL
                            console.log('[Notification][Step 5] success=true but redirect_url missing, triggering reload');
                            // location.reload(); //ye redirect ho rha h 
                        } else {
                            console.log('[Notification][Step 5] Failed to mark notification as read', data);
                        }
                    })
                    .catch(error => {
                        console.log('[Notification][Step X] Exception in markAsRead', error);
                        // Fallback to dashboard on error
                        console.log('[Notification][Fallback] Redirecting to dashboard due to error');
                        // window.location.href = '{{ route("admin.dashboard") }}';
                    });
            }

            // Notification click (avoid inline onclick to prevent Blade JS parsing issues)
            document.addEventListener('click', function (e) {
                // Don't intercept wish reply buttons or wish card clicks — handled by page-level logic
                if (e.target && e.target.closest && e.target.closest('.btn-wish-reply')) return;
                if (e.target && e.target.closest && e.target.closest('.dashboard-feed-wish-card')) return;

                const target = e.target && e.target.closest ? e.target.closest('[data-notification-id]') : null;
                if (!target) return;

                const id = target.getAttribute('data-notification-id');
                if (!id) return;

                console.log('[Notification][Step 0] Notification clicked', {
                    id,
                    elementClass: target.className
                });
                markAsRead(id);
            });

            function updateHeaderNotificationBellRing(unreadCount) {
                document.querySelectorAll('.header-notification-bell').forEach(function (el) {
                    el.classList.toggle('header-notification-bell--ring', unreadCount > 0);
                });
            }

            function updateNotificationBadges(unreadCount) {
                document.querySelectorAll('.notification-badge, .notification-badge-mobile').forEach(function (el) {
                    el.remove();
                });
                updateHeaderNotificationBellRing(unreadCount || 0);
                if (!unreadCount || unreadCount <= 0) {
                    return;
                }
                var label = unreadCount > 99 ? '99+' : String(unreadCount);
                var desktopBtn = document.getElementById('notificationDropdown');
                if (desktopBtn) {
                    var desktopBadge = document.createElement('span');
                    desktopBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge';
                    desktopBadge.textContent = label;
                    desktopBtn.appendChild(desktopBadge);
                }
                var mobileBtn = document.getElementById('notificationBtnMobile');
                if (mobileBtn) {
                    var mobileBadge = document.createElement('span');
                    mobileBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge-mobile';
                    mobileBadge.style.fontSize = '9px';
                    mobileBadge.textContent = label;
                    mobileBtn.appendChild(mobileBadge);
                }
            }

            function toggleMarkAllButtons(unreadCount) {
                document.querySelectorAll('.notification-mark-all-btn').forEach(function (btn) {
                    btn.classList.toggle('d-none', !unreadCount || unreadCount <= 0);
                });
            }

                    function refreshNotificationPanels() {
                        return fetch(notificationPanelsUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                if (!data || !data.success) {
                                    return;
                                }
                                var desktopList = document.getElementById('notificationList');
                                var mobileList = document.getElementById('notificationListMobile');
                                if (desktopList && data.desktop_html) {
                                    desktopList.innerHTML = data.desktop_html;
                                }
                                if (mobileList && data.mobile_html) {
                                    mobileList.innerHTML = data.mobile_html;
                                }
                                updateNotificationBadges(data.unread_count || 0);
                                toggleMarkAllButtons(data.unread_count || 0);
                            })
                            .catch(function(error) {
                                console.error('[Notification] Failed to refresh panels', error);
                            });
                    }

                    function markAllAsRead() {
                        fetch(notificationMarkAllReadUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                if (data && data.success) {
                                    refreshNotificationPanels();
                                }
                            })
                            .catch(function(error) {
                                console.error('[Notification][AllRead] Exception', error);
                            });
                    }
                </script>
        </nav>
    </div>
</header>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        function scrollHeaderMainNavActiveTab(link) {
            var viewport = link.closest('.header-main-nav-scroll__viewport') ||
                document.querySelector('.header-main-nav-scroll__viewport');
            if (!viewport || !link) {
                return;
            }
            var linkRect = link.getBoundingClientRect();
            var vpRect = viewport.getBoundingClientRect();
            if (linkRect.left < vpRect.left || linkRect.right > vpRect.right) {
                link.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'nearest'
                });
            }
        }

        function updateHeaderMainNavScrollState() {
            document.querySelectorAll('.header-main-nav-scroll').forEach(function(wrap) {
                var viewport = wrap.querySelector('.header-main-nav-scroll__viewport');
                if (!viewport) {
                    return;
                }
                var scrollable = viewport.scrollWidth > viewport.clientWidth + 1;
                wrap.classList.toggle('is-scrollable', scrollable);
            });
        }

        document.querySelectorAll('.header-main-nav-scroll__viewport').forEach(function(viewport) {
            viewport.addEventListener('scroll', updateHeaderMainNavScrollState, {
                passive: true
            });
            if (typeof ResizeObserver !== 'undefined') {
                new ResizeObserver(updateHeaderMainNavScrollState).observe(viewport);
            }
        });

        updateHeaderMainNavScrollState();
        window.addEventListener('resize', updateHeaderMainNavScrollState);
        setTimeout(updateHeaderMainNavScrollState, 150);

        document.querySelectorAll('.header-main-nav-scroll__viewport .header-nav-link.active').forEach(function(el) {
            scrollHeaderMainNavActiveTab(el);
        });

        document.querySelectorAll('#mainNavbar [data-bs-toggle="tab"]').forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(e) {
                if (e.target && e.target.classList.contains('header-nav-link')) {
                    scrollHeaderMainNavActiveTab(e.target);
                }
                updateHeaderMainNavScrollState();
            });
        });
    });
</script>

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

    var headerNavScrollStep = 140;
    var headerNavMaxWidth = 760;

    document.querySelectorAll('.header-nav-scroll-wrap').forEach(function (wrap) {
        var scrollEl = wrap.querySelector('.header-nav-scroll');
        var btnPrev = wrap.querySelector('.header-nav-scroll-btn--prev');
        var btnNext = wrap.querySelector('.header-nav-scroll-btn--next');

        [btnPrev, btnNext].forEach(function (btn) {
            if (!btn) {
                return;
            }
            btn.addEventListener('click', function () {
                if (!scrollEl || btn.disabled) {
                    return;
                }
                var delta = btn.classList.contains('header-nav-scroll-btn--prev') ? -headerNavScrollStep : headerNavScrollStep;
                scrollEl.scrollBy({ left: delta, behavior: 'smooth' });
            });
        });

        if (scrollEl) {
            scrollEl.addEventListener('scroll', function () {
                updateHeaderNavScrollButtons(wrap);
            }, { passive: true });
        }
    });

    function setHeaderNavScrollButtonsVisible(wrap, visible) {
        var btnPrev = wrap.querySelector('.header-nav-scroll-btn--prev');
        var btnNext = wrap.querySelector('.header-nav-scroll-btn--next');
        [btnPrev, btnNext].forEach(function (btn) {
            if (btn) {
                btn.hidden = !visible;
            }
        });
        if (visible) {
            updateHeaderNavScrollButtons(wrap);
        }
    }

    function updateHeaderNavScrollButtons(wrap) {
        var scrollEl = wrap.querySelector('.header-nav-scroll');
        var btnPrev = wrap.querySelector('.header-nav-scroll-btn--prev');
        var btnNext = wrap.querySelector('.header-nav-scroll-btn--next');
        if (!scrollEl || !wrap.classList.contains('is-overflow')) {
            return;
        }

        var atStart = scrollEl.scrollLeft <= 1;
        var atEnd = scrollEl.scrollLeft + scrollEl.clientWidth >= scrollEl.scrollWidth - 1;

        if (btnPrev) {
            btnPrev.disabled = atStart;
            btnPrev.classList.toggle('is-disabled', atStart);
        }
        if (btnNext) {
            btnNext.disabled = atEnd;
            btnNext.classList.toggle('is-disabled', atEnd);
        }
    }

    function updateHeaderNavLayout() {
        document.querySelectorAll('.header-nav-scroll-wrap').forEach(function (wrap) {
            var scrollEl = wrap.querySelector('.header-nav-scroll');
            var nav = wrap.querySelector('.header-main-nav');
            if (!scrollEl || !nav) {
                return;
            }

            wrap.classList.remove('is-overflow', 'is-expanded');
            setHeaderNavScrollButtonsVisible(wrap, false);

            var parentWidth = wrap.parentElement ? wrap.parentElement.clientWidth : window.innerWidth;
            var cap = Math.min(headerNavMaxWidth, parentWidth || headerNavMaxWidth);
            var chrome = 72;
            var navWidth = nav.scrollWidth;
            var needsOverflow = navWidth + chrome > cap || navWidth > scrollEl.clientWidth + 1;

            if (needsOverflow) {
                wrap.classList.add('is-overflow', 'is-expanded');
                setHeaderNavScrollButtonsVisible(wrap, true);
            }
        });
    }

    updateHeaderNavLayout();

    var headerNavResizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(headerNavResizeTimer);
        headerNavResizeTimer = setTimeout(updateHeaderNavLayout, 120);
    });

    document.querySelectorAll('.header-main-nav').forEach(function (nav) {
        if (typeof ResizeObserver !== 'undefined') {
            var ro = new ResizeObserver(function () {
                updateHeaderNavLayout();
            });
            ro.observe(nav);
        }
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
    window.SARGAM_DASHBOARD_URL = "{{ route('admin.dashboard') }}";
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const headerTabLinks = document.querySelectorAll(
        '#mainNavbar .nav-link[data-bs-toggle="tab"], .mobile-tabbar .nav-link[data-bs-toggle="tab"]'
    );
    const panes = document.querySelectorAll('#mainNavbarContent .tab-pane');

    const sidebarTabMap = {
        '#home': '#sidebar-home',
        '#tab-setup': '#sidebar-setup',
        '#tab-communications': '#sidebar-communications',
        '#tab-academics': '#sidebar-academics',
        '#tab-material-management': '#sidebar-purchase-order'
    };

    function syncSidebarPane(targetId) {
        const sidebarTabContent = document.getElementById('sidebarTabContent');
        if (!sidebarTabContent) return;

        sidebarTabContent.querySelectorAll('.tab-pane').forEach(function (pane) {
            pane.classList.remove('show', 'active');
        });

        const sidebarSel = sidebarTabMap[targetId];
        const sidebarPane = sidebarSel
            ? sidebarTabContent.querySelector(sidebarSel + '.tab-pane')
            : null;

        if (sidebarPane) {
            sidebarPane.classList.add('show', 'active');
        } else {
            const homeSidebar = sidebarTabContent.querySelector('#sidebar-home.tab-pane');
            if (homeSidebar) {
                homeSidebar.classList.add('show', 'active');
            }
        }
    }

    function setHeaderTabActiveState(targetId) {
        headerTabLinks.forEach(function (link) {
            const href = link.getAttribute('href');
            if (href === targetId) {
                link.classList.add('active');
                link.setAttribute('aria-selected', 'true');
            } else {
                link.classList.remove('active');
                link.setAttribute('aria-selected', 'false');
            }
        });
    }

    function showPane(targetId) {
        if (!targetId || targetId === '#') return;

        const targetPaneId = getMainContentPaneId(targetId);
        const targetPane = targetPaneId ? document.getElementById(targetPaneId) : null;
        const canSwitchBodyPane = !!(targetPane && targetPane.children.length > 0);

        if (canSwitchBodyPane) {
            panes.forEach(function (p) {
                if ('#' + p.id === targetId) {
                    p.classList.add('show', 'active');
                } else {
                    p.classList.remove('show', 'active');
                }
            });
        }

        syncSidebarPane(targetId);
        setHeaderTabActiveState(targetId);
        localStorage.setItem('activeMainTab', targetId);
    }

    function getMainContentPaneId(targetId) {
        const map = {
            '#home': 'home',
            '#tab-setup': 'tab-setup',
            '#tab-communications': 'tab-communications',
            '#tab-academics': 'tab-academics',
            '#tab-material-management': 'tab-material-management'
        };
        return map[targetId] || null;
    }

    function isMainContentPaneEmpty(targetId) {
        const pid = getMainContentPaneId(targetId);
        if (!pid) return true;
        const pane = document.querySelector('#mainNavbarContent #' + pid);
        if (!pane) return true;
        return pane.children.length === 0;
    }

    /**
     * After a tab switch, show the first mini-nav submenu so the sidebar is never blank.
     */
    function activateDefaultSubmenuForPane(targetId) {
        const sidebarTabMap = {
            '#home': '#sidebar-home',
            '#tab-setup': '#sidebar-setup',
            '#tab-communications': '#sidebar-communications',
            '#tab-academics': '#sidebar-academics',
            '#tab-material-management': '#sidebar-purchase-order'
        };
        const sidebarPaneSel = sidebarTabMap[targetId];
        if (!sidebarPaneSel) return;

        const pane = document.querySelector('#sidebarTabContent ' + sidebarPaneSel + '.tab-pane');
        if (!pane) return;

            const storageKey = 'active-mini-nav-' + (pane.id || 'global');
            let storedId = localStorage.getItem(storageKey);
            let itemEl = null;
            if (storedId) {
                try {
                    itemEl = pane.querySelector('#' + (typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(
                        storedId) : storedId.replace(/([#.;?+*^$[\]\\(){}|\-])/g, '\\$1')));
                } catch (err) {
                    itemEl = null;
                }
            }
            if (!itemEl) {
                itemEl = pane.querySelector('.mini-nav .mini-nav-item[id]') || pane.querySelector(
                    '.mini-nav .mini-nav-item');
            }
            if (!itemEl || !itemEl.id) return;

        pane.querySelectorAll('.mini-nav .mini-nav-item').forEach(el => el.classList.remove('selected'));
        itemEl.classList.add('selected');

        const targetMenuId = 'menu-right-' + itemEl.id;
        const allMenus = pane.querySelectorAll('.sidebarmenu nav');
        allMenus.forEach(nav => {
            nav.classList.remove('d-block');
            nav.style.display = 'none';
        });
        const safeId = targetMenuId.replace(/([#.;?+*^$[\]\\(){}|\-])/g, '\\$1');
        const targetMenu = pane.querySelector('#' + safeId);
        if (targetMenu) {
            targetMenu.classList.add('d-block');
            targetMenu.style.display = 'block';
            document.body.setAttribute('data-sidebartype', 'full');
        }

    }

    // Handle clicks on header tabs only (not sidebar/content sub-tabs)
    headerTabLinks.forEach(function (link) {
        link.addEventListener('click', function(e) {
            const target = this.getAttribute('href');
            if (!target || target === '#') {
                e.preventDefault();
                return; // Skip tabs without proper href
            }

                // From Setup (or any non-Home main tab), "Home" must load the dashboard route
                // so the main content area is not an empty/stale #home pane.
                // Note: some setup-only pages (e.g. admin.mess.*) used to leave $activeNavTab as #home;
                // also detect "effective" setup when #tab-setup has content but #home does not.
                if (target === '#home' && window.SARGAM_DASHBOARD_URL) {
                    const routeTab = window.SARGAM_ACTIVE_NAV_TAB || '#home';
                    const setupPane = document.querySelector(
                        '#mainNavbarContent #tab-setup.tab-pane');
                    const homePane = document.querySelector('#mainNavbarContent #home.tab-pane');
                    const setupHasContent = !!(setupPane && setupPane.children.length > 0);
                    const homeHasContent = !!(homePane && homePane.children.length > 0);
                    const effectiveSetup = routeTab !== '#home' || (setupHasContent && !
                        homeHasContent);
                    if (effectiveSetup) {
                        e.preventDefault();
                        window.location.href = window.SARGAM_DASHBOARD_URL;
                        return;
                    }
                }

                e.preventDefault();
                showPane(target);
                history.replaceState(null, '', target);
                // Defer so #sidebarTabContent sync (other listeners) runs first
                setTimeout(function() {
                    activateDefaultSubmenuForPane(target);
                }, 0);
            });
        });

        // Determine initial tab.
        // Prefer server route tab first (source of truth),
        // then infer from sidebar links, then localStorage fallback.
        function inferTabFromSidebarByUrl() {
            const current = new URL(window.location.href);
            const currentPath = current.pathname.replace(/\/+$/, '');
            const currentQuery = current.search || '';
            const sidebarPanes = [{
                    pane: '#sidebar-home',
                    tab: '#home'
                },
                {
                    pane: '#sidebar-setup',
                    tab: '#tab-setup'
                },
                {
                    pane: '#sidebar-communications',
                    tab: '#tab-communications'
                },
                {
                    pane: '#sidebar-academics',
                    tab: '#tab-academics'
                },
                {
                    pane: '#sidebar-purchase-order',
                    tab: '#tab-material-management'
                }
            ];

        for (const item of sidebarPanes) {
            const links = document.querySelectorAll(`${item.pane} .sidebar-link[href]`);
            for (const link of links) {
                if (!link.href) continue;
                const target = new URL(link.href, window.location.origin);
                const targetPath = target.pathname.replace(/\/+$/, '');
                const targetQuery = target.search || '';
                if (targetPath === currentPath && targetQuery === currentQuery) {
                    return item.tab;
                }
            }
        }

            // GET filters add query string; sidebar links are usually path-only. Match path, prefer non-Home panes first.
            const pathMatchOrder = [...sidebarPanes].sort(function(a, b) {
                if (a.tab === '#home') return 1;
                if (b.tab === '#home') return -1;
                return 0;
            });
            for (const item of pathMatchOrder) {
                const links = document.querySelectorAll(`${item.pane} .sidebar-link[href]`);
                for (const link of links) {
                    if (!link.href) continue;
                    const target = new URL(link.href, window.location.origin);
                    const targetPath = target.pathname.replace(/\/+$/, '');
                    const targetQuery = target.search || '';
                    if (targetPath === currentPath && !targetQuery && currentQuery) {
                        return item.tab;
                    }
                }
            }

        return null;
    }

    const routeTab = window.SARGAM_ACTIVE_NAV_TAB || '#home';
    const savedTab = localStorage.getItem('activeMainTab');
    const inferredTab = inferTabFromSidebarByUrl();
    const tabExists = function (tabId) {
        return tabId && !!document.querySelector('#mainNavbar .nav-link[href="' + tabId + '"], .mobile-tabbar .nav-link[href="' + tabId + '"]');
    };
    const hasRouteTab = tabExists(routeTab);
    const hasSavedTab = tabExists(savedTab);
    const hasInferredTab = tabExists(inferredTab);
    let initial = '#home';

    if (hasRouteTab) {
        initial = routeTab;
        console.log('Initial tab from route:', initial);
    } else if (hasInferredTab) {
        initial = inferredTab;
        console.log('Initial tab from sidebar URL match:', initial);
    } else if (hasSavedTab) {
        initial = savedTab;
        console.log('Initial tab from storage fallback:', initial);
    } else {
        console.log('Initial tab fallback to home');
    }
    
    showPane(initial);

    // Apply default submenu/content for initial tab (after sidebar init)
    setTimeout(function() { activateDefaultSubmenuForPane(initial); }, 0);
});
</script>
<!-- 🌟 Header End -->
