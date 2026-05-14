@php
    $isContractualEmployee = false;
    $authUser = \Illuminate\Support\Facades\Auth::user();
    if ($authUser && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'payroll')) {
        $userId = $authUser->user_id ?? $authUser->pk ?? null;
        if ($userId) {
            $emp = \Illuminate\Support\Facades\DB::table('employee_master')
                ->where('pk', $userId)
                ->orWhere('pk_old', $userId)
                ->first(['payroll']);
            $isContractualEmployee = $emp && (int) ($emp->payroll ?? 0) !== 0;
        }
    }
@endphp
<aside class="side-mini-panel with-vertical sidebar-google-style" id="mainSidebar">
    <div class="vh-100 d-flex flex-column overflow-hidden">
        <!-- ---------------------------------- -->
        <!-- Start Vertical Layout Sidebar -->
        <!-- ---------------------------------- -->
        <div class="iconbar flex-fill d-flex flex-column" style="min-height: 0;">
            <div class="flex-fill d-flex flex-column" style="min-height: 0;">
                <div class="mini-nav flex-fill d-flex flex-column" style="min-height: 0;">
                    <div class="d-flex align-items-center justify-content-center sidebar-google-hamburger">
    <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)" data-bs-toggle="tooltip"
        data-bs-custom-class="custom-tooltip" data-bs-placement="right" aria-label="Toggle menu">

        <i id="sidebarToggleIcon" class="material-icons menu-icon material-symbols-rounded fs-4">
            menu
        </i>

    </a>
</div>
                    <ul class="mini-nav-ul simplebar-scrollable-y flex-fill" data-simplebar="init" style="min-height: 0;">
                        <div class="simplebar-wrapper" style="margin: 0px;">
                            <div class="simplebar-height-auto-observer-wrapper">
                                <div class="simplebar-height-auto-observer"></div>
                            </div>

                            <div class="simplebar-mask">
                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                        aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                                        <div class="simplebar-content" style="padding: 0px;">
                                            <li class="mini-nav-item {{ (request()->routeIs('admin.dashboard') || request()->is('dashboard')) ? 'selected' : '' }}"
                                                id="mini-1">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">apps</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">General</span>
                                                </a>
                                            </li>
                                            @if(hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST') || hasRole('Estate') || hasRole('HAC Person') || hasRole('Staff') || hasRole('Student-OT') || hasRole('Doctor') || hasRole('Guest Faculty') || hasRole('Internal Faculty'))
                                            <li class="mini-nav-item {{ request()->is('admin/estate*') ? 'selected' : '' }}" id="mini-11">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">house</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Estate Management</span>
                                                </a>
                                            </li>
                                            @endif
                                            @if(canSeeMessSelfServiceSetup())
                                            <li class="mini-nav-item {{ request()->is('admin/mess*') ? 'selected' : '' }}" id="setup-mini-9">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">restaurant_menu</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Mess Management</span>
                                                </a>
                                            </li>
                                            @endif
                                            @if(! hasRole('Student-OT') && ! $isContractualEmployee)
                                            <li class="mini-nav-item {{ request()->is('security*') ? 'selected' : '' }}" id="mini-9">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3">
                                                    <span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">shield</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">Security</span>
                                                </a>
                                            </li>
                                            <li class="mini-nav-item {{ request()->is('admin/issue-management*') || request()->is('admin/issue-categories*') || request()->is('admin/issue-sub-categories*') ? 'selected' : '' }}" id="mini-10">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3">
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
                    <!-- Dashboard -->
                    <!-- ---------------------------------- -->
                    <x-menu.general />
                    <x-menu.setup_estate_management />
                    <x-menu.setup_mess_management />
                    @if(! hasRole('Student-OT') && ! $isContractualEmployee)
                    <x-menu.setup_security_management />
                    <x-menu.setup_issue_management />
                    @endif

                </div>
            </div>
        </div>
    </div>
</aside>

<style>
/* Google-style sidebar - home (matches setup) */
#sidebar-home .sidebar-google-style.side-mini-panel {
    width: 90px;
}
#sidebar-home .sidebar-google-style .mini-nav {
    background: #f0f0f0 !important;
    border: 1px solid var(--bs-border-color-translucent);
    padding: 12px 0;
    border-radius: 10px;
}
#sidebar-home .sidebar-google-style .sidebar-google-hamburger {
    padding: 16px 0;
    margin: 0;
}
#sidebar-home .sidebar-google-style .sidebar-google-hamburger .sidebartoggler {
    color: var(--bs-secondary-color) !important;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item {
    list-style: none;
    display: flex !important;
    justify-content: center !important;
}
#sidebar-home .sidebar-google-style .mini-nav ul.mini-nav-ul {
    padding-inline-start: 0 !important;
    list-style: none !important;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item > a {
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
    transition: background-color 0.2s ease, color 0.2s ease;
}
#sidebar-home .sidebar-google-style .sidebar-google-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px;
    text-align: center !important;
}
#sidebar-home .sidebar-google-style .sidebar-google-icon-wrap {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 48px;
    height: 32px;
    margin-inline: auto;
    border-radius: 24px;
    transition: background 0.2s;
}
#sidebar-home .sidebar-google-style .sidebar-google-icon-wrap .material-icons {
    line-height: 1 !important;
    vertical-align: middle !important;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item > a .material-icons {
    font-size: 24px !important;
    color: var(--bs-secondary-color) !important;
}
#sidebar-home .sidebar-google-style .sidebar-google-label {
    font-size: 11px;
    color: var(--bs-secondary-color) !important;
    font-weight: 400;
    text-align: center;
    line-height: 1.2;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item > a:hover .material-icons,
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item > a:hover .sidebar-google-label {
    color: var(--bs-emphasis-color) !important;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item > a:focus-visible {
    outline: 2px solid rgba(var(--bs-primary-rgb), 0.35);
    outline-offset: 2px;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item.selected > a .sidebar-google-icon-wrap {
    background: var(--bs-primary-bg-subtle) !important;
    border-radius: 10px;
    padding: 10px;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.16);
    transition: all 0.2s ease;
    transform: scale(1.05);
    margin: 4px 8px;
    width: 100%;
    height: 100%;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item.selected > a .material-icons,
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item.selected > a .sidebar-google-label {
    color: var(--bs-primary-text-emphasis) !important;
}
#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item.selected > a:before {
    display: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Home sidebar script started');
    const isDashboard = {{ (request()->routeIs('admin.dashboard') || request()->is('dashboard')) ? 'true' : 'false' }};
    // Scope to home sidebar (in #sidebar-home tab pane)
    const sidebarHome = document.getElementById('sidebar-home');
    if (!sidebarHome) {
        console.error('Home sidebar not found');
        return;
    }

        // Initialize mini-navbar functionality for home ONLY
        const miniNavItems = sidebarHome.querySelectorAll('.mini-nav .mini-nav-item');
        const sidebarMenus = sidebarHome.querySelectorAll('.sidebarmenu nav');

        console.log('Found mini-nav items in home tab:', miniNavItems.length);
        console.log('Found sidebar menus in home tab:', sidebarMenus.length);

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

        // Function to keep sidebar menu visible
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
                // Periodically keep sidebar visible
                keepSidebarVisible(targetMenuId, 3000);
            } else {
                console.error('Target menu not found:', targetMenuId);
            }
            localStorage.setItem('selectedHomeMiniNav', miniId);
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
            } else {
                const savedMiniId = localStorage.getItem('selectedHomeMiniNav');
                if (savedMiniId && document.getElementById(savedMiniId)) {
                    showSidebarMenu(savedMiniId);
                    setTimeout(expandActiveMenus, 100);
                } else if (miniNavItems.length > 0) {
                    // Default behavior
                    showSidebarMenu(miniNavItems[0].id);
                }
            }
        }

        // Initial restore on page load
        restoreSidebarMenu();

        // Listen for tab switches (Bootstrap)
        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tabLink) {
            tabLink.addEventListener('shown.bs.tab', function(e) {
                if (e.target.getAttribute('href') === '#home') {
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