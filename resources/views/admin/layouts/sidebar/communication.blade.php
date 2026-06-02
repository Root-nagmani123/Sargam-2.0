
<aside class="side-mini-panel with-vertical sidebar-google-style" id="sidebar-communication">
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
                    <ul class="mini-nav-ul simplebar-scrollable-y flex-fill" data-simplebar="init"
                        style="min-height: 0;">
                        <div class="simplebar-wrapper" style="margin: 0px;">
                            <div class="simplebar-height-auto-observer-wrapper">
                                <div class="simplebar-height-auto-observer"></div>
                            </div>

                            <div class="simplebar-mask">
                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                        aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                                        <div class="simplebar-content" style="padding: 0px;">
                                            @include('admin.layouts.sidebar.partials.mini-sidebar-toggle')
                                            <li class="mini-nav-item {{ request()->routeIs('admin.birthday-wish.*', 'admin.word-of-day.*', 'admin.login-carousel-images.*') ? 'selected' : '' }}"
                                                id="mini-12">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item sidebar-mini-squircle-item">
                                                    <span class="sidebar-mini-squircle-box">
                                                        <i class="material-icons menu-icon material-symbols-rounded">apps</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-mini-squircle-label">General</span>
                                                </a>
                                            </li>


                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="simplebar-placeholder" style="width: 80px; min-width: 80px; height: 537px;">
                            </div>
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
                    <!-- Communication -->
                    <!-- ---------------------------------- -->
                    <x-menu.communication_setup />
                </div>
        </div>
    </div>
</aside>

<style>
/* Section headers in fly-out menu (matches home / general menu pill style) */
#sidebar-communications .comm-sidebar-section-heading {
    background: #4077ad;
    border-radius: 30px 0 0 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;
    list-style: none;
}
#sidebar-communications .comm-sidebar-section-heading .sidebar-link {
    color: rgba(255, 255, 255, 0.95) !important;
}
#sidebar-communications .comm-sidebar-section-heading .material-icons {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 1.25rem !important;
}
#sidebar-communications .comm-sidebar-section-heading + .collapse .sidebar-link {
    border-radius: 0.375rem;
    transition: background-color 0.15s ease, color 0.15s ease;
}
#sidebar-communications .comm-sidebar-section-heading + .collapse .sidebar-link:hover {
    background-color: var(--bs-primary-bg-subtle);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarComm = document.getElementById('sidebar-communication');
    if (!sidebarComm) {
        console.error('Communication sidebar not found');
        return;
    }

    const miniNavItems = sidebarComm.querySelectorAll('.mini-nav .mini-nav-item');
    const sidebarMenus = sidebarComm.querySelectorAll('.sidebarmenu nav');

    function markActiveLinks() {
        const currentUrl = window.location.href;
        sidebarMenus.forEach(function(nav) {
            const links = nav.querySelectorAll('.sidebar-link[href]');
            links.forEach(function(link) {
                const href = link.getAttribute('href');
                if (!href || href === '#' || href === 'javascript:void(0)') return;
                if (link.href === currentUrl) {
                    link.classList.add('active');
                }
            });
        });
    }

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

    function showSidebarMenu(miniId) {
        miniNavItems.forEach(function(navItem) {
            navItem.classList.remove('selected');
        });
        const selectedItem = document.getElementById(miniId);
        if (selectedItem) {
            selectedItem.classList.add('selected');
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
            keepSidebarVisible(targetMenuId, 3000);
        }
        localStorage.setItem('selectedCommunicationMiniNav', miniId);
    }

    sidebarMenus.forEach(function(nav) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function() {
                if (nav.classList.contains('d-block') && nav.style.display !== 'block') {
                    nav.style.display = 'block';
                }
            });
        });
        observer.observe(nav, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });

    function expandActiveMenus() {
        sidebarMenus.forEach(function(nav) {
            if (!nav.classList.contains('d-block') && nav.style.display !== 'block') {
                return;
            }
            const activeLinks = nav.querySelectorAll('.sidebar-link.active');
            activeLinks.forEach(function(activeLink) {
                let parent = activeLink.closest('.collapse');
                while (parent) {
                    parent.classList.add('show', 'in');
                    parent.style.display = 'block';
                    const collapseId = parent.id;
                    const toggleBtn = nav.querySelector(
                        `[href="#${collapseId}"], [data-bs-target="#${collapseId}"]`
                    );
                    if (toggleBtn) {
                        toggleBtn.setAttribute('aria-expanded', 'true');
                        toggleBtn.classList.remove('collapsed');
                    }
                    parent = parent.parentElement.closest('.collapse');
                }
            });
        });
    }

    markActiveLinks();

    function restoreSidebarMenu() {
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
            const savedMiniId = localStorage.getItem('selectedCommunicationMiniNav');
            if (savedMiniId && document.getElementById(savedMiniId)) {
                showSidebarMenu(savedMiniId);
                setTimeout(expandActiveMenus, 100);
            } else if (miniNavItems.length > 0) {
                showSidebarMenu(miniNavItems[0].id);
            }
        }
    }

    restoreSidebarMenu();

    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tabLink) {
        tabLink.addEventListener('shown.bs.tab', function(e) {
            if (e.target.getAttribute('href') === '#tab-communications') {
                setTimeout(restoreSidebarMenu, 100);
            }
        });
    });

    window.addEventListener('focus', function() {
        setTimeout(restoreSidebarMenu, 100);
    });
});
</script>
