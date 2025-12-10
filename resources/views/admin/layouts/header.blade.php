<!-- 🌟 Header Start -->
<style>
/* ========================================
   GIGW Compliant Header & Tab Styling
   ======================================== */

/* --- Navbar Base Styling --- */
.navbar-nav .nav-link {
    color: #1f2937;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
    position: relative;
    border-radius: 8px;
    padding: 10px 16px !important;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    min-height: 40px;
}

.navbar-nav .nav-link:hover {
    background-color: #e8eef7;
    color: #004a93;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 74, 147, 0.1);
}

.navbar-nav .nav-link:focus {
    outline: 3px solid #004a93;
    outline-offset: 2px;
    background-color: #e8eef7;
}

/* --- Active Tab Styling (GIGW Compliance) --- */
.navbar-nav .nav-link.active {
    background: linear-gradient(135deg, #af2910 0%, #af2910 100%);
    color: #ffffff !important;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 74, 147, 0.25);
    position: relative;
}

.navbar-nav .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 4px;
    background: #af2910;
    border-radius: 2px 2px 0 0;
}

/* Icon styling */
.navbar-nav .nav-link i {
    font-size: 20px;
    transition: transform 0.3s ease;
}

.navbar-nav .nav-link:hover i {
    transform: scale(1.1);
}

.navbar-nav .nav-link.active i {
    transform: scale(1.15);
}

/* --- Navigation Container --- */
.nav-container {
    background: linear-gradient(180deg, #f8f9fa 0%, #f0f3f7 100%);
    border-radius: 12px;
    border: 1px solid #d1d5db;
    padding: 6px 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    min-height: 56px;
    display: flex;
    align-items: center;
}

.nav-container .navbar-nav {
    height: auto !important;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.nav-container .navbar-nav::-webkit-scrollbar {
    height: 4px;
}

.nav-container .navbar-nav::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.nav-container .navbar-nav::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.nav-container .navbar-nav::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Dropdown Toggle Custom */
.dropdown-toggle-custom::after {
    display: none !important;
}

.dropdown-toggle-custom .dropdown-arrow {
    transition: transform 0.25s ease;
    font-size: 18px;
}

.show > .dropdown-toggle-custom .dropdown-arrow {
    transform: rotate(180deg);
}

/* --- Dropdown Menu Enhancement --- */
.dropdown-menu {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    padding: 8px !important;
    margin-top: 8px !important;
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-item {
    color: #1f2937;
    padding: 10px 12px !important;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.dropdown-item:hover {
    background-color: #f0f3f7;
    color: #004a93;
    transform: translateX(4px);
}

.dropdown-item:focus {
    background-color: #e8eef7;
    outline: 2px solid #004a93;
    outline-offset: -2px;
}

/* --- Search Button --- */
.search-trigger {
    background-color: #f3f4f6;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    width: 40px !important;
    height: 40px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 !important;
}

.search-trigger:hover {
    background-color: #004a93;
    color: white;
    border-color: #004a93;
    box-shadow: 0 4px 12px rgba(0, 74, 147, 0.2);
}

.search-trigger:focus {
    outline: 3px solid #004a93;
    outline-offset: 2px;
}

/* --- Search Box Animation --- */
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
    min-width: 260px;
    transition: all 0.3s ease;
    z-index: 1050;
}

.search-box.show {
    display: block !important;
    opacity: 1;
    transform: translateY(-50%) scale(1);
}

.input-group-sm .form-control {
    border-radius: 8px 0 0 8px;
    border: 1px solid #e5e7eb;
    padding: 8px 12px;
    font-size: 0.9rem;
}

.input-group-sm .form-control:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1);
    outline: none;
}

.input-group-sm .btn {
    border-radius: 0 8px 8px 0;
    background-color: #004a93;
    border-color: #004a93;
    color: white;
}

.input-group-sm .btn:hover {
    background-color: #003366;
    border-color: #003366;
}

/* Navbar overflow handling */
#mainNavbar {
    height: auto !important;
    overflow: visible !important;
}

#mainNavbar.collapse:not(.show),
#navbarNav.collapse:not(.show) {
    display: contents !important;
}

/* --- Right Side Actions --- */
.navbar-action-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.2s ease;
    gap: 8px;
}

.navbar-action-item:hover {
    background-color: #f0f3f7;
}

.navbar-action-item:focus-within {
    outline: 2px solid #004a93;
    outline-offset: 2px;
}

/* --- Button Styling --- */
.btn-outline-light {
    color: #4b5563;
    border-color: transparent;
    transition: all 0.2s ease;
}

.btn-outline-light:hover {
    background-color: #f0f3f7;
    color: #004a93;
    border-color: #d1d5db;
    transform: translateY(-2px);
}

.btn-outline-light:focus {
    outline: 2px solid #004a93;
    outline-offset: 2px;
}

/* --- Text and Labels --- */
.text-muted {
    color: #6b7280 !important;
    font-size: 0.85rem;
}

/* --- Responsive Design --- */
@media (max-width: 991.98px) {
    .navbar-nav {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
        gap: 4px;
    }

    .navbar-nav .nav-link {
        width: 100%;
        border-radius: 6px;
    }

    .nav-container {
        border-radius: 8px;
        padding: 4px;
    }

    .navbar-nav .nav-link.active::after {
        bottom: 2px;
        left: 8px;
        width: 4px;
        height: 24px;
    }

    .d-flex.ms-auto {
        gap: 8px !important;
    }
}

@media (max-width: 576px) {
    .navbar-nav .nav-link {
        padding: 8px 12px !important;
        font-size: 0.9rem;
    }

    .nav-container {
        padding: 4px;
    }

    .search-box {
        min-width: 200px;
    }

    .dropdown-menu {
        min-width: 150px !important;
    }
}

/* --- Accessibility & Focus States --- */
.nav-link:focus-visible,
.btn-outline-light:focus-visible,
.search-trigger:focus-visible {
    outline: 3px solid #004a93;
    outline-offset: 2px;
}

/* --- Smooth Transitions --- */
* {
    transition-property: background-color, border-color, color, box-shadow, transform;
    transition-duration: 0.2s;
    transition-timing-function: ease-in-out;
}

/* --- Hover Lift Effect --- */
.hover-lift {
    transition: all 0.2s ease-in-out;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>

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

            <div class="d-block d-lg-none py-9 py-xl-0">
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
                        <ul class="navbar-nav gap-2 align-items-center" role="menubar"
                            aria-label="Main navigation tabs">

                            <!-- Home Tab -->
                            <li class="nav-item" role="none">
                                <a href="#home"
                                    class="nav-link active rounded-lg px-3 py-2 d-flex align-items-center gap-2 fw-500"
                                    data-bs-toggle="tab" role="tab" aria-selected="true" aria-controls="home-panel"
                                    id="home-tab"
                                    title="Home - Dashboard and overview">
                                    <i class="material-icons material-symbols-rounded">home</i>
                                    <span class="d-none d-sm-inline">Home</span>
                                </a>
                            </li>

                            <!-- Setup Tab -->
                            <li class="nav-item" role="none">
                                <a href="#tab-setup"
                                    class="nav-link rounded-lg px-3 py-2 d-flex align-items-center gap-2 fw-500"
                                    data-bs-toggle="tab" role="tab" aria-selected="false" aria-controls="setup-panel"
                                    id="setup-tab"
                                    title="Setup - Configure system">
                                    <i class="material-icons material-symbols-rounded">settings</i>
                                    <span class="d-none d-sm-inline">
                                        @if(hasRole('Admin') || hasRole('Training'))
                                            Setup
                                        @elseif(hasRole('Internal Faculty')  || hasRole('Guest Faculty') || hasRole('Student-OT'))
                                            Academics
                                        @else
                                            Setup
                                        @endif
                                    </span>
                                </a>
                            </li>

                            <!-- Communications Tab -->
                            <li class="nav-item" role="none">
                                <a href="#tab-communications"
                                    class="nav-link rounded-lg px-3 py-2 d-flex align-items-center gap-2 fw-500"
                                    data-bs-toggle="tab" role="tab" aria-selected="false"
                                    aria-controls="communications-panel" id="communications-tab"
                                    title="Communications - Send messages and notices">
                                    <i class="material-icons material-symbols-rounded">mail</i>
                                    <span class="d-none d-sm-inline">Communications</span>
                                </a>
                            </li>

                            <!-- Material Management Tab -->
                            <li class="nav-item" role="none">
                                <a href="#tab-material-management"
                                    class="nav-link rounded-lg px-3 py-2 d-flex align-items-center gap-2 fw-500"
                                    data-bs-toggle="tab" role="tab" aria-selected="false"
                                    aria-controls="material-management-panel" id="material-management-tab"
                                    title="Material Management - Manage resources">
                                    <i class="material-icons material-symbols-rounded">inventory</i>
                                    <span class="d-none d-sm-inline">Materials</span>
                                </a>
                            </li>

                            <!-- Financial Dropdown - Enhanced with GIGW compliance -->
                            <li class="nav-item dropdown" role="none">
                                <a class="nav-link rounded-lg px-3 py-2 d-flex align-items-center gap-2 fw-500 dropdown-toggle-custom"
                                    href="#" id="financialDropdown" role="menuitem" aria-haspopup="true"
                                    aria-expanded="false" data-bs-toggle="dropdown"
                                    title="Financial - Budget and accounts">
                                    <i class="material-icons material-symbols-rounded">account_balance_wallet</i>
                                    <span class="d-none d-sm-inline">Financial</span>
                                    <i class="material-icons material-symbols-rounded fs-6 dropdown-arrow transition-all"
                                        aria-hidden="true">expand_more</i>
                                </a>

                                <ul class="dropdown-menu shadow-lg border-0 rounded-lg p-2 mt-2"
                                    style="min-width: 200px; border: 1px solid rgba(0, 0, 0, 0.08);"
                                    aria-labelledby="financialDropdown" role="menu">
                                    <li role="none">
                                        <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg fw-500"
                                            href="#" role="menuitem"
                                            title="Budget - View and manage budget">
                                            <i class="material-icons material-symbols-rounded" style="font-size: 18px;">account_balance</i>
                                            <span>Budget</span>
                                        </a>
                                    </li>
                                    <li role="none">
                                        <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg fw-500"
                                            href="#" role="menuitem"
                                            title="Accounts - View financial records">
                                            <i class="material-icons material-symbols-rounded" style="font-size: 18px;">receipt_long</i>
                                            <span>Accounts</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Search with Enhanced UI -->
                            <li class="nav-item ms-auto ms-md-2" role="none">
                                <button class="nav-link search-trigger rounded-lg p-2"
                                    aria-label="Open search" aria-expanded="false"
                                    aria-controls="searchModal"
                                    title="Search across system">
                                    <i class="material-icons material-symbols-rounded text-dark"
                                        style="font-size: 20px;" aria-hidden="true">search</i>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Right Side Actions - Enhanced with GIGW compliance -->
                <div class="d-flex align-items-center ms-auto gap-3" style="margin-right: 20px;">
                    <!-- Logout Button - Enhanced -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 d-inline" role="form">
                        @csrf
                        <button type="submit"
                            class="btn btn-outline-light border-0 p-2 rounded-lg hover-lift position-relative"
                            aria-label="Sign out from system" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            title="Sign Out"
                            style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="material-icons material-symbols-rounded" style="font-size: 22px; color: #4b5563;"
                                aria-hidden="true">logout</i>
                            <span class="tooltip-text visually-hidden">Sign out from system</span>
                        </button>
                    </form>

                    <!-- Last Login - Enhanced with better typography -->
                    <div class="d-flex flex-column align-items-end navbar-action-item">
                        <div class="text-muted small d-flex align-items-center gap-1"
                            style="font-size: 0.8rem; line-height: 1.2;">
                            <i class="material-icons material-symbols-rounded" style="font-size: 14px;"
                                aria-hidden="true">schedule</i>
                            <span class="fw-600">Last login:</span>
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