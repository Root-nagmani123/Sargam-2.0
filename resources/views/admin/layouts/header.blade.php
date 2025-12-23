<header class="topbar">
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
                    <!-- Enhanced Navigation Container -->
                    <div class="nav-container position-relative">
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

                                    @if(hasRole('Admin') || hasRole('Training'))
                                    <span>Setup</span>
                                    @elseif(hasRole('Internal Faculty') || hasRole('Guest Faculty') ||
                                    hasRole('Student-OT'))
                                    <span>Academics</span>
                                    @else
                                    <span>Setup</span>
                                    @endif

                                </a>
                            </li>


                            <!-- Communications -->
                            <li class="nav-item" role="none">
                                <a href="#tab-communications"
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
                                <a href="#tab-material-management"
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
                                $notifications = notification()->getNotifications(Auth::user()->user_id ?? 0, 10, true);
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

            <script>
            document.addEventListener('DOMContentLoaded', function() {
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
                const searchTrigger = document.querySelector('.search-trigger');
                if (searchTrigger) {
                    searchTrigger.addEventListener('click', function() {
                        // Open search modal or expand search bar
                        this.setAttribute('aria-expanded', 'true');
                        // Add your search functionality here
                        console.log('Search triggered');
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
    const tabLinks = document.querySelectorAll('#mainNavbar .nav-link[data-bs-toggle="tab"]');
    const panes = document.querySelectorAll('#mainNavbarContent .tab-pane');

    function showPane(targetId) {
        panes.forEach(p => {
            if ('#' + p.id === targetId) {
                p.classList.add('show', 'active');
            } else {
                p.classList.remove('show', 'active');
            }
        });
        tabLinks.forEach(l => {
            if (l.getAttribute('href') === targetId) {
                l.classList.add('active');
            } else {
                l.classList.remove('active');
            }
        });
        // Save the active tab to localStorage
        localStorage.setItem('activeMainTab', targetId);
    }

    function detectRouteTab() {
        const currentPath = window.location.pathname;
        console.log('Current path:', currentPath);

        // Dashboard routes - HIGHEST PRIORITY
        if (currentPath === '/dashboard' || 
            currentPath.includes('/admin/dashboard') ||
            currentPath === '/' || 
            currentPath === '/admin') {
            console.log('Route matched: Dashboard/Home tab');
            return '#home';
        }

        // Setup routes - CHECK FIRST (more specific)
        if (currentPath.includes('/admin/setup') ||
            currentPath.includes('/admin/caste') ||
            currentPath.includes('/admin/category') ||
            currentPath.includes('/admin/religion') ||
            currentPath.includes('/admin/state') ||
            currentPath.includes('/admin/district') ||
            currentPath.includes('/admin/building') ||
            currentPath.includes('/admin/hostel') ||
            currentPath.includes('/admin/designation') ||
            currentPath.includes('/admin/department') ||
            currentPath.includes('/admin/stream') ||
            currentPath.includes('/admin/section') ||
            currentPath.includes('/admin/subject') ||
            currentPath.includes('/admin/venueMaster') ||
            currentPath.includes('/admin/building-floor-room')) {
            console.log('Route matched: Setup tab');
            return '#tab-setup';
        }

        // Academics routes
        if (currentPath.includes('/admin/faculty') ||
            currentPath.includes('/admin/academics') ||
            currentPath.includes('/admin/course') ||
            currentPath.includes('/admin/batch')) {
            console.log('Route matched: Academics tab');
            return '#tab-academics';
        }

        // Communications routes
        if (currentPath.includes('/admin/communications') ||
            currentPath.includes('/admin/notices')) {
            console.log('Route matched: Communications tab');
            return '#tab-communications';
        }

        // Material Management routes
        if (currentPath.includes('/admin/material') ||
            currentPath.includes('/admin/inventory')) {
            console.log('Route matched: Material Management tab');
            return '#tab-material-management';
        }

        // Default to home
        console.log('Route matched: Home tab (default)');
        return '#home';
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            showPane(target);
            history.replaceState(null, '', target);
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
});
</script>
<!-- 🌟 Header End -->