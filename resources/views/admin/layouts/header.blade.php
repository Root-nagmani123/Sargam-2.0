<!-- 🌟 Header Start -->
<style>
/* --- Navbar Styling --- */
.navbar-nav .nav-link {
    color: #333;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link:focus {
    background-color: #f2f2f2;
    color: #000;
    outline: none;
}

.navbar-nav .nav-link.active {
    background-color: #B12923;
    color: #fbf8f8 !important;
    font-size: 16px !important;
    line-height: 24px;
    font-weight: 500 !important;
    padding: 20px !important;
    border-radius: 26px !important;
    Width: 100% !important;
    Height: 40px !important;
    text-align: center !important;
    justify-content: center !important;
    transition: all 0.3s ease-in-out;
    box-shadow: 3px 0 3px 0 rgba(232, 191, 189, 0.8);
}

.btn-link {
    text-decoration: none !important;
}

.btn-link:hover {
    opacity: 0.8;
}

@media (max-width: 991.98px) {
    .navbar-nav {
        border-radius: 0.5rem;
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }

    .navbar-nav .nav-link {
        width: 100%;
        border-radius: 0.5rem;
    }
}

/* --- Search Animation --- */
.search-wrapper {
    position: relative;
    display: inline-block;
}

.search-box {
    position: absolute;
    top: 50%;
    left: 120%;
    transform: translateY(-50%) scale(0.95);
    opacity: 0;
    display: none;
    min-width: 220px;
    transition: all 0.3s ease;
    z-index: 1050;
}

.search-box.show {
    display: block !important;
    opacity: 1;
    transform: translateY(-50%) scale(1);
}

.input-group-sm .form-control {
    border-radius: 50rem 0 0 50rem;
}

.input-group-sm .btn {
    border-radius: 0 50rem 50rem 0;
}

#mainNavbar {
    height: auto !important;
    overflow: visible !important;
}

#mainNavbar.collapse:not(.show),
#navbarNav.collapse:not(.show) {
    display: contents !important;
}

/* Notification Badge */
.notification-badge {
    font-size: 10px;
    padding: 2px 6px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-link {
    font-size: 14px;
    font-weight: 500;
}

.nav-link:not(.active):hover {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 999px;
}

.nav-link:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}
</style>

<!-- GIGW-Compliant Modern Header -->
<header class="topbar bg-white border-bottom" role="banner">
    <nav class="navbar container-fluid px-4 py-2" role="navigation" aria-label="Primary navigation">

        <!-- LEFT: LOGO -->
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/ashoka.webp') }}" alt="Sargam Icon" height="46">
            |
            <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="Sargam – LBSNAA" height="46">
        </div>

        <!-- CENTER: PILL NAVIGATION -->
        <div class="mx-auto">
            <ul class="nav align-items-center px-2 py-1" style="border-radius: 20px; height: 60px; background: #f2f2f2; 
                       border: 1px solid rgba(0, 0, 0, 0.05);" role="menubar" aria-label="Main navigation">

                <li class="nav-item" role="none">
                    <a class="nav-link px-3 py-1 text-dark" href="#home" role="menuitem">
                        Home
                    </a>
                </li>

                <li class="nav-item" role="none">
                    <a class="nav-link px-3 py-1 active text-white" href="#setup" role="menuitem" aria-current="page"
                        style="background:#b91c1c; border-radius:999px;">
                        Setup
                    </a>
                </li>

                <li class="nav-item" role="none">
                    <a class="nav-link px-3 py-1 text-dark" href="#communications" role="menuitem">
                        Communications
                    </a>
                </li>

                <li class="nav-item" role="none">
                    <a class="nav-link px-3 py-1 text-dark" href="#academics" role="menuitem">
                        Academics
                    </a>
                </li>

                <li class="nav-item" role="none">
                    <a class="nav-link px-3 py-1 text-dark" href="#material" role="menuitem">
                        Material Management
                    </a>
                </li>

                <li class="nav-item dropdown" role="none">
                    <a class="nav-link px-3 py-1 text-dark dropdown-toggle" href="#" id="financeMenu" role="menuitem"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Financial
                    </a>
                    <ul class="dropdown-menu" role="menu">
                        <li><a class="dropdown-item" href="#">Budget</a></li>
                        <li><a class="dropdown-item" href="#">Accounts</a></li>
                    </ul>
                </li>

                <li class="nav-item" role="none">
                    <button class="btn p-1" aria-label="Search">
                        <span class="material-icons" style="font-size:20px;">
                            search
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        <!-- RIGHT: LOGOUT + LAST LOGIN -->
        <div class="d-flex align-items-center gap-4">

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn p-0 border-0 bg-transparent" aria-label="Logout">
                    <span class="material-icons" style="font-size:22px;">
                        logout
                    </span>
                </button>
            </form>

            @php
            $lastLogin = Auth::user()->last_login;
            $dt = $lastLogin ? \Carbon\Carbon::parse($lastLogin) : null;
            @endphp

            <div class="text-end">
                <div class="text-muted" style="font-size:11px;">
                    Last login
                </div>
                <time datetime="{{ $dt?->toIso8601String() }}" class="fw-medium text-dark" style="font-size:12px;">
                    {{ $dt?->format('d-m-Y H:i:s') ?? 'Never' }}
                </time>
            </div>
        </div>
    </nav>
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

    // Determine initial tab based on current route
    const routeTab = detectRouteTab();
    const savedTab = localStorage.getItem('activeMainTab') || routeTab;
    const initial = savedTab || '#home';
    console.log('Initial tab:', initial);
    showPane(initial);
});
</script>
<!-- 🌟 Header End -->