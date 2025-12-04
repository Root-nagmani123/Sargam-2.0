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
            <ul class="navbar-nav shadow-lg px-4 py-2 gap-2 align-items-center"
                style="border-radius: 40px; height: 60px; background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%); 
                       border: 1px solid rgba(0, 0, 0, 0.05); backdrop-filter: blur(10px);"
                role="menubar" aria-label="Main navigation">
                
                <!-- Home -->
                <li class="nav-item" role="none">
                    <a href="#home" 
                       class="nav-link active rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2"
                       data-bs-toggle="tab" 
                       role="tab"
                       aria-selected="true"
                       aria-controls="home-panel"
                       id="home-tab">
                        <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">home</i>
                        <span>Home</span>
                    </a>
                </li>

                <!-- Communications -->
                <li class="nav-item" role="none">
                    <a href="#tab-communications" 
                       class="nav-link rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2 hover-lift"
                       data-bs-toggle="tab" 
                       role="tab"
                       aria-selected="false"
                       aria-controls="communications-panel"
                       id="communications-tab">
                        <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">chat</i>
                        <span>Communications</span>
                    </a>
                </li>

                <!-- Academics -->
                <li class="nav-item" role="none">
                    <a href="#tab-academics" 
                       class="nav-link rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2 hover-lift"
                       data-bs-toggle="tab" 
                       role="tab"
                       aria-selected="false"
                       aria-controls="academics-panel"
                       id="academics-tab">
                        <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">school</i>
                        <span>Academics</span>
                    </a>
                </li>

                <!-- Material Management -->
                <li class="nav-item" role="none">
                    <a href="#tab-material-management" 
                       class="nav-link rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2 hover-lift"
                       data-bs-toggle="tab" 
                       role="tab"
                       aria-selected="false"
                       aria-controls="material-management-panel"
                       id="material-management-tab">
                        <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">inventory</i>
                        <span>Material Management</span>
                    </a>
                </li>

                <!-- Financial Dropdown - Enhanced -->
                <li class="nav-item dropdown" role="none">
                    <a class="nav-link rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2 hover-lift dropdown-toggle-custom"
                       href="#" 
                       id="financialDropdown" 
                       role="menuitem"
                       aria-haspopup="true"
                       aria-expanded="false"
                       data-bs-toggle="dropdown">
                        <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">payments</i>
                        <span>Financial</span>
                        <i class="material-icons material-symbols-rounded fs-6 dropdown-arrow transition-all" 
                           aria-hidden="true">expand_more</i>
                    </a>

                    <ul class="dropdown-menu shadow-lg border-0 rounded-xl p-2 mt-1" 
                        style="min-width: 180px; border: 1px solid rgba(0, 0, 0, 0.08);"
                        aria-labelledby="financialDropdown"
                        role="menu">
                        <li role="none">
                            <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg hover-lift" 
                               href="#"
                               role="menuitem">
                                <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">account_balance</i>
                                <span>Budget</span>
                            </a>
                        </li>
                        <li role="none">
                            <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2 rounded-lg hover-lift" 
                               href="#"
                               role="menuitem">
                                <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">receipt_long</i>
                                <span>Accounts</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Search with Enhanced UI -->
                <li class="nav-item" role="none">
                    <button class="nav-link rounded-circle px-2 py-2 search-trigger hover-lift"
                            style="width: 40px; height: 40px;"
                            aria-label="Open search"
                            aria-expanded="false"
                            aria-controls="searchModal">
                        <i class="material-icons material-symbols-rounded text-dark" 
                           style="font-size: 20px;" 
                           aria-hidden="true">search</i>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Right Side Actions - Enhanced -->
    <div class="d-flex align-items-center ms-auto gap-4" style="margin-right: 56px;">
          <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 30px;">notifications</i>
        <!-- Logout Button - Enhanced -->
        <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 d-inline" role="form">
            @csrf
            <button type="submit" 
                    class="btn btn-outline-light border-0 p-2 rounded-circle hover-lift position-relative"
                    aria-label="Sign out from system"
                    data-bs-toggle="tooltip"
                    data-bs-placement="bottom"
                    title="Sign Out">
                <i class="material-icons material-symbols-rounded"
                   style="font-size: 22px; color: #475569;"
                   aria-hidden="true">logout</i>
                <span class="tooltip-text visually-hidden">Sign out from system</span>
            </button>
        </form>

        <!-- Last Login - Enhanced -->
        <div class="d-flex flex-column align-items-end">
            <div class="text-muted small d-flex align-items-center gap-1" style="font-size: 11px; line-height: 14px;">
                <i class="material-icons material-symbols-rounded" 
                   style="font-size: 14px;"
                   aria-hidden="true">schedule</i>
                <span class="fw-medium">Last login:</span>
            </div>
            <time id="myTime" 
                  datetime="2025-05-14T13:56:02"
                  class="text-dark fw-semibold"
                  style="font-size: 13px; line-height: 16px;"
                  aria-live="polite">
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

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            showPane(target);
            history.replaceState(null, '', target);
        });
    });

    // Check localStorage for saved tab, then check hash, then default to #home
    const savedTab = localStorage.getItem('activeMainTab');
    const initial = savedTab || window.location.hash || '#home';
    showPane(initial);
});
</script>
<!-- 🌟 Header End -->