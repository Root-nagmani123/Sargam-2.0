/**
 * Fixed Sidebar Navigation System
 * Properly connects sidebar menu links to header tabs and body content
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
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
    
    function initializeMiniNav() {
        // GLOBAL SINGLE-CLICK HANDLER using event delegation
        // This prevents multiple event listeners from causing multi-click issues
        
        const miniNavContainers = document.querySelectorAll('.mini-nav');
        
        if (miniNavContainers.length === 0) {
            console.warn('No mini-nav containers found');
            return;
        }
        
        console.log('Found', miniNavContainers.length, 'mini-nav containers');
        
        function getSidebarPaneFromContainer(container) {
            return container.closest('.tab-pane') || document;
        }

        function getStorageKeyForPane(pane) {
            const paneId = pane && pane.id ? pane.id : 'global';
            return 'active-mini-nav-' + paneId;
        }

        function isDynamicGroupSidebarItem(item) {
            return item.classList.contains('sidebar-group-item')
                || item.querySelector('.sidebar-group-link');
        }

        function isDynamicGroupSidebarPane(paneRoot) {
            return paneRoot.querySelector('.sidebar-group-link')
                || paneRoot.querySelector('[data-sidebar-layout="dynamic"]');
        }

        function showDynamicSidebarNav(paneRoot) {
            if (typeof window.setDynamicSidebarMenuExpanded === 'function') {
                window.setDynamicSidebarMenuExpanded(true, false);
                return;
            }
            paneRoot.querySelectorAll('.sidebarmenu nav.sidebar-nav').forEach(function(nav) {
                nav.classList.add('d-block', 'left-none');
                nav.style.display = 'block';
            });
            document.body.setAttribute('data-sidebartype', 'full');
        }

        // Use event delegation on each container to handle all clicks
        miniNavContainers.forEach(function(container) {
            container.addEventListener('click', function(e) {
                const miniNavItem = e.target.closest('.mini-nav-item');
                
                if (!miniNavItem || !container.contains(miniNavItem)) return;

                const paneRoot = getSidebarPaneFromContainer(container);
                if (isDynamicGroupSidebarItem(miniNavItem) || isDynamicGroupSidebarPane(paneRoot)) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                const itemId = miniNavItem.id;
                console.log('Mini-nav item clicked:', itemId);
                
                // Remove selected class only within current pane
                paneRoot.querySelectorAll('.mini-nav-item').forEach(function(navItem) {
                    navItem.classList.remove('selected');
                });
                
                // Add selected class to clicked item
                miniNavItem.classList.add('selected');
                
                // Hide sidebar menus only within current pane
                paneRoot.querySelectorAll('.sidebarmenu nav').forEach(function(nav) {
                    nav.classList.remove('d-block');
                    nav.style.display = 'none';
                });
                
                // Show the target menu
                const targetMenuId = 'menu-right-' + itemId;
                let targetMenu = paneRoot.querySelector('#' + CSS.escape(targetMenuId));
                if (!targetMenu) {
                    targetMenu = document.getElementById(targetMenuId);
                }
                if (targetMenu) {
                    targetMenu.classList.add('d-block');
                    targetMenu.style.display = 'block';
                    document.body.setAttribute('data-sidebartype', 'full');
                    console.log('Displayed menu:', targetMenuId);
                }
                
                // Store active mini-nav in localStorage
                if (itemId) {
                    localStorage.setItem(getStorageKeyForPane(paneRoot), itemId);
                }
            });
        });
        
        // Restore active mini-nav per pane
        miniNavContainers.forEach(function(container) {
            const paneRoot = getSidebarPaneFromContainer(container);
            if (isDynamicGroupSidebarPane(paneRoot)) {
                showDynamicSidebarNav(paneRoot);
                return;
            }
            const activeId = localStorage.getItem(getStorageKeyForPane(paneRoot));
            if (!activeId) return;

            const activeItem = paneRoot.querySelector('#' + CSS.escape(activeId));
            if (!activeItem) return;

            paneRoot.querySelectorAll('.mini-nav-item').forEach(function(navItem) {
                navItem.classList.remove('selected');
            });
            activeItem.classList.add('selected');

            const targetMenuId = 'menu-right-' + activeId;
            let targetMenu = paneRoot.querySelector('#' + CSS.escape(targetMenuId));
            if (!targetMenu) {
                targetMenu = document.getElementById(targetMenuId);
            }
            if (targetMenu) {
                paneRoot.querySelectorAll('.sidebarmenu nav').forEach(function(nav) {
                    nav.classList.remove('d-block');
                    nav.style.display = 'none';
                });
                targetMenu.classList.add('d-block');
                targetMenu.style.display = 'block';
            }
            console.log('Restored active mini-nav:', activeId, 'for pane:', paneRoot.id || 'global');
        });
    }
    
    // ==========================================
    // SIDEBAR MENUS: Collapse functionality
    // ==========================================
    
    function initializeSidebarCollapse() {
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
                
                // Use Bootstrap's collapse API
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

        // Dynamic single-pane sidebar: one #sidebar-setup pane serves every
        // category (its groups/menus swap via AJAX, not by pane-swapping). The
        // legacy per-tab panes (#sidebar-communications, #sidebar-home, …) no
        // longer exist, so the old mapping below would deactivate the only pane
        // and hide the whole sidebar. Keep the dynamic pane active for all tabs.
        const dynamicPane = sidebarTabContent.querySelector('.tab-pane[data-sidebar-layout="dynamic"]');
        if (dynamicPane) {
            dynamicPane.classList.add('show', 'active');
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
        const bodyWrapper = document.querySelector('.body-wrapper');
        if (!bodyWrapper) return;

        // Which content pane actually holds the page content (the page's @section).
        let contentTabId = null;
        bodyWrapper.querySelectorAll('.tab-pane').forEach(function(pane) {
            if (pane.textContent.trim().length > 0 || pane.querySelector('*')) {
                if (pane.id !== 'home') {
                    contentTabId = '#' + pane.id;
                }
            }
        });

        // The header tab to highlight comes from the RBAC resolver — the SAME source
        // as the breadcrumb (window.SARGAM_ACTIVE_NAV_TAB). This keeps the active
        // header tab in lock-step with the breadcrumb, even if a menu was relocated
        // to a category whose content pane differs. Falls back to the content pane
        // when the server value isn't available.
        const tabToHighlight = (typeof window.SARGAM_ACTIVE_NAV_TAB === 'string' && window.SARGAM_ACTIVE_NAV_TAB)
            ? window.SARGAM_ACTIVE_NAV_TAB
            : contentTabId;

        if (tabToHighlight) {
            document.querySelectorAll('.sidebar-category-link').forEach(function(tab) {
                var on = tab.getAttribute('href') === tabToHighlight;
                tab.classList.toggle('active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });
        }

        // Show whichever pane actually has content so the page never goes blank,
        // even when the highlighted tab differs from the content's section.
        if (contentTabId) {
            syncBodyWrapperTab(contentTabId);
            syncSidebarTab(contentTabId);
            setTimeout(adjustAllDataTables, 150);
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
    
    setTimeout(function() {
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
    }, 100);
});
