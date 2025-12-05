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
                        <ul class="navbar-nav px-4 py-2 gap-2 align-items-center" style="border-radius: 20px; height: 60px; background: #f2f2f2; 
                       border: 1px solid rgba(0, 0, 0, 0.05);" role="menubar"
                            aria-label="Main navigation">

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
                             @if(hasRole('Admin') || hasRole('Training') )
                            <li class="nav-item" role="none">
                                <a href="#tab-setup"
                                    class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 hover-lift"
                                    data-bs-toggle="tab" role="tab" aria-selected="false" aria-controls="setup-panel"
                                    id="setup-tab">
                                    <span>Setup</span>  | 
                                    <span>Academics</span>
                                </a>
                            </li>
                            @endif

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
                             @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Student-OT')  || hasRole('Admin'))
                            <li class="nav-item" role="none">
                                <a href="#tab-academics"
                                    class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 hover-lift"
                                    data-bs-toggle="tab" role="tab" aria-selected="false"
                                    aria-controls="academics-panel" id="academics-tab">
                                    <span>Academics</span>
                                </a>
                            </li>
                            @endif

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
                <div class="d-flex align-items-center ms-auto gap-4" style="margin-right: 56px;">
                    <!-- Logout Button - Enhanced -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 d-inline" role="form">
                        @csrf
                        <button type="submit"
                            class="btn btn-outline-light border-0 p-2 rounded-circle hover-lift position-relative"
                            aria-label="Sign out from system" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            title="Sign Out">
                            <i class="material-icons material-symbols-rounded" style="font-size: 22px; color: #475569;"
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
                        <time id="myTime" datetime="2025-05-14T13:56:02" class="text-dark fw-semibold"
                            style="font-size: 13px; line-height: 16px;" aria-live="polite">
                            14 May 2025, 13:56
                        </time>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Update time format for better UX
                const timeElement = document.getElementById('myTime');
                if (timeElement) {
                    const date = new Date(timeElement.getAttribute('datetime'));
                    const options = {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    };
                    timeElement.textContent = date.toLocaleDateString('en-US', options);
                }

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