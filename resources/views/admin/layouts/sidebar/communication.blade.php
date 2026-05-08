
<aside class="side-mini-panel with-vertical sidebar-google-style" id="sidebar-communication">
    <div class="vh-100 d-flex flex-column overflow-hidden">
        <!-- ---------------------------------- -->
        <!-- Start Vertical Layout Sidebar -->
        <!-- ---------------------------------- -->
        <div class="iconbar flex-fill d-flex flex-column" style="min-height: 0;">
            <div class="flex-fill d-flex flex-column" style="min-height: 0;">
                <div class="mini-nav flex-fill d-flex flex-column" style="min-height: 0;">
                    <div class="d-flex align-items-center justify-content-center sidebar-google-hamburger">
                        <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)"
                            data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                            aria-label="Toggle menu">

                            <i id="sidebarToggleIcon" class="material-icons menu-icon material-symbols-rounded fs-4">
                                menu
                            </i>

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
                                            <li class="mini-nav-item {{ request()->routeIs('admin.birthday-wish.*', 'admin.word-of-day.*') ? 'selected' : '' }}"
                                                id="mini-12">
                                                <a href="javascript:void(0)"
                                                    class="mini-nav-link sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3">
                                                    <span
                                                        class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                        <i class="material-icons menu-icon material-symbols-rounded">apps</i>
                                                    </span>
                                                    <span class="mini-nav-title sidebar-google-label">General</span>
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
                <div class="sidebarmenu">
                    <!-- ---------------------------------- -->
                    <!-- Communication -->
                    <!-- ---------------------------------- -->
                    <x-menu.communication_setup />
                </div>
            </div>
        </div>
    </div>
</aside>

<style>
/* Google-style sidebar — communications tab pane (#sidebar-communications), same language as home */
#sidebar-communications .sidebar-google-style.side-mini-panel {
    width: 90px;
}
#sidebar-communications .sidebar-google-style .mini-nav {
    background: #f0f0f0 !important;
    border: 1px solid var(--bs-border-color-translucent);
    padding: 12px 0;
    border-radius: 10px;
}
#sidebar-communications .sidebar-google-style .sidebar-google-hamburger {
    padding: 16px 0;
    margin: 0;
}
#sidebar-communications .sidebar-google-style .sidebar-google-hamburger .sidebartoggler {
    color: var(--bs-secondary-color) !important;
}
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item {
    list-style: none;
    display: flex !important;
    justify-content: center !important;
}
#sidebar-communications .sidebar-google-style .mini-nav ul.mini-nav-ul {
    padding-inline-start: 0 !important;
    list-style: none !important;
}
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item > a {
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
#sidebar-communications .sidebar-google-style .sidebar-google-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px;
    text-align: center !important;
}
#sidebar-communications .sidebar-google-style .sidebar-google-icon-wrap {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 48px;
    height: 32px;
    margin-inline: auto;
    border-radius: 24px;
    transition: background 0.2s;
}
#sidebar-communications .sidebar-google-style .sidebar-google-icon-wrap .material-icons {
    line-height: 1 !important;
    vertical-align: middle !important;
}
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item > a .material-icons {
    font-size: 24px !important;
    color: var(--bs-secondary-color) !important;
}
#sidebar-communications .sidebar-google-style .sidebar-google-label {
    font-size: 11px;
    color: var(--bs-secondary-color) !important;
    font-weight: 400;
    text-align: center;
    line-height: 1.2;
}
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item > a:hover .material-icons,
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item > a:hover .sidebar-google-label {
    color: var(--bs-emphasis-color) !important;
}
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item > a:focus-visible {
    outline: 2px solid rgba(var(--bs-primary-rgb), 0.35);
    outline-offset: 2px;
}
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item.selected > a .sidebar-google-icon-wrap {
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
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item.selected > a .material-icons,
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item.selected > a .sidebar-google-label {
    color: var(--bs-primary-text-emphasis) !important;
}
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item.selected > a:before {
    display: none !important;
}

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
        if (typeof window.sargamMarkSidebarActiveLinks === 'function') {
            window.sargamMarkSidebarActiveLinks(sidebarMenus);
        } else {
            const currentUrl = window.location.href;
            sidebarMenus.forEach(function(nav) {
                nav.querySelectorAll('.sidebar-link[href]').forEach(function(link) {
                    const href = link.getAttribute('href');
                    if (!href || href === '#' || href === 'javascript:void(0)') return;
                    if (link.href === currentUrl) {
                        link.classList.add('active');
                    }
                });
            });
        }
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
