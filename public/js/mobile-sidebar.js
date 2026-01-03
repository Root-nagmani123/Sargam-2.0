/**
 * Mobile-First Responsive Sidebar
 * Handles sidebar behavior across all screen sizes
 * Compatible with existing desktop sidebar toggle
 */

(function() {
    'use strict';

    // Breakpoints
    const BREAKPOINTS = {
        mobile: 768,
        tablet: 992,
        desktop: 1200
    };

    // State management
    let isMobile = false;
    let isTablet = false;
    let sidebarOverlay = null;

    /**
     * Check if current viewport is mobile
     */
    function checkMobileView() {
        const width = window.innerWidth;
        const wasMobile = isMobile;
        const wasTablet = isTablet;

        isMobile = width < BREAKPOINTS.mobile;
        isTablet = width >= BREAKPOINTS.mobile && width < BREAKPOINTS.tablet;

        // If transitioning between mobile/tablet and desktop, reset sidebar state
        if ((wasMobile || wasTablet) && !isMobile && !isTablet) {
            // Transitioning to desktop
            handleDesktopTransition();
        } else if ((!wasMobile && !wasTablet) && (isMobile || isTablet)) {
            // Transitioning to mobile/tablet
            handleMobileTransition();
        }

        return isMobile || isTablet;
    }

    /**
     * Create sidebar overlay for mobile
     */
    function createOverlay() {
        if (sidebarOverlay) return sidebarOverlay;

        sidebarOverlay = document.createElement('div');
        sidebarOverlay.className = 'sidebar-overlay';
        sidebarOverlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(sidebarOverlay);

        // Close sidebar when overlay is clicked
        sidebarOverlay.addEventListener('click', closeMobileSidebar);

        return sidebarOverlay;
    }

    /**
     * Open mobile sidebar
     */
    function openMobileSidebar() {
        const sidebar = document.querySelector('.left-sidebar');
        const sidePanel = document.querySelector('.side-mini-panel');
        
        if (!sidebar && !sidePanel) return;

        // Show overlay
        const overlay = createOverlay();
        overlay.classList.add('active');

        // Show sidebar
        if (sidebar) sidebar.classList.add('show-sidebar');
        if (sidePanel) sidePanel.classList.add('show-sidebar');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Accessibility
        if (sidebar) sidebar.setAttribute('aria-hidden', 'false');
        
        // Trap focus in sidebar
        trapFocus(sidebar || sidePanel);
    }

    /**
     * Close mobile sidebar
     */
    function closeMobileSidebar() {
        const sidebar = document.querySelector('.left-sidebar');
        const sidePanel = document.querySelector('.side-mini-panel');
        const overlay = document.querySelector('.sidebar-overlay');

        if (sidebar) {
            sidebar.classList.remove('show-sidebar');
            sidebar.setAttribute('aria-hidden', 'true');
        }
        if (sidePanel) sidePanel.classList.remove('show-sidebar');
        if (overlay) overlay.classList.remove('active');

        // Restore body scroll
        document.body.style.overflow = '';

        // Return focus to toggle button
        const toggleBtn = document.getElementById('headerCollapse');
        if (toggleBtn) toggleBtn.focus();
    }

    /**
     * Toggle mobile sidebar
     */
    function toggleMobileSidebar() {
        const sidebar = document.querySelector('.left-sidebar');
        const hasSidebarOpen = sidebar && sidebar.classList.contains('show-sidebar');

        if (hasSidebarOpen) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    /**
     * Handle transition to desktop
     */
    function handleDesktopTransition() {
        const overlay = document.querySelector('.sidebar-overlay');
        const sidebar = document.querySelector('.left-sidebar');
        const sidePanel = document.querySelector('.side-mini-panel');

        // Remove overlay
        if (overlay) overlay.classList.remove('active');

        // Restore body scroll
        document.body.style.overflow = '';

        // Don't force remove show-sidebar on desktop - let desktop JS handle it
        // Just ensure accessibility attributes are correct
        if (sidebar) sidebar.setAttribute('aria-hidden', 'false');
    }

    /**
     * Handle transition to mobile/tablet
     */
    function handleMobileTransition() {
        const sidebar = document.querySelector('.left-sidebar');
        const sidePanel = document.querySelector('.side-mini-panel');

        // Close sidebar by default on mobile
        if (sidebar) {
            sidebar.classList.remove('show-sidebar');
            sidebar.setAttribute('aria-hidden', 'true');
        }
        if (sidePanel) sidePanel.classList.remove('show-sidebar');

        // Create overlay element
        createOverlay();
    }

    /**
     * Trap focus within sidebar (accessibility)
     */
    function trapFocus(element) {
        if (!element) return;

        const focusableElements = element.querySelectorAll(
            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length === 0) return;

        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        // Focus first element
        firstFocusable.focus();

        // Handle Tab key
        function handleTab(e) {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    lastFocusable.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    firstFocusable.focus();
                    e.preventDefault();
                }
            }
        }

        element.addEventListener('keydown', handleTab);
    }

    /**
     * Handle ESC key to close sidebar on mobile
     */
    function handleEscKey(e) {
        if (e.key === 'Escape' && (isMobile || isTablet)) {
            closeMobileSidebar();
        }
    }

    /**
     * Enhanced sidebar toggle for responsive behavior
     */
    function enhancedSidebarToggle(originalToggleFn) {
        return function(e) {
            if (isMobile || isTablet) {
                // Mobile/tablet behavior
                e.preventDefault();
                e.stopPropagation();
                toggleMobileSidebar();
            } else {
                // Desktop behavior - call original function
                if (typeof originalToggleFn === 'function') {
                    originalToggleFn.call(this, e);
                }
            }
        };
    }

    /**
     * Adjust DataTables on resize (debounced)
     */
    let resizeTimer;
    function adjustDataTablesOnResize() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.jQuery && $.fn && $.fn.dataTable) {
                try {
                    const api = $.fn.dataTable.tables({ visible: true, api: true });
                    if (api && api.columns) {
                        api.columns.adjust();
                        if (api.responsive && api.responsive.recalc) {
                            api.responsive.recalc();
                        }
                    }
                } catch (err) {
                    console.warn('DataTables adjust failed:', err);
                }
            }
        }, 250);
    }

    /**
     * Add close button to sidebar for mobile
     */
    function addMobileCloseButton() {
        const sidebar = document.querySelector('.left-sidebar');
        if (!sidebar) return;

        // Check if close button already exists
        if (sidebar.querySelector('.sidebar-close-btn')) return;

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'sidebar-close-btn';
        closeBtn.setAttribute('aria-label', 'Close sidebar');
        closeBtn.innerHTML = '<i class="material-icons">close</i>';
        closeBtn.addEventListener('click', closeMobileSidebar);

        // Create header if it doesn't exist
        let sidebarHeader = sidebar.querySelector('.sidebar-header');
        if (!sidebarHeader) {
            sidebarHeader = document.createElement('div');
            sidebarHeader.className = 'sidebar-header';
            
            // Add logo/title
            const logo = document.createElement('div');
            logo.className = 'sidebar-logo';
            const img = document.querySelector('.navbar-brand img');
            if (img) {
                const logoImg = img.cloneNode(true);
                logoImg.style.height = '40px';
                logo.appendChild(logoImg);
            }
            
            sidebarHeader.appendChild(logo);
            sidebar.insertBefore(sidebarHeader, sidebar.firstChild);
        }

        // Add close button to header
        sidebarHeader.appendChild(closeBtn);
    }

    /**
     * Initialize mobile sidebar enhancements
     */
    function init() {
        // Check initial viewport
        checkMobileView();

        // Add mobile close button
        addMobileCloseButton();

        // Create overlay
        createOverlay();

        // Set initial state for mobile
        if (isMobile || isTablet) {
            handleMobileTransition();
        }

        // Enhance existing toggle button
        const toggleBtn = document.getElementById('headerCollapse');
        if (toggleBtn) {
            // Store original click handler reference if it exists
            const originalHandler = toggleBtn.onclick;
            
            // Remove existing click listener
            toggleBtn.onclick = null;
            
            // Add enhanced toggle
            toggleBtn.addEventListener('click', enhancedSidebarToggle(function(e) {
                // This will call the desktop toggle logic
                // The desktop logic is in master.blade.php
                if (!isMobile && !isTablet) {
                    // Let the original desktop handler work
                    const sidebar = document.getElementById("main-wrapper");
                    const body = document.body;
                    const sidebarmenus = document.querySelectorAll(".sidebarmenu");
                    const icon = document.getElementById("sidebarToggleIcon");
                    
                    if (sidebar) {
                        sidebar.classList.toggle("show-sidebar");
                        sidebarmenus.forEach(function(el) {
                            el.classList.toggle("close");
                        });

                        const currentType = body.getAttribute("data-sidebartype");
                        if (currentType === "mini-sidebar") {
                            body.setAttribute("data-sidebartype", "full");
                            try { localStorage.setItem('SidebarType', 'full'); } catch (e) {}
                            if (icon) icon.textContent = "keyboard_double_arrow_left";
                        } else {
                            body.setAttribute("data-sidebartype", "mini-sidebar");
                            try { localStorage.setItem('SidebarType', 'mini-sidebar'); } catch (e) {}
                            if (icon) icon.textContent = "keyboard_double_arrow_right";
                        }

                        // Adjust DataTables
                        setTimeout(function() {
                            if (window.jQuery && $.fn && $.fn.dataTable) {
                                const api = $.fn.dataTable.tables({ visible: true, api: true });
                                if (api && api.columns) {
                                    api.columns.adjust();
                                    if (api.responsive && api.responsive.recalc) {
                                        api.responsive.recalc();
                                    }
                                }
                            }
                        }, 300);
                    }
                }
            }));
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const wasResponsive = isMobile || isTablet;
            checkMobileView();
            const isResponsive = isMobile || isTablet;

            // Adjust DataTables on resize
            adjustDataTablesOnResize();

            // If changed from mobile to desktop or vice versa, handle appropriately
            if (wasResponsive !== isResponsive) {
                if (isResponsive) {
                    handleMobileTransition();
                } else {
                    handleDesktopTransition();
                }
            }
        });

        // Handle ESC key
        document.addEventListener('keydown', handleEscKey);

        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                checkMobileView();
                adjustDataTablesOnResize();
            }, 100);
        });

        console.log('Mobile sidebar initialized');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose functions for external use
    window.MobileSidebar = {
        open: openMobileSidebar,
        close: closeMobileSidebar,
        toggle: toggleMobileSidebar,
        isMobile: function() { return isMobile; },
        isTablet: function() { return isTablet; }
    };

})();
