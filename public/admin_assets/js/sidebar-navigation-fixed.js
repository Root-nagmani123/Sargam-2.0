/**
 * Fixed Sidebar Navigation System
 * Properly connects sidebar menu links to header tabs and body content
 */

(function (global) {
    'use strict';

    function normalizePathname(urlInput) {
        try {
            var u = typeof urlInput === 'string' ? new URL(urlInput, global.location.origin) : urlInput;
            var p = u.pathname || '/';
            if (p.length > 1 && p.charAt(p.length - 1) === '/') {
                p = p.slice(0, -1);
            }
            return p;
        } catch (e) {
            return '';
        }
    }

    function isSidebarPageNavLink(link) {
        var href = link.getAttribute('href');
        if (!href || href === '#' || href === 'javascript:void(0)') {
            return false;
        }
        if (href.charAt(0) === '#') {
            return false;
        }
        if (link.getAttribute('data-bs-toggle') === 'collapse') {
            return false;
        }
        return true;
    }

    /**
     * Per-nav: clear .active, then mark the best .sidebar-link for the current URL.
     * Uses exact match or longest pathname prefix so index + create/edit/show stay highlighted.
     *
     * @param {NodeListOf<Element>|Element[]} sidebarMenus - .sidebarmenu nav elements
     */
    global.sargamMarkSidebarActiveLinks = function (sidebarMenus) {
        if (!sidebarMenus || !sidebarMenus.length) {
            return;
        }
        var currentPath = normalizePathname(global.location.href);
        Array.prototype.forEach.call(sidebarMenus, function (nav) {
            var links = Array.prototype.slice.call(nav.querySelectorAll('.sidebar-link[href]')).filter(isSidebarPageNavLink);
            links.forEach(function (l) {
                l.classList.remove('active');
            });
            var best = null;
            var bestLen = -1;
            links.forEach(function (link) {
                var linkPath = '';
                try {
                    linkPath = normalizePathname(link.href);
                } catch (e2) {
                    return;
                }
                if (!linkPath) {
                    return;
                }
                if (currentPath !== linkPath && currentPath.indexOf(linkPath + '/') !== 0) {
                    return;
                }
                if (linkPath.length > bestLen) {
                    bestLen = linkPath.length;
                    best = link;
                }
            });
            if (best) {
                best.classList.add('active');
            }
        });
    };
})(typeof window !== 'undefined' ? window : this);

function sargamRunSidebarNavigationInit() {
    'use strict';

    /* Browser has no bare `global`; all APIs below use this alias. */
    var global =
        typeof window !== 'undefined'
            ? window
            : typeof globalThis !== 'undefined'
              ? globalThis
              : this;

    if (document.documentElement.getAttribute('data-sargam-sidebar-nav-init') === '1') {
        return;
    }

    console.log('Sidebar navigation system initializing...');

    // ==========================================
    // DataTables: global safe adjust helper
    // ==========================================
    function adjustAllDataTables() {
        try {
            if (window.jQuery && $.fn && $.fn.dataTable) {
                const api = $.fn.dataTable.tables({ visible: true, api: true });
                if (api && api.columns) {
                    api.columns.adjust();
                    if (api.responsive && api.responsive.recalc) {
                        api.responsive.recalc();
                    }
                    // Only redraw client-side tables
                    if (api.settings) {
                        const settings = api.settings();
                        let clientSideExists = false;
                        for (let i = 0; i < settings.length; i++) {
                            if (!settings[i].oFeatures.bServerSide) {
                                clientSideExists = true;
                                break;
                            }
                        }
                        if (clientSideExists) api.draw(false);
                    }
                }
            }
        } catch (err) {
            console.warn('DataTables adjust failed (navigation-fixed):', err);
        }
    }

    // ==========================================
    // SIDEBAR MENU LINKS: Trigger header tabs
    // ==========================================
    
    function initializeSidebarLinks() {
        // Get all sidebar menu links that navigate to pages
        const sidebarLinks = document.querySelectorAll('.sidebar-link[href]:not([data-bs-toggle="collapse"])');
        
        console.log('Found', sidebarLinks.length, 'sidebar navigation links');
        
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // If it's a real page navigation (not collapse or #), let it proceed normally
                if (href && href !== '#' && href !== 'javascript:void(0)' && !href.startsWith('#collapse')) {
                    console.log('Navigating to:', href);
                    // Let the normal navigation happen
                    // The page will load and show the correct tab based on @section
                }
            });
        });
    }
    
    // ==========================================
    // MINI-NAV: Controls sidebar menu visibility
    // ==========================================
    
    function getSidebarPaneFromMiniNav(container) {
        return (
            container.closest('.side-mini-panel') ||
            container.closest('.tab-pane') ||
            document
        );
    }

    function getFlyoutPanelRoot(container) {
        return container.closest('.side-mini-panel') || getSidebarPaneFromMiniNav(container);
    }

    function getMiniNavStorageKey(pane) {
        const paneId = pane && pane.id ? pane.id : 'global';
        return 'active-mini-nav-' + paneId;
    }

    function escapeMenuId(id) {
        if (typeof CSS !== 'undefined' && CSS.escape) {
            return CSS.escape(id);
        }
        return String(id).replace(/([#.;?+*^$[\]\\(){}|\-])/g, '\\$1');
    }

    function getPaneFlyoutMenus(paneRoot) {
        return paneRoot.querySelectorAll('.sidebarmenu nav.sidebar-nav, .sidebarmenu nav.sargam-menu-flyout');
    }

    function hidePaneFlyoutMenus(paneRoot) {
        getPaneFlyoutMenus(paneRoot).forEach(function (nav) {
            nav.classList.remove('d-block', 'is-active', 'sargam-menu-visible', 'left-none');
            nav.style.removeProperty('display');
        });
    }

    function showPaneFlyoutMenu(nav) {
        if (!nav) {
            return;
        }
        nav.classList.add('d-block', 'is-active', 'sargam-menu-visible', 'left-none');
        nav.style.removeProperty('display');
    }

    function setPanelFlyoutOpen(panel, open) {
        if (!panel) {
            return;
        }
        panel.classList.toggle('expanded', open);
        panel.classList.toggle('sidebar-flyout-open', open);
        panel.setAttribute('data-flyout-open', open ? 'true' : 'false');
        var sidebarmenu = panel.querySelector('.sidebarmenu');
        if (sidebarmenu) {
            sidebarmenu.classList.toggle('hovermenus', open);
        }
    }

    /**
     * Resolve flyout <nav> for a mini-nav item id within a sidebar pane.
     * @param {Element} paneRoot
     * @param {string} itemId - li.mini-nav-item id (e.g. mini-1, setup-mini-9)
     * @returns {Element|null}
     */
    function resolveMenuForMiniItem(paneRoot, itemId) {
        if (!paneRoot || !itemId) {
            return null;
        }
        var byId = paneRoot.querySelector('#' + escapeMenuId('menu-right-' + itemId));
        if (byId) {
            return byId;
        }
        var dataMenus = paneRoot.querySelectorAll('.sidebarmenu nav[data-mini-nav-target]');
        for (var i = 0; i < dataMenus.length; i++) {
            if (dataMenus[i].getAttribute('data-mini-nav-target') === itemId) {
                return dataMenus[i];
            }
        }
        return document.getElementById('menu-right-' + itemId);
    }

    global.sargamResolveMenuForMiniItem = resolveMenuForMiniItem;
    global.sargamShowPaneFlyoutMenu = showPaneFlyoutMenu;
    global.sargamHidePaneFlyoutMenus = hidePaneFlyoutMenus;

    /**
     * Restore saved mini-nav, match active route, or open first item / sole menu for a pane.
     */
    function restoreOrActivatePaneMiniNav(container) {
        if (!container) {
            return;
        }

        const paneRoot = getFlyoutPanelRoot(container);
        const storageKey = getMiniNavStorageKey(
            container.closest('.tab-pane') || paneRoot
        );
        let activeId = null;

        try {
            activeId = localStorage.getItem(storageKey);
        } catch (e) {}

        if (activeId) {
            const storedItem = paneRoot.querySelector('#' + escapeMenuId(activeId));
            if (storedItem) {
                global.sargamActivateMiniNavItem(container, storedItem, false);
                return;
            }
        }

        const menus = getPaneFlyoutMenus(paneRoot);
        if (typeof global.sargamMarkSidebarActiveLinks === 'function') {
            global.sargamMarkSidebarActiveLinks(menus);
        }

        let menuFromRoute = null;
        menus.forEach(function (nav) {
            if (!menuFromRoute && nav.querySelector('.sidebar-link.active')) {
                menuFromRoute = nav;
            }
        });

        if (menuFromRoute) {
            var miniIdFromMenu =
                menuFromRoute.getAttribute('data-mini-nav-target') ||
                (menuFromRoute.id ? menuFromRoute.id.replace(/^menu-right-/, '') : '');
            const miniItem = miniIdFromMenu
                ? paneRoot.querySelector('#' + escapeMenuId(miniIdFromMenu))
                : null;
            if (miniItem) {
                global.sargamActivateMiniNavItem(container, miniItem, false);
                return;
            }
            hidePaneFlyoutMenus(paneRoot);
            showPaneFlyoutMenu(menuFromRoute);
            return;
        }

        const firstMini = container.querySelector('.mini-nav-item[id]');
        if (firstMini) {
            global.sargamActivateMiniNavItem(container, firstMini, false);
            return;
        }

        hidePaneFlyoutMenus(paneRoot);
        if (menus.length === 1) {
            showPaneFlyoutMenu(menus[0]);
        } else if (menus.length > 0) {
            showPaneFlyoutMenu(menus[0]);
        }
    }

    global.sargamRestoreOrActivatePaneMiniNav = restoreOrActivatePaneMiniNav;

    /**
     * Show flyout menu for a mini-nav item without changing page layout (data-sidebartype).
     * @param {Element} container - .mini-nav element
     * @param {Element} miniNavItem - li.mini-nav-item
     * @param {boolean} persist - write selection to localStorage
     */
    global.sargamActivateMiniNavItem = function (container, miniNavItem, persist) {
        if (!container || !miniNavItem || !miniNavItem.id) {
            return;
        }
        const paneRoot = getFlyoutPanelRoot(container);
        const itemId = miniNavItem.id;

        paneRoot.querySelectorAll('.mini-nav-item').forEach(function (navItem) {
            navItem.classList.remove('selected');
        });
        miniNavItem.classList.add('selected');

        hidePaneFlyoutMenus(paneRoot);

        const targetMenu = resolveMenuForMiniItem(paneRoot, itemId);
        showPaneFlyoutMenu(targetMenu);

        if (persist && itemId) {
            try {
                var tabPane = container.closest('.tab-pane');
                localStorage.setItem(getMiniNavStorageKey(tabPane || paneRoot), itemId);
            } catch (e) {}
        }
    };

    function initializeMiniNav() {
        const miniNavContainers = document.querySelectorAll('.mini-nav');

        if (miniNavContainers.length === 0) {
            console.warn('No mini-nav containers found');
            return;
        }

        console.log('Found', miniNavContainers.length, 'mini-nav containers');

        function isDesktopFlyout() {
            return global.matchMedia && global.matchMedia('(min-width: 992px)').matches;
        }

        function panelHasFlyoutHover(panel) {
            if (!panel) {
                return false;
            }
            return !!(
                panel.querySelector('.mini-nav .mini-nav-item:hover') ||
                panel.querySelector('.sidebarmenu:hover') ||
                panel.matches(':hover')
            );
        }

        document.querySelectorAll('.side-mini-panel').forEach(function (panel) {
            hidePaneFlyoutMenus(panel);
            setPanelFlyoutOpen(panel, false);

            if (panel.getAttribute('data-sargam-flyout-bound') === '1') {
                return;
            }
            panel.setAttribute('data-sargam-flyout-bound', '1');

            panel.addEventListener('mouseover', function (e) {
                if (!isDesktopFlyout()) {
                    return;
                }
                var miniNavItem = e.target.closest('.mini-nav-item');
                if (!miniNavItem || !panel.contains(miniNavItem)) {
                    return;
                }
                var miniNav = panel.querySelector('.mini-nav');
                if (!miniNav || !miniNavItem.id) {
                    return;
                }
                global.sargamActivateMiniNavItem(miniNav, miniNavItem, false);
                setPanelFlyoutOpen(panel, true);
            });

            panel.addEventListener('mouseout', function (e) {
                if (!isDesktopFlyout()) {
                    return;
                }
                var to = e.relatedTarget;
                if (to && panel.contains(to)) {
                    return;
                }
                global.requestAnimationFrame(function () {
                    if (!panelHasFlyoutHover(panel)) {
                        setPanelFlyoutOpen(panel, false);
                    }
                });
            });

            panel.addEventListener('focusin', function () {
                if (isDesktopFlyout()) {
                    setPanelFlyoutOpen(panel, true);
                }
            });

            panel.addEventListener('focusout', function (e) {
                if (!isDesktopFlyout()) {
                    return;
                }
                if (e.relatedTarget && panel.contains(e.relatedTarget)) {
                    return;
                }
                global.requestAnimationFrame(function () {
                    if (!panelHasFlyoutHover(panel)) {
                        setPanelFlyoutOpen(panel, false);
                    }
                });
            });
        });

        miniNavContainers.forEach(function (container) {
            const aside = container.closest('.side-mini-panel');

            container.addEventListener('click', function (e) {
                const miniNavItem = e.target.closest('.mini-nav-item');
                if (!miniNavItem || !container.contains(miniNavItem)) return;

                e.preventDefault();
                e.stopPropagation();

                global.sargamActivateMiniNavItem(container, miniNavItem, true);
                const asideEl = miniNavItem.closest('.side-mini-panel') || aside;
                setPanelFlyoutOpen(asideEl, true);
            });
        });
        
        // Restore / default active mini-nav for every sidebar pane in the project
        miniNavContainers.forEach(function (container) {
            restoreOrActivatePaneMiniNav(container);
        });

        document.querySelectorAll('#sidebarTabContent .tab-pane').forEach(function (pane) {
            const miniNav = pane.querySelector('.mini-nav');
            if (miniNav) {
                restoreOrActivatePaneMiniNav(miniNav);
            }
        });

        document.querySelectorAll('.side-mini-panel').forEach(function (panel) {
            panel.classList.add('sargam-hover-sidebar');
            const miniNav = panel.querySelector('.mini-nav');
            if (miniNav) {
                restoreOrActivatePaneMiniNav(miniNav);
            }
        });
    }

    function initAllSidebarPanels() {
        document.querySelectorAll('.side-mini-panel .mini-nav').forEach(function (container) {
            restoreOrActivatePaneMiniNav(container);
        });
    }

    global.sargamInitAllSidebarPanels = initAllSidebarPanels;
    
    // ==========================================
    // SIDEBAR MENUS: Collapse functionality
    // ==========================================
    
    function initializeSidebarCollapse() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
            console.warn('Bootstrap Collapse not available; sidebar collapse handlers skipped');
            return;
        }

        const collapseLinks = document.querySelectorAll('.sidebar-link[data-bs-toggle="collapse"]');
        
        console.log('Found', collapseLinks.length, 'collapse links in sidebar');
        
        collapseLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const targetId = this.getAttribute('href') || this.getAttribute('data-bs-target');
                if (!targetId) return;
                
                const targetElement = document.querySelector(targetId);
                if (!targetElement) {
                    console.warn('Target element not found:', targetId);
                    return;
                }
                
                console.log('Toggling collapse:', targetId);
                
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(targetElement, {
                    toggle: false
                });
                
                // Toggle the collapse
                if (targetElement.classList.contains('show')) {
                    bsCollapse.hide();
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    bsCollapse.show();
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }
    
    // ==========================================
    // HEADER TABS: Enhanced to sync everything
    // ==========================================
    
    function initializeHeaderTabs() {
        const headerTabs = document.querySelectorAll('.navbar-nav [data-bs-toggle="tab"]');
        
        console.log('Found', headerTabs.length, 'header tab links');
        
        headerTabs.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(event) {
                const targetId = this.getAttribute('href');
                console.log('Header tab activated:', targetId);
                
                // Sync body wrapper tabs
                syncBodyWrapperTab(targetId);
                
                // Sync sidebar tabs
                syncSidebarTab(targetId);

                // After tab becomes visible, adjust DataTables to correct header widths
                setTimeout(adjustAllDataTables, 150);
            });
        });
    }
    
    function syncBodyWrapperTab(targetId) {
        const bodyWrapper = document.querySelector('.body-wrapper');
        if (!bodyWrapper) return;
        
        const allBodyPanes = bodyWrapper.querySelectorAll('.tab-pane');
        allBodyPanes.forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });
        
        const targetPane = bodyWrapper.querySelector(targetId + '.tab-pane');
        if (targetPane) {
            targetPane.classList.add('show', 'active');
            console.log('Body wrapper pane activated:', targetId);
        } else {
            console.warn('Body wrapper pane not found:', targetId);
        }
    }
    
    function syncSidebarTab(targetId) {
        // Prefer direct container to avoid dependency on wrapper class names.
        const sidebarTabContent =
            document.getElementById('sidebarTabContent') ||
            document.querySelector('.left-sidebar .tab-content') ||
            document.querySelector('.side-mini-panel .tab-content');
        if (!sidebarTabContent) {
            console.warn('Sidebar tab content not found for target:', targetId);
            return;
        }
        
        const allSidebarPanes = sidebarTabContent.querySelectorAll('.tab-pane');
        allSidebarPanes.forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });

        // Main header tab ids and sidebar pane ids are different.
        // Map them explicitly to keep correct sidebar open.
        const sidebarTabMap = {
            '#home': '#sidebar-home',
            '#tab-setup': '#sidebar-setup',
            '#tab-communications': '#sidebar-communications',
            '#tab-academics': '#sidebar-academics',
            '#tab-material-management': '#sidebar-purchase-order'
        };

        const sidebarTargetId = sidebarTabMap[targetId] || targetId;
        const targetSidebarPane = sidebarTabContent.querySelector(sidebarTargetId + '.tab-pane');
        if (targetSidebarPane) {
            targetSidebarPane.classList.add('show', 'active');
            console.log('Sidebar pane activated:', sidebarTargetId);

            const miniNav = targetSidebarPane.querySelector('.mini-nav');
            if (miniNav) {
                restoreOrActivatePaneMiniNav(miniNav);
            }
            const paneMenus = targetSidebarPane.querySelectorAll('.sidebarmenu nav.sidebar-nav');
            if (typeof global.sargamMarkSidebarActiveLinks === 'function') {
                global.sargamMarkSidebarActiveLinks(paneMenus);
            }
            setTimeout(function () {
                if (typeof global.sargamInitAllSidebarPanels === 'function') {
                    global.sargamInitAllSidebarPanels();
                }
            }, 50);
        } else {
            // Safe fallback: keep home sidebar visible instead of leaving empty state
            const homeSidebarPane = sidebarTabContent.querySelector('#sidebar-home.tab-pane');
            if (homeSidebarPane) {
                homeSidebarPane.classList.add('show', 'active');
            }
        }
    }
    
    // ==========================================
    // DETECT CURRENT SECTION AND ACTIVATE TAB
    // ==========================================
    
    function detectAndActivateCurrentTab() {
        // Check which @section is being used on this page
        const bodyWrapper = document.querySelector('.body-wrapper');
        if (!bodyWrapper) return;
        
        // Find which tab-pane has actual content
        const tabPanes = bodyWrapper.querySelectorAll('.tab-pane');
        let activeTabId = null;
        
        tabPanes.forEach(function(pane) {
            // Check if this pane has any content (not just whitespace)
            if (pane.textContent.trim().length > 0 || pane.querySelector('*')) {
                const paneId = pane.id;
                console.log('Found content in tab pane:', paneId);
                
                // Activate this tab if it's not the home tab and has content
                if (paneId !== 'home') {
                    activeTabId = '#' + paneId;
                }
            }
        });
        
        // If we found a non-home tab with content, activate it
        if (activeTabId) {
            console.log('Auto-activating tab based on content:', activeTabId);
            
            // Find and activate the corresponding header tab
            const headerTab = document.querySelector('.navbar-nav a[href="' + activeTabId + '"]');
            if (headerTab) {
                // Remove active from all tabs
                document.querySelectorAll('.navbar-nav [data-bs-toggle="tab"]').forEach(function(tab) {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                
                // Activate the correct header tab
                headerTab.classList.add('active');
                headerTab.setAttribute('aria-selected', 'true');
                
                // Sync both content areas
                syncBodyWrapperTab(activeTabId);
                syncSidebarTab(activeTabId);

                // Adjust tables after auto-activation
                setTimeout(adjustAllDataTables, 150);
            }
        }
    }
    
    // ==========================================
    // SCROLL POSITION PERSISTENCE
    // ==========================================
    
    function initializeScrollPersistence() {
        const sidebar = document.querySelector('.sidebarmenu .simplebar-content-wrapper');
        
        if (sidebar) {
            const scrollPos = localStorage.getItem('sidebar-scroll');
            if (scrollPos) {
                sidebar.scrollTop = parseInt(scrollPos, 10);
            }
            
            window.addEventListener('beforeunload', function() {
                localStorage.setItem('sidebar-scroll', sidebar.scrollTop);
            });
        }
    }
    
    // ==========================================
    // INITIALIZE ALL FUNCTIONALITY
    // ==========================================
    
    try {
        initializeSidebarLinks();
        initializeMiniNav();
        initializeSidebarCollapse();
        initializeHeaderTabs();
        initializeScrollPersistence();
        
        // Observe layout width changes and adjust DataTables accordingly
        const mainWrapper = document.getElementById('main-wrapper');
        const bodyWrapperEl = document.querySelector('.body-wrapper');
        if (window.ResizeObserver) {
            const ro = new ResizeObserver(function(entries) {
                // Debounce via requestAnimationFrame for smoother updates
                window.requestAnimationFrame(adjustAllDataTables);
            });
            if (mainWrapper) ro.observe(mainWrapper);
            if (bodyWrapperEl) ro.observe(bodyWrapperEl);
        } else {
            // Fallback: on window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(adjustAllDataTables, 150);
            });
        }

        // Also watch class and attribute changes that indicate sidebar toggling
        if (window.MutationObserver) {
            const mo = new MutationObserver(function(mutationsList) {
                for (const mutation of mutationsList) {
                    if (mutation.type === 'attributes') {
                        const name = mutation.attributeName || '';
                        if (name === 'class' || name === 'data-sidebartype') {
                            // Allow transition to complete
                            setTimeout(adjustAllDataTables, 150);
                        }
                    }
                }
            });
            if (mainWrapper) mo.observe(mainWrapper, { attributes: true, attributeFilter: ['class'] });
            mo.observe(document.body, { attributes: true, attributeFilter: ['data-sidebartype'] });
        }

        // Adjust on Bootstrap collapse show/hide (menus may affect available width)
        document.addEventListener('shown.bs.collapse', function() { setTimeout(adjustAllDataTables, 100); });
        document.addEventListener('hidden.bs.collapse', function() { setTimeout(adjustAllDataTables, 100); });
        
        // CRITICAL: Detect which section has content and activate that tab
        setTimeout(function() {
            detectAndActivateCurrentTab();
        }, 150);
        
        console.log('Sidebar navigation system initialized successfully');
        document.documentElement.setAttribute('data-sargam-sidebar-nav-init', '1');
    } catch (initErr) {
        console.error('Sidebar navigation init failed:', initErr);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', sargamRunSidebarNavigationInit);
} else {
    sargamRunSidebarNavigationInit();
}
