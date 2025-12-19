 <aside class="side-mini-panel with-vertical">
     <div style="height: 100vh; display: flex; flex-direction: column;">
         <!-- ---------------------------------- -->
         <!-- Start Vertical Layout Sidebar -->
         <!-- ---------------------------------- -->
         <div class="iconbar" style="flex: 1 1 auto; display: flex; flex-direction: column;">
             <div style="flex: 1 1 auto; display: flex; flex-direction: column;">
                 <div class="mini-nav" style="flex: 1 1 auto; display: flex; flex-direction: column;">
                     <ul class="mini-nav-ul simplebar-scrollable-y" data-simplebar="init" style="flex: 1 1 auto;">
                         <div class="simplebar-wrapper" style="margin: 0px;">
                             <div class="simplebar-height-auto-observer-wrapper">
                                 <div class="simplebar-height-auto-observer"></div>
                             </div>
                             <div class="simplebar-mask">
                                 <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                     <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                         aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                                         <div class="simplebar-content" style="padding: 0px;">

                                             @include('components.profile')


                                             <li class="mini-nav-item {{ (request()->routeIs('admin.dashboard') || request()->is('dashboard')) ? 'selected' : '' }}"
                                                 id="mini-1">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right" data-bs-title="General">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded"
                                                             style="font-size: 32px;">apps</i>
                                                         <span
                                                             class="mini-nav-title fs-4 fw-medium text-dark">General</span>
                                                     </div>

                                                     <i class="material-icons material-symbols-rounded"
                                                         style="font-size: 20px;">chevron_right</i>
                                                 </a>
                                             </li>

                                         </div>
                                     </div>
                                 </div>
                             </div>
                             <div class="simplebar-placeholder" style="width: 80px; height: 537px;"></div>
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

                 </div>
             </div>
         </div>
     </div>
 </aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Home sidebar script started');
    const isDashboard = {{ (request()->routeIs('admin.dashboard') || request()->is('dashboard')) ? 'true' : 'false' }};
    // Scope to ONLY the home tab
    const homeTab = document.getElementById('home');
    if (!homeTab) {
        console.error('Home tab not found');
        return;
    }

        // Initialize mini-navbar functionality for home ONLY
        const miniNavItems = homeTab.querySelectorAll('.mini-nav .mini-nav-item');
        const sidebarMenus = homeTab.querySelectorAll('.sidebarmenu nav');

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

        // Ensure single-click opens the corresponding sidebar via event delegation
        const miniNav = homeTab.querySelector('.mini-nav');
        if (miniNav) {
            miniNav.addEventListener('click', function(e) {
                const li = e.target.closest('.mini-nav-item');
                if (li && miniNav.contains(li)) {
                    e.preventDefault();
                    showSidebarMenu(li.id);
                }
            });
            // Explicitly bind anchor clicks to ensure single-click behavior
            const anchorLinks = miniNav.querySelectorAll('.mini-nav-item > a.mini-nav-link');
            anchorLinks.forEach(function(anchor) {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const li = this.closest('.mini-nav-item');
                    if (li) {
                        showSidebarMenu(li.id);
                    }
                });
            });
        }

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