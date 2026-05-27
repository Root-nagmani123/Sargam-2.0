{{-- $activeNavTab is set in admin.layouts.master before this include --}}
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
                        <div class="header-nav-scroll flex-grow-1" tabindex="0">
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
                        <button type="button" class="header-nav-scroll-btn" aria-label="Scroll navigation" title="More">
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
                <a href="javascript:void(0)" class="link-primary text-decoration-underline small notification-view-all-link">
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

            <style>
                .notification-btn {
                    transition: background-color 0.2s ease, transform 0.2s ease;
                }
                .notification-btn:hover {
                    background-color: var(--bs-light);
                    transform: translateY(-1px);
                }
                .notification-badge {
                    font-size: 10px;
                    padding: 4px 6px;
                }
                .notification-dropdown {
                    width: 350px;
                    max-height: 480px;
                    overflow: hidden;
                }
                .notification-dropdown-header {
                    position: sticky;
                    top: 0;
                    z-index: 2;
                }
                .notification-list {
                    max-height: 340px;
                    overflow-y: auto;
                }
                .notification-list-item:last-child {
                    margin-bottom: 0 !important;
                }
                .notification-item {
                    color: inherit;
                    transition: box-shadow 0.15s ease, border-color 0.15s ease;
                }
                .notification-item:hover {
                    border-color: var(--bs-primary-border-subtle) !important;
                    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
                }
                .notification-item-unread {
                    border-color: var(--bs-primary-border-subtle) !important;
                    background-color: rgba(var(--bs-primary-rgb), 0.03);
                }
                .notification-item-body {
                    min-width: 0;
                }
                .notification-item-title {
                    font-size: 0.9rem;
                    line-height: 1.3;
                }
                .notification-item-message {
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                    line-height: 1.45;
                }
                .notification-item-time {
                    font-size: 0.75rem;
                }
                .notification-dropdown-footer {
                    position: sticky;
                    bottom: 0;
                    z-index: 2;
                }
                .notification-view-all-link {
                    font-weight: 500;
                }
                /* Blinking "New" tag for unread notifications */
                .notification-new-tag {
                    font-size: 0.625rem;
                    font-weight: 600;
                    letter-spacing: 0.02em;
                    padding: 0.2em 0.5em;
                    flex-shrink: 0;
                    animation: notification-blink 1.2s ease-in-out infinite;
                }
                @keyframes notification-blink {
                    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(var(--bs-danger-rgb), 0.4); }
                    50% { opacity: 0.85; box-shadow: 0 0 0 4px rgba(var(--bs-danger-rgb), 0); }
                }
                .notification-empty-state .material-icons {
                    font-size: 2.25rem;
                }
                /* Mobile offcanvas notifications */
                .notification-mobile-list {
                    padding: 0.75rem 1rem 1rem;
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
    background: #071a3b;
    min-height: 38px;
    border: none;
}
.header-top-inner {
    min-height: 38px;
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
}
.header-govt-wrap {
    min-width: 0;
}
.header-flag-wrap {
    width: 24px;
    height: 14px;
    overflow: hidden;
    border-radius: 3px !important;
}
.header-flag-icon {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.header-govt-text {
    font-size: 0.765rem;
    font-weight: 500;
    letter-spacing: 0;
    opacity: 0.92;
    line-height: 1.1;
}
.header-utility-nav .header-utility-sep {
    width: 1px;
    height: 13px;
    background: rgba(255,255,255,0.34);
    margin: 0 4px;
    display: inline-block;
}
.header-utility-link,
.header-font-btn {
    text-decoration: none !important;
    font-size: 0.765rem;
    font-weight: 500;
    opacity: 0.92;
    padding-left: 0.45rem !important;
    padding-right: 0.45rem !important;
    transition: color 0.2s ease, opacity 0.2s ease;
}
.header-utility-link:hover,
.header-font-btn:hover {
    color: #ffffff !important;
    opacity: 1;
}
.header-lang-dropdown {
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 0;
}
.header-globe-icon {
    font-size: 11px !important;
    color: #fff !important;
    opacity: 0.9;
}
.header-lang-select {
    background: transparent !important;
    border: none !important;
    color: #fff !important;
    font-size: 0.765rem;
    line-height: 1.1;
    padding: 0 2px;
    min-width: 62px;
    box-shadow: none !important;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}
.header-lang-select option { background: #122442; color: #fff; }
.header-lang-caret {
    color: #fff;
    opacity: 0.78;
    font-size: 9px;
}

@media (min-width: 992px) and (max-width: 1199.98px) {
    .header-govt-text,
    .header-utility-link,
    .header-font-btn,
    .header-lang-select {
        font-size: 0.72rem;
    }

    .header-utility-nav .header-utility-sep {
        margin: 0 3px;
    }
}

/* Main nav bar - white background */
.with-vertical .navbar,
.header-main-navbar {
    background: #fff !important;
    min-height: 72px;
    width: 100%;
}
.header-main-navbar {
    align-items: center;
    padding: 0 0.75rem;
}
@media (min-width: 992px) {
    .header-main-navbar {
        flex-wrap: nowrap;
        padding: 0 1.25rem;
        gap: 0.75rem;
    }
}
.header-brand-block { min-width: 0; }
.header-brand { gap: 0 !important; padding-right: 0.25rem; }
.header-brand-divider {
    width: 1px;
    height: 38px;
    background: #d1d5db;
    margin: 0 0.75rem;
    flex-shrink: 0;
}
.header-logo-emblem { height: 44px; width: auto; object-fit: contain; }
.header-logo { height: 34px; width: auto; object-fit: contain; }
@media (min-width: 992px) {
    .header-logo-emblem { height: 48px !important; }
    .header-logo { height: 36px !important; margin-left: 10px !important; }
    .header-brand-divider { height: 42px; margin: 0 0.85rem; }
}
.header-app-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #004a93;
}

/* Centered scrollable nav pill */
.header-nav-center {
    min-width: 0;
    max-width: 100%;
}
.header-nav-scroll-wrap {
    display: flex;
    align-items: center;
    background: #e8eaee;
    padding: 4px 4px 4px 6px;
    width: min(100%, 760px);
    max-width: 100%;
}
.header-nav-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
    min-width: 0;
}
.header-nav-scroll::-webkit-scrollbar { display: none; }
.header-main-nav {
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 2px;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    min-height: auto;
    padding: 0;
}
.header-nav-link {
    color: #4b5563 !important;
    text-decoration: none !important;
    font-size: 0.8125rem;
    font-weight: 500;
    border: none !important;
    padding: 7px 15px !important;
    white-space: nowrap;
    line-height: 1.25;
    transition: color 0.15s ease, background-color 0.15s ease;
}
.header-nav-link:hover {
    color: #1f2937 !important;
    background-color: rgba(255, 255, 255, 0.55);
}
.header-nav-link.active {
    color: #fff !important;
    background: #0a4a8c !important;
    box-shadow: none;
}
.header-nav-scroll-btn {
    flex-shrink: 0;
    border: none;
    background: transparent;
    color: #6b7280;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin-left: 2px;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}
.header-nav-scroll-btn .material-icons {
    font-size: 20px !important;
}
.header-nav-scroll-btn:hover {
    background: rgba(255, 255, 255, 0.65);
    color: #374151;
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
.header-right-actions { margin-right: 0.4rem; }
.header-icon-sm { font-size: 24px !important; }
.header-logout-icon { font-size: 22px !important; }
.header-last-login { font-size: 0.8125rem; }

.notification-btn {
    width: 42px;
    height: 42px;
    border-radius: 8px !important;
    border: 1px solid #9cb4cc !important;
    background: #fff !important;
    color: #0f3f78 !important;
    box-shadow: none !important;
}

.notification-btn .material-icons {
    font-size: 21px !important;
}

.header-notification-bell {
    display: inline-block;
    transform-origin: top center;
}

.header-notification-bell--ring {
    animation: header-notification-bell-ring 1.25s ease-in-out infinite;
}

@keyframes header-notification-bell-ring {
    0%, 100% { transform: rotate(0); }
    8% { transform: rotate(16deg); }
    16% { transform: rotate(-14deg); }
    24% { transform: rotate(12deg); }
    32% { transform: rotate(-10deg); }
    40% { transform: rotate(8deg); }
    48% { transform: rotate(-6deg); }
    56% { transform: rotate(4deg); }
    64% { transform: rotate(-2deg); }
    72% { transform: rotate(0); }
}

@media (prefers-reduced-motion: reduce) {
    .header-notification-bell--ring {
        animation: none;
    }
}

.header-profile-chip:hover {
    opacity: 0.9;
}

.header-user-avatar {
    width: 44px;
    height: 44px;
    line-height: 0;
}

.header-user-avatar-img {
    width: 44px;
    height: 44px;
}

.header-user-avatar-fallback {
    width: 44px;
    height: 44px;
    font-size: 0.875rem;
}

.header-profile-name,
.header-profile-role {
    max-width: 200px;
}

.header-profile-chevron {
    font-size: 22px !important;
    width: 22px;
    height: 22px;
    margin-left: 0.15rem;
    flex-shrink: 0;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
}

.header-profile-dropdown-wrap:hover .header-profile-chevron,
.header-profile-chip[aria-expanded="true"] .header-profile-chevron {
    opacity: 1;
    visibility: visible;
}

.header-profile-chip[aria-expanded="true"] .header-profile-chevron {
    transform: rotate(180deg);
}

.header-profile-dropdown {
    min-width: 280px;
    margin-top: 0.5rem !important;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12) !important;
}

.header-profile-dropdown-header {
    background-color: #e8f2fc;
}

.header-profile-menu-list .dropdown-item {
    font-size: 0.9rem;
    font-weight: 400;
    color: #374151;
}

.header-profile-menu-list .dropdown-item:hover,
.header-profile-menu-list .dropdown-item:focus {
    background-color: #f3f4f6;
    color: #111827;
}

.header-profile-menu-icon {
    font-size: 22px !important;
    color: #6b7280;
    flex-shrink: 0;
}

.header-profile-logout-item:hover,
.header-profile-logout-item:focus {
    background-color: #FFD5DD !important;
    color: #F0143E !important;
}

.header-profile-logout-item:hover .header-profile-menu-icon,
.header-profile-logout-item:focus .header-profile-menu-icon {
    color: #F0143E !important;
}

.header-logout-icon-btn {
    width: 36px;
    height: 36px;
    color: #5b6678 !important;
}

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

                .header-profile-chip {
                    display: none !important;
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

                /* Modern dual sidebar sits below header */
                aside.side-mini-panel.sidebar-google-style {
                    top: var(--sargam-header-offset, 122px) !important;
                    height: calc(100vh - var(--sargam-header-offset, 122px)) !important;
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

            /* Medium desktop fallback (e.g., 1280x1024 at 100% zoom):
               keep content shifted so fixed sidebar doesn't overlap mid section. */
            @media (min-width: 992px) and (max-width: 1299.98px) {
                html[data-layout="vertical"] body[data-sidebartype="full"] .page-wrapper {
                    margin-left: calc(80px + 240px) !important;
                    width: calc(100% - 320px) !important;
                }

                html[data-layout="vertical"] body[data-sidebartype="mini-sidebar"] .page-wrapper {
                    margin-left: 80px !important;
                    width: calc(100% - 80px) !important;
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
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
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
                    .catch(function (error) {
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
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            refreshNotificationPanels();
                        }
                    })
                    .catch(function (error) {
                        console.error('[Notification][AllRead] Exception', error);
                    });
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

    document.querySelectorAll('.header-nav-scroll-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var scrollEl = btn.closest('.header-nav-scroll-wrap')?.querySelector('.header-nav-scroll');
            if (scrollEl) {
                scrollEl.scrollBy({ left: 140, behavior: 'smooth' });
            }
        });
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
                itemEl = pane.querySelector('#' + (typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(storedId) : storedId.replace(/([#.;?+*^$[\]\\(){}|\-])/g, '\\$1')));
            } catch (err) {
                itemEl = null;
            }
        }
        if (!itemEl) {
            itemEl = pane.querySelector('.mini-nav .mini-nav-item[id]') || pane.querySelector('.mini-nav .mini-nav-item');
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
                const setupPane = document.querySelector('#mainNavbarContent #tab-setup.tab-pane');
                const homePane = document.querySelector('#mainNavbarContent #home.tab-pane');
                const setupHasContent = !!(setupPane && setupPane.children.length > 0);
                const homeHasContent = !!(homePane && homePane.children.length > 0);
                const effectiveSetup = routeTab !== '#home' || (setupHasContent && !homeHasContent);
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
            setTimeout(function() { activateDefaultSubmenuForPane(target); }, 0);
        });
    });

    // Determine initial tab.
    // Prefer server route tab first (source of truth),
    // then infer from sidebar links, then localStorage fallback.
    function inferTabFromSidebarByUrl() {
        const current = new URL(window.location.href);
        const currentPath = current.pathname.replace(/\/+$/, '');
        const currentQuery = current.search || '';
        const sidebarPanes = [
            { pane: '#sidebar-home', tab: '#home' },
            { pane: '#sidebar-setup', tab: '#tab-setup' },
            { pane: '#sidebar-communications', tab: '#tab-communications' },
            { pane: '#sidebar-academics', tab: '#tab-academics' },
            { pane: '#sidebar-purchase-order', tab: '#tab-material-management' }
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
        const pathMatchOrder = [...sidebarPanes].sort(function (a, b) {
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