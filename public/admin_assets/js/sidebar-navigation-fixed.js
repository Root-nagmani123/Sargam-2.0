/**
 * Fixed Sidebar Navigation System
 * Properly connects sidebar menu links to header tabs and body content
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    console.log('Sidebar navigation system initializing...');

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
        const miniNavItems = document.querySelectorAll('.mini-nav-item');
        
        if (miniNavItems.length === 0) {
            console.warn('No mini-nav items found');
            return;
        }
        
        console.log('Found', miniNavItems.length, 'mini-nav items');
        
        miniNavItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Mini-nav item clicked:', this.id);
                
                // Remove selected class from all mini-nav items
                miniNavItems.forEach(function(navItem) {
                    navItem.classList.remove('selected');
                });
                
                // Add selected class to clicked item
                this.classList.add('selected');
                
                // Store active mini-nav in localStorage
                if (this.id) {
                    localStorage.setItem('active-mini-nav', this.id);
                }
            });
        });
        
        // Restore active mini-nav from localStorage
        const activeId = localStorage.getItem('active-mini-nav');
        if (activeId) {
            const activeItem = document.getElementById(activeId);
            if (activeItem) {
                activeItem.classList.add('selected');
                console.log('Restored active mini-nav:', activeId);
            }
        }
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
        const leftSidebar = document.querySelector('.left-sidebar');
        if (!leftSidebar) return;
        
        const sidebarTabContent = leftSidebar.querySelector('.tab-content');
        if (!sidebarTabContent) return;
        
        const allSidebarPanes = sidebarTabContent.querySelectorAll('.tab-pane');
        allSidebarPanes.forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });
        
        const targetSidebarPane = sidebarTabContent.querySelector(targetId + '.tab-pane');
        if (targetSidebarPane) {
            targetSidebarPane.classList.add('show', 'active');
            console.log('Sidebar pane activated:', targetId);
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
    
    setTimeout(function() {
        initializeSidebarLinks();
        initializeMiniNav();
        initializeSidebarCollapse();
        initializeHeaderTabs();
        initializeScrollPersistence();
        
        // CRITICAL: Detect which section has content and activate that tab
        setTimeout(function() {
            detectAndActivateCurrentTab();
        }, 150);
        
        console.log('Sidebar navigation system initialized successfully');
    }, 100);
});
