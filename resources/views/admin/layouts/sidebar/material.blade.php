<aside class="side-mini-panel with-vertical sidebar-google-style" id="sidebar-material">
    <div class="vh-100 d-flex flex-column overflow-hidden">
        <div class="iconbar sargam-sidebar-iconbar flex-fill d-flex flex-row align-items-stretch" style="min-height: 0;">
            <div class="mini-nav sargam-mini-nav flex-shrink-0 d-flex flex-column" style="min-height: 0;">
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
                                        <li class="mini-nav-item selected" id="material-mini-1">
                                            <a href="javascript:void(0)"
                                                class="mini-nav-link sidebar-google-item sidebar-mini-squircle-item">
                                                <span class="sidebar-mini-squircle-box">
                                                    <i class="material-icons menu-icon material-symbols-rounded">inventory_2</i>
                                                </span>
                                                <span class="mini-nav-title sidebar-mini-squircle-label">Material</span>
                                            </a>
                                        </li>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ul>
            </div>
            <div class="sidebarmenu flex-fill min-vw-0 d-flex flex-column" style="min-height: 0;">
                <x-menu.material_management />
            </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarMaterial = document.getElementById('sidebar-material');
    if (!sidebarMaterial) return;

    const miniNavItems = sidebarMaterial.querySelectorAll('.mini-nav .mini-nav-item');
    const sidebarMenus = sidebarMaterial.querySelectorAll('.sidebarmenu nav');

    function markActiveLinks() {
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

    function showSidebarMenu(miniId) {
        miniNavItems.forEach(function(navItem) {
            navItem.classList.remove('selected');
        });
        const selectedItem = document.getElementById(miniId);
        if (selectedItem) {
            selectedItem.classList.add('selected');
        }
        const targetMenu = document.getElementById('menu-right-' + miniId);
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
        }
        localStorage.setItem('selectedMaterialMiniNav', miniId);
    }

    function expandActiveMenus() {
        sidebarMenus.forEach(function(nav) {
            if (!nav.classList.contains('d-block') && nav.style.display !== 'block') return;
            nav.querySelectorAll('.sidebar-link.active').forEach(function(activeLink) {
                let parent = activeLink.closest('.collapse');
                while (parent) {
                    parent.classList.add('show');
                    const collapseId = parent.id;
                    const toggleBtn = nav.querySelector('[href="#' + collapseId + '"], [data-bs-target="#' + collapseId + '"]');
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
        let activeMiniId = null;
        sidebarMenus.forEach(function(nav) {
            if (nav.querySelector('.sidebar-link.active')) {
                activeMiniId = nav.id.replace('menu-right-', '');
            }
        });
        if (!activeMiniId) {
            activeMiniId = localStorage.getItem('selectedMaterialMiniNav') || (miniNavItems[0] && miniNavItems[0].id);
        }
        if (activeMiniId) {
            showSidebarMenu(activeMiniId);
            setTimeout(expandActiveMenus, 100);
        }
    }

    restoreSidebarMenu();

    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tabLink) {
        tabLink.addEventListener('shown.bs.tab', function(e) {
            if (e.target.getAttribute('href') === '#tab-material-management') {
                setTimeout(restoreSidebarMenu, 100);
            }
        });
    });
});
</script>
