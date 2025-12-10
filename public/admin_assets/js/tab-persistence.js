/**
 * Dashboard Tab Persistence
 * Ensures users always start on Home tab after login
 * Only affects the HEADER navigation tabs, not the content tabs
 */

// Run immediately to avoid flash of wrong content
(function() {
    'use strict';

    const ACTIVE_TAB_KEY = 'sargam_active_tab';
    const TAB_TIMESTAMP_KEY = 'sargam_tab_timestamp';

    /**
     * Force activate only the Home tab in the HEADER navigation
     * BUT: Only on fresh login, not on regular navigation
     */
    function forceActivateHomeTab() {
        // Check if this is a fresh login or just regular navigation
        const isFromLogin = sessionStorage.getItem('fresh_login');
        
        if (!isFromLogin) {
            console.log('Not from login - skipping force home activation');
            // Clear the flag for next time
            sessionStorage.removeItem('fresh_login');
            return; // Don't force home tab on regular navigation
        }
        
        console.log('Fresh login detected - activating home tab');
        sessionStorage.removeItem('fresh_login'); // Clear the flag
        
        // Wait for DOM to be ready
        setTimeout(function() {
            // Only target the header nav tabs (navbar-nav), not content tabs
            const headerNav = document.querySelector('.navbar-nav');
            if (!headerNav) return;

            // Remove active class from all header nav links
            const allNavLinks = headerNav.querySelectorAll('[data-bs-toggle="tab"]');
            allNavLinks.forEach(function(link) {
                link.classList.remove('active');
                link.setAttribute('aria-selected', 'false');
            });

            // Activate home tab link in HEADER only
            const homeTabLink = headerNav.querySelector('a[href="#home"][data-bs-toggle="tab"], a[id="home-tab"]');
            if (homeTabLink) {
                homeTabLink.classList.add('active');
                homeTabLink.setAttribute('aria-selected', 'true');
                console.log('Home tab link activated in header');
            }

            // Now handle BOTH content tab systems properly
            // 1. Body wrapper tabs (main content area)
            const bodyWrapper = document.querySelector('.body-wrapper');
            if (bodyWrapper) {
                const contentTabPanes = bodyWrapper.querySelectorAll('.tab-pane');
                contentTabPanes.forEach(function(pane) {
                    pane.classList.remove('show', 'active');
                });

                // Activate home content pane
                const homeContentPane = bodyWrapper.querySelector('#home.tab-pane');
                if (homeContentPane) {
                    homeContentPane.classList.add('show', 'active');
                    console.log('Home content pane activated in body wrapper');
                }
            }

            // 2. Sidebar tabs (sidebar menu sections) - ALSO activate home
            const leftSidebar = document.querySelector('.left-sidebar');
            if (leftSidebar) {
                const sidebarTabContent = leftSidebar.querySelector('.tab-content');
                if (sidebarTabContent) {
                    const sidebarTabPanes = sidebarTabContent.querySelectorAll('.tab-pane');
                    sidebarTabPanes.forEach(function(pane) {
                        pane.classList.remove('show', 'active');
                    });

                    // Activate home sidebar pane
                    const homeSidebarPane = sidebarTabContent.querySelector('#home.tab-pane');
                    if (homeSidebarPane) {
                        homeSidebarPane.classList.add('show', 'active');
                        console.log('Home sidebar pane activated');
                    }
                }
            }

            // Clear URL hash
            if (window.location.hash && window.location.hash !== '#home') {
                history.replaceState(null, null, window.location.pathname + window.location.search);
            }
        }, 50); // Small delay to ensure DOM is ready
    }

    /**
     * Store active tab when user clicks on a tab
     */
    function initializeTabTracking() {
        const headerNav = document.querySelector('.navbar-nav');
        if (!headerNav) return;

        const navLinks = headerNav.querySelectorAll('[data-bs-toggle="tab"]');
        
        navLinks.forEach(function(link) {
            link.addEventListener('shown.bs.tab', function(event) {
                const tabId = this.getAttribute('href');
                if (tabId) {
                    localStorage.setItem(ACTIVE_TAB_KEY, tabId.substring(1));
                    localStorage.setItem(TAB_TIMESTAMP_KEY, new Date().getTime());
                }
            });

            link.addEventListener('click', function(e) {
                const tabId = this.getAttribute('href');
                if (tabId && tabId !== '#') {
                    localStorage.setItem(ACTIVE_TAB_KEY, tabId.substring(1));
                    localStorage.setItem(TAB_TIMESTAMP_KEY, new Date().getTime());
                }
            });
        });
    }

    /**
     * Clear stored tab when logout form is submitted
     */
    function setupLogoutHandler() {
        const logoutForm = document.querySelector('form[action*="logout"]');
        
        if (logoutForm) {
            logoutForm.addEventListener('submit', function(e) {
                localStorage.removeItem(ACTIVE_TAB_KEY);
                localStorage.removeItem(TAB_TIMESTAMP_KEY);
            });
        }

        const logoutButtons = document.querySelectorAll('form[action*="logout"] button');
        logoutButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                localStorage.removeItem(ACTIVE_TAB_KEY);
                localStorage.removeItem(TAB_TIMESTAMP_KEY);
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            forceActivateHomeTab();
            initializeTabTracking();
            setupLogoutHandler();
        });
    } else {
        // DOM already loaded
        forceActivateHomeTab();
        initializeTabTracking();
        setupLogoutHandler();
    }
})();
