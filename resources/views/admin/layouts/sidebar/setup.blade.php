<aside class="side-mini-panel with-vertical sidebar-google-style">
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
        $setupAcademicsActive = request()->routeIs('programme.*')
            || request()->routeIs('master.course.group.type.*')
            || request()->routeIs('group.mapping.*')
            || request()->routeIs('student.medical.exemption.*')
            || request()->routeIs('mdo-escrot-exemption.*')
            || request()->routeIs('mdo-escort-exemption.*')
            || request()->routeIs('master.exemption.*')
            || request()->routeIs('master.mdo_duty_type.*')
            || request()->routeIs('master.memo.*')
            || request()->routeIs('course.memo.*')
            || request()->routeIs('master.discipline.*')
            || request()->routeIs('admin.feedback.*')
            || request()->routeIs('feedback.*')
            || request()->routeIs('calendar.*')
            || request()->routeIs('attendance.*')
            || request()->routeIs('faculty.*')
            || request()->routeIs('medical.exception.*')
            || request()->routeIs('ot.*')
            || request()->routeIs('memo.*')
            || request()->routeIs('peer.*')
            || request()->routeIs('admin.course-repository.user.*')
            || request()->is('academic*');
    @endphp
    <div class="vh-100 d-flex flex-column overflow-hidden">
        <!-- ---------------------------------- -->
        <!-- Start Vertical Layout Sidebar -->
        <!-- ---------------------------------- -->
        <div class="iconbar sargam-sidebar-iconbar flex-fill d-flex flex-row align-items-stretch" style="min-height: 0;">
                <div class="mini-nav sargam-mini-nav flex-shrink-0 d-flex flex-column" style="min-height: 0; width: 90px;">
                    <div class="d-flex align-items-center justify-content-center sidebar-google-hamburger">
                        <a class="sidebar-mini-toggle sidebartoggler sidebar-mini-squircle-item nav-link p-0 border-0 bg-transparent shadow-none"
                            id="headerCollapse"
                            href="javascript:void(0)"
                            data-bs-toggle="tooltip"
                            data-bs-custom-class="custom-tooltip"
                            data-bs-placement="right"
                            aria-label="Toggle sidebar menu">
                            <span class="sidebar-mini-squircle-box sidebar-mini-squircle-box--neutral">
                                <i id="sidebarToggleIcon" class="material-icons material-symbols-rounded" aria-hidden="true">left_panel_close</i>
                            </span>
                            <span class="sidebar-mini-squircle-label sidebar-mini-toggle-label">
                                <span class="sidebar-mini-toggle-text-close">Close</span>
                                <span class="sidebar-mini-toggle-text-open">Open</span>
                            </span>
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
                                            <li class="mini-nav-item {{ $setupAcademicsActive ? 'selected' : '' }}" id="setup-mini-4">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item sidebar-mini-squircle-item">
                                                    <span class="sidebar-mini-squircle-box">
                                                        <i class="material-icons menu-icon material-symbols-rounded">school</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-mini-squircle-label">Academics</span>
                                                </a>
                                            </li>

                                            @if(
                                                hasRole('Super Admin')
                                                || hasRole('Training Induction Admin')
                                                || hasRole('Training MCTP Admin')
                                                || hasRole('Training IST')
                                            )
                                            <li class="mini-nav-item" id="setup-mini-5">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item sidebar-mini-squircle-item">
                                                    <span class="sidebar-mini-squircle-box">
                                                        <i class="material-icons menu-icon material-symbols-rounded">calendar_month</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-mini-squircle-label">Time Table</span>
                                                </a>
                                            </li>

                                            <li class="mini-nav-item {{ request()->is('users*') ? 'selected' : '' }}" id="setup-mini-6">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item sidebar-mini-squircle-item">
                                                    <span class="sidebar-mini-squircle-box">
                                                        <i class="material-icons menu-icon material-symbols-rounded">user_attributes</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-mini-squircle-label">Users</span>
                                                </a>
                                            </li>
                                            @endif
                                            @if(hasRole('Super Admin') || hasRole('Training Induction Admin') ||  hasRole('Training MCTP Admin') || hasRole('Training IST'))
                                            @if(! hasRole('Training MCTP Admin') && ! hasRole('Training IST'))
                                            <li class="mini-nav-item {{ request()->is('master*') ? 'selected' : '' }}" id="setup-mini-7">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item sidebar-mini-squircle-item">
                                                    <span class="sidebar-mini-squircle-box">
                                                        <i class="material-icons menu-icon material-symbols-rounded">menu_open</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-mini-squircle-label">Master</span>
                                                </a>
                                            </li>
                                            <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}" id="mini-3">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item sidebar-mini-squircle-item">
                                                    <span class="sidebar-mini-squircle-box">
                                                        <i class="material-icons menu-icon material-symbols-rounded">note_add</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-mini-squircle-label">FC Forms</span>
                                                </a>
                                            </li>
                                            @endif

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
                <div class="sidebarmenu flex-fill min-vw-0 d-flex flex-column" style="min-height: 0;">
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

                    <!-- Security Management (Vehicle Pass) -->
                    <!-- ---------------------------------- -->
                    <!-- Estate Management -->
                    <!-- ---------------------------------- -->

                </div>
        </div>
    </div>
</aside>

<script>
    function collapseAllMenus() {
        const setupSidebar = document.getElementById('sidebar-setup');
        if (!setupSidebar) return;
        setupSidebar.querySelectorAll('nav.sidebar-panel-menu').forEach(function (nav) {
            if (typeof window.collapseSidebarPanelNav === 'function') {
                window.collapseSidebarPanelNav(nav);
            }
        });
    }

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
        const targetMenuId = 'menu-right-' + miniId;
        const targetMenu = document.getElementById(targetMenuId);
        if (targetMenu) {
            if (typeof window.activateSidebarPanelNav === 'function') {
                window.activateSidebarPanelNav(targetMenu);
            } else {
                sidebarMenus.forEach(function(nav) {
                    nav.classList.remove('d-block', 'is-active-panel');
                    nav.style.display = 'none';
                });
                targetMenu.classList.add('d-block', 'is-active-panel');
                targetMenu.style.display = 'flex';
            }
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
                    if (parent.getAttribute('data-sidebar-no-auto-expand') === 'true') {
                        parent = parent.parentElement.closest('.collapse');
                        continue;
                    }
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
            const serverSelected = setupSidebar.querySelector('.mini-nav-item.selected');
            if (serverSelected) {
                showSidebarMenu(serverSelected.id);
                setTimeout(expandActiveMenus, 100);
                return;
            }

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