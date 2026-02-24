<aside class="side-mini-panel with-vertical sidebar-google-style">
    <div style="height: 100vh; display: flex; flex-direction: column; overflow: hidden;">
        <!-- ---------------------------------- -->
        <!-- Start Vertical Layout Sidebar -->
        <!-- ---------------------------------- -->
        <div class="iconbar" style="flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0;">
            <div style="flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0;">
                <div class="mini-nav" style="flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0;">
                    <div class="d-flex align-items-center justify-content-center sidebar-google-hamburger">
    <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)" data-bs-toggle="tooltip"
        data-bs-custom-class="custom-tooltip" data-bs-placement="right" aria-label="Toggle menu">

        <i id="sidebarToggleIcon" class="material-icons menu-icon material-symbols-rounded"
            style="font-size: 24px;">
            menu
        </i>

    </a>
</div>
                    <ul class="mini-nav-ul simplebar-scrollable-y" data-simplebar="init" style="flex: 1 1 auto; min-height: 0;">
                        <div class="simplebar-wrapper" style="margin: 0px;">
                            <div class="simplebar-height-auto-observer-wrapper">
                                <div class="simplebar-height-auto-observer"></div>
                            </div>

                            <div class="simplebar-mask">
                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                        aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                                        <div class="simplebar-content" style="padding: 0px;">
                                            <li class="mini-nav-item" id="setup-mini-4">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">dashboard_customize</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Academic</span>
                                                </a>
                                            </li>

                                            @if(hasRole('Admin') || hasRole('Training-Induction') ||  hasRole('Training-MCTP') || hasRole('IST'))
                                            <li class="mini-nav-item" id="setup-mini-5">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">calendar_month</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Time Table</span>
                                                </a>
                                            </li>
                                            
                                            <li class="mini-nav-item" id="setup-mini-6">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">user_attributes</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Users</span>
                                                </a>
                                            </li>
                                            @if(! hasRole('Training-MCTP') && ! hasRole('IST'))
                                            <li class="mini-nav-item" id="setup-mini-7">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">menu_open</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Master</span>
                                                </a>
                                            </li>
                                            <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}" id="mini-3">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">note_add</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">FC Forms</span>
                                                </a>
                                            </li>
                                           
                                            @endif

                                            @endif
                                        @if(! hasRole('Student-OT'))
                                             <li class="mini-nav-item {{ request()->is('admin/issue-management*') || request()->is('admin/issue-categories*') || request()->is('admin/issue-sub-categories*') ? 'selected' : '' }}" id="mini-10">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">report_problem</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Centcom</span>
                                                </a>
                                            </li>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="simplebar-placeholder" style="width: 80px; min-width: 80px; height: 537px;"></div>
                        </div>
                        <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                            <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                        </div>
                        <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                            <div class="simplebar-scrollbar"
                                style="height: 75px; display: block; transform: translate3d(0px, 0px, 0px);">
                            </div>
                        </div>
                    </ul>

                </div>
                <div class="sidebarmenu">
                    <!-- ---------------------------------- -->
                    <!-- Academic -->
                    <!-- ---------------------------------- -->
                    <x-menu.setup_academic />

                    <!-- ---------------------------------- -->
                    <!-- Academic -->
                    <!-- ---------------------------------- -->
                    <x-menu.setup_general />

                    <!-- ---------------------------------- -->
                    <!-- Academic -->
                    <!-- ---------------------------------- -->
                    <x-menu.setup_activities />


                    <!-- ---------------------------------- -->
                    <!-- Academic -->
                    <!-- ---------------------------------- -->
                    <x-menu.setup_mappings />

                    <!-- Forms -->
                    <!-- ---------------------------------- -->
                    <x-menu.fc-sidebar />

                    <!-- Issue Management (CENTCOM) -->
                    <!-- ---------------------------------- -->
                    <x-menu.setup_issue_management />

                </div>
            </div>
        </div>
    </div>
</aside>

<style>
/* Google-style sidebar - light gray, icon above text, oval selected state */
#sidebar-setup .sidebar-google-style.side-mini-panel {
    width: 90px;
}
#sidebar-setup .sidebar-google-style .mini-nav {
    background: #F0F0F0 !important;
    padding: 12px 0;
    border-radius: 10px;
}
#sidebar-setup .sidebar-google-style .sidebar-google-hamburger {
    padding: 16px 0;
    margin: 0;
}
#sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebartoggler {
    color: #555 !important;
}
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item {
    list-style: none;
    display: flex !important;
    justify-content: center !important;
}
#sidebar-setup .sidebar-google-style .mini-nav ul.mini-nav-ul {
    padding-inline-start: 0 !important;
    list-style: none !important;
}
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item > a {
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 12px 8px !important;
    padding-left: 8px !important;
    margin: 4px 8px !important;
    background: transparent !important;
    height: auto !important;
    min-height: 56px;
    width: 100%;
}
#sidebar-setup .sidebar-google-style .sidebar-google-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px;
    text-align: center !important;
}
#sidebar-setup .sidebar-google-style .sidebar-google-icon-wrap {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 48px;
    height: 32px;
    margin-inline: auto;
    border-radius: 24px;
    transition: background 0.2s;
}
#sidebar-setup .sidebar-google-style .sidebar-google-icon-wrap .material-icons {
    line-height: 1 !important;
    vertical-align: middle !important;
}
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item > a .material-icons {
    font-size: 24px !important;
    color: #555 !important;
}
#sidebar-setup .sidebar-google-style .sidebar-google-label {
    font-size: 11px;
    color: #555 !important;
    font-weight: 400;
    text-align: center;
    line-height: 1.2;
}
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item > a:hover .material-icons,
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item > a:hover .sidebar-google-label {
    color: #333 !important;
}
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected > a .sidebar-google-icon-wrap {
    background: #E0E0E0 !important;
    border-radius: 10px;
    padding: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    transform: scale(1.05);
    margin: 4px 8px;
    width: 100%;
    height: 100%;
}
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected > a .material-icons,
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected > a .sidebar-google-label {
    color: #333 !important;
}
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected > a:before {
    display: none !important;
}
</style>

<script>
// Global function to collapse all menus
function collapseAllMenus() {
    const allCollapses = document.querySelectorAll('.sidebarmenu .collapse');
    allCollapses.forEach(collapse => {
        const bsCollapse = bootstrap.Collapse.getInstance(collapse);
        if (bsCollapse) {
            bsCollapse.hide();
        } else {
            collapse.classList.remove('show');
        }

        // Update the toggle button arrow
        const collapseId = collapse.id;
        const toggleBtn = document.querySelector(`[href="#${collapseId}"], [data-bs-target="#${collapseId}"]`);
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'false');
            toggleBtn.classList.add('collapsed');
            const icon = toggleBtn.querySelector('.material-icons');
            if (icon && icon.textContent.includes('keyboard_arrow_up')) {
                icon.textContent = 'keyboard_arrow_down';
            }
        }
    });
}

// Add accordion behavior - when one opens, others close
document.addEventListener('DOMContentLoaded', function() {
    const setupSidebar = document.getElementById('sidebar-setup');
    if (!setupSidebar) return;

    // Add accordion behavior to collapsible menus
    const collapseElements = setupSidebar.querySelectorAll('.sidebar-item [data-bs-toggle="collapse"]');
    collapseElements.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href') || this.getAttribute('data-bs-target');
            const targetCollapse = document.querySelector(targetId);

            // Find all collapse elements in the same parent container
            const parentNav = this.closest('.sidebar-nav');
            if (parentNav) {
                const allCollapses = parentNav.querySelectorAll('.collapse');
                allCollapses.forEach(collapse => {
                    if (collapse !== targetCollapse && collapse.classList.contains(
                            'show')) {
                        const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                        if (bsCollapse) {
                            bsCollapse.hide();
                        }
                    }
                });
            }

            // Rotate arrow icon
            const icon = this.querySelector('.material-icons');
            if (icon) {
                setTimeout(() => {
                    if (targetCollapse.classList.contains('show')) {
                        icon.textContent = 'keyboard_arrow_up';
                    } else {
                        icon.textContent = 'keyboard_arrow_down';
                    }
                }, 350);
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('Setup sidebar script started');
    // Scope to setup sidebar (in #sidebar-setup tab pane)
    const setupSidebar = document.getElementById('sidebar-setup');
    if (!setupSidebar) {
        console.error('Setup sidebar not found');
        return;
    }

        // Initialize mini-navbar functionality for setup ONLY
        const miniNavItems = setupSidebar.querySelectorAll('.mini-nav .mini-nav-item');
        const sidebarMenus = setupSidebar.querySelectorAll('.sidebarmenu nav');

        console.log('Found mini-nav items in setup tab:', miniNavItems.length);
        console.log('Found sidebar menus in setup tab:', sidebarMenus.length);

        // Function to manually find and mark active links based on current URL
        function markActiveLinks() {
            const currentUrl = window.location.href;
            console.log('Current URL:', currentUrl);

            sidebarMenus.forEach(function(nav) {
                const links = nav.querySelectorAll('.sidebar-link[href]');
                links.forEach(function(link) {
                    if (link.href === currentUrl) {
                        console.log('Found matching link:', link.href, 'in nav:', nav
                            .id);
                        link.classList.add('active');
                    }
                });
            });
        }

        // Function to keep sidebar menu visible for a few seconds
        function keepSidebarVisible(menuId, duration = 3000) {
            const targetMenu = document.getElementById(menuId);
            if (!targetMenu) return;
            let elapsed = 0;
            const interval = setInterval(function() {
                if (!targetMenu.classList.contains('d-block')) {
                    targetMenu.classList.add('d-block');
                }
                if (targetMenu.style.display !== 'block') {
                    targetMenu.style.display = 'block';
                }
                elapsed += 200;
                if (elapsed >= duration) {
                    clearInterval(interval);
                }
            }, 200);
        }

        // Function to show sidebar menu and save state
        function showSidebarMenu(miniId) {
            console.log('Showing sidebar for miniId:', miniId);
            // Remove selected from all mini-nav-items
            miniNavItems.forEach(function(navItem) {
                navItem.classList.remove('selected');
            });
            // Add selected only to the clicked/active one
            const selectedItem = document.getElementById(miniId);
            if (selectedItem) {
                selectedItem.classList.add('selected');
                console.log('Selected mini-nav item:', miniId);
            }
            sidebarMenus.forEach(function(nav) {
                nav.classList.remove('d-block');
                nav.style.display = 'none';
            });
            const targetMenuId = 'menu-right-' + miniId;
            const targetMenu = document.getElementById(targetMenuId);
            if (targetMenu) {
                targetMenu.classList.add('d-block');
                targetMenu.style.display = 'block';
                document.body.setAttribute('data-sidebartype', 'full');
                console.log('Displayed menu:', targetMenu.id);
                // Periodically keep sidebar visible for 3 seconds
                keepSidebarVisible(targetMenuId, 3000);
            } else {
                console.error('Target menu not found:', targetMenuId);
            }
            localStorage.setItem('selectedMiniNav', miniId);
            // Don't force tab switch - let user's navigation determine the active tab
        }

        // MutationObserver to keep sidebar visible
        sidebarMenus.forEach(function(nav) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (nav.classList.contains('d-block') && nav.style
                        .display !== 'block') {
                        nav.style.display = 'block';
                    }
                });
            });
            observer.observe(nav, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        });

        // Function to expand collapsed menus containing active links
        function expandActiveMenus() {
            console.log('Expanding active menus');
            sidebarMenus.forEach(function(nav) {
                if (!nav.classList.contains('d-block') && nav.style.display !== 'block') {
                    return;
                }
                const activeLinks = nav.querySelectorAll('.sidebar-link.active');
                console.log('Found active links in', nav.id, ':', activeLinks.length);
                activeLinks.forEach(function(activeLink) {
                    console.log('Processing active link:', activeLink.textContent
                        .trim());
                    let parent = activeLink.closest('.collapse');
                    while (parent) {
                        console.log('Expanding collapse:', parent.id);
                        parent.classList.add('show', 'in');
                        parent.style.display = 'block';
                        const collapseId = parent.id;
                        const toggleBtn = nav.querySelector(
                            `[href="#${collapseId}"], [data-bs-target="#${collapseId}"]`
                        );
                        if (toggleBtn) {
                            console.log('Found toggle button for:', collapseId);
                            toggleBtn.setAttribute('aria-expanded', 'true');
                            toggleBtn.classList.remove('collapsed');
                        }
                        parent = parent.parentElement.closest('.collapse');
                    }
                });
            });
        }

        // Mark active links first
        markActiveLinks();

        // Note: Mini-nav click handling is done globally by sidebar-navigation-fixed.js
        // No need to add event listeners here to avoid duplicate handlers

        // Function to restore sidebar menu visibility
        function restoreSidebarMenu() {
            // Always remove selected from all mini-nav-items first
            miniNavItems.forEach(function(navItem) {
                navItem.classList.remove('selected');
            });
            let activeMiniId = null;
            sidebarMenus.forEach(function(nav) {
                const activeLink = nav.querySelector('.sidebar-link.active');
                if (activeLink) {
                    const navId = nav.id;
                    activeMiniId = navId.replace('menu-right-', '');
                }
            });
            if (activeMiniId) {
                showSidebarMenu(activeMiniId);
                setTimeout(function() {
                    expandActiveMenus();
                }, 100);
                // Only activate setup tab if it's not already active
                setTimeout(function() {
                    const setupTabPane = document.getElementById('tab-setup');
                    if (setupTabPane && setupTabPane.classList.contains('active')) {
                        // Already on setup tab, just ensure it stays active
                        const setupTabLink = document.querySelector('a[href="#tab-setup"]');
                        if (setupTabLink) {
                            setupTabLink.classList.add('active');
                        }
                    }
                }, 150);
            } else {
                const savedMiniId = localStorage.getItem('selectedMiniNav');
                if (savedMiniId && document.getElementById(savedMiniId)) {
                    showSidebarMenu(savedMiniId);
                    setTimeout(expandActiveMenus, 100);
                } else {
                    // Only one selected from server, if any
                    const hasSelected = setupSidebar.querySelector('.mini-nav .mini-nav-item.selected');
                    if (hasSelected) {
                        // Remove selected from all, add only to this one
                        miniNavItems.forEach(function(navItem) {
                            navItem.classList.remove('selected');
                        });
                        hasSelected.classList.add('selected');
                        showSidebarMenu(hasSelected.id);
                        setTimeout(expandActiveMenus, 100);
                    } else if (miniNavItems.length > 0) {
                        showSidebarMenu(miniNavItems[0].id);
                    }
                }
            }
        }

        // Initial restore on page load
        restoreSidebarMenu();

        // Listen for tab switches (Bootstrap)
        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tabLink) {
            tabLink.addEventListener('shown.bs.tab', function(e) {
                if (e.target.getAttribute('href') === '#tab-setup') {
                    setTimeout(restoreSidebarMenu, 100);
                }
            });
        });

        // Listen for window focus
        window.addEventListener('focus', function() {
            setTimeout(restoreSidebarMenu, 100);
        });
});
</script>