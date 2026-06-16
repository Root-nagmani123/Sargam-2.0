/**
 * Sidebar panel menu: accordion (one sibling open at a time) + all collapsed by default.
 * Only ONE mini-nav panel visible per .sidebarmenu (no stacked ACADEMICS + TIME TABLE).
 */
(function () {
    'use strict';

    function getCollapseTrigger(collapseEl) {
        const id = collapseEl && collapseEl.id;
        if (!id) return null;
        const nav = collapseEl.closest('nav.sidebar-panel-menu');
        const scope = nav || document;
        return scope.querySelector(
            '[href="#' + CSS.escape(id) + '"], [data-bs-target="#' + CSS.escape(id) + '"]'
        );
    }

    function resetCollapseTrigger(trigger) {
        if (!trigger) return;
        trigger.setAttribute('aria-expanded', 'false');
        trigger.classList.add('collapsed');
    }

    function openCollapseTrigger(trigger) {
        if (!trigger) return;
        trigger.setAttribute('aria-expanded', 'true');
        trigger.classList.remove('collapsed');
    }

    function hideCollapseElement(collapseEl) {
        if (!collapseEl) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            const inst = bootstrap.Collapse.getInstance(collapseEl);
            if (inst) {
                inst.hide();
            } else {
                collapseEl.classList.remove('show');
            }
        } else {
            collapseEl.classList.remove('show');
        }
        resetCollapseTrigger(getCollapseTrigger(collapseEl));
    }

    function showCollapseElement(collapseEl) {
        if (!collapseEl) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show();
        } else {
            collapseEl.classList.add('show');
        }
        openCollapseTrigger(getCollapseTrigger(collapseEl));
    }

    function closeSiblingCollapses(collapseEl) {
        const trigger = getCollapseTrigger(collapseEl);
        if (!trigger) return;
        const triggerLi = trigger.closest('li.sidebar-item');
        if (!triggerLi || !triggerLi.parentElement) return;

        const parentList = triggerLi.parentElement;
        parentList.querySelectorAll(':scope > li.sidebar-item [data-bs-toggle="collapse"]').forEach(function (otherTrigger) {
            if (otherTrigger === trigger) return;
            const raw = otherTrigger.getAttribute('href') || otherTrigger.getAttribute('data-bs-target') || '';
            const otherId = raw.replace(/^#/, '');
            if (!otherId) return;
            const otherCollapse = document.getElementById(otherId);
            if (otherCollapse && otherCollapse !== collapseEl && otherCollapse.classList.contains('show')) {
                hideCollapseElement(otherCollapse);
            }
        });
    }

    function deactivatePanelNav(nav) {
        if (!nav) return;
        nav.classList.remove('d-block', 'is-active-panel');
        nav.style.display = 'none';
        nav.setAttribute('aria-hidden', 'true');
    }

    function activatePanelNav(nav) {
        if (!nav) return;
        const sidebarmenu = nav.closest('.sidebarmenu');
        if (sidebarmenu) {
            sidebarmenu.querySelectorAll('nav.sidebar-panel-menu').forEach(function (otherNav) {
                if (otherNav !== nav) {
                    deactivatePanelNav(otherNav);
                }
            });
        }
        nav.classList.add('d-block', 'is-active-panel');
        nav.style.display = 'flex';
        nav.setAttribute('aria-hidden', 'false');
    }

    /**
     * Show one panel by mini-nav id (e.g. setup-mini-4) within optional pane root.
     */
    function activateSidebarPanelByMiniId(miniId, paneRoot) {
        if (!miniId) return null;
        const root = paneRoot || document;
        const targetMenuId = 'menu-right-' + miniId;
        let targetMenu = root.querySelector('#' + CSS.escape(targetMenuId));
        if (!targetMenu) {
            targetMenu = document.getElementById(targetMenuId);
        }
        if (targetMenu) {
            activatePanelNav(targetMenu);
        }
        return targetMenu;
    }

    function collapseNavPanel(nav) {
        if (!nav) return;
        nav.querySelectorAll('.collapse').forEach(function (collapseEl) {
            collapseEl.classList.remove('show');
            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                const inst = bootstrap.Collapse.getInstance(collapseEl);
                if (inst) inst.hide();
            }
        });
        nav.querySelectorAll('[data-bs-toggle="collapse"]').forEach(resetCollapseTrigger);
    }

    function collapseAllPanelMenus() {
        document.querySelectorAll('nav.sidebar-panel-menu').forEach(collapseNavPanel);
    }

    function isNavVisible(nav) {
        if (!nav) return false;
        return nav.classList.contains('is-active-panel');
    }

    function expandActivePathInNav(nav) {
        if (!nav) return;
        nav.querySelectorAll('.sidebar-link.active[href]').forEach(function (activeLink) {
            var parent = activeLink.closest('.collapse');
            while (parent && nav.contains(parent)) {
                showCollapseElement(parent);
                parent = parent.parentElement ? parent.parentElement.closest('.collapse') : null;
            }
        });
    }

    function expandActivePathInVisibleMenus() {
        document.querySelectorAll('nav.sidebar-panel-menu.is-active-panel').forEach(expandActivePathInNav);
    }

    function initSidebarmenuPanels() {
        document.querySelectorAll('.sidebarmenu').forEach(function (sidebarmenu) {
            const paneRoot = sidebarmenu.closest('.tab-pane') || sidebarmenu.closest('.side-mini-panel') || document;
            const menus = sidebarmenu.querySelectorAll('nav.sidebar-panel-menu');
            if (!menus.length) return;

            menus.forEach(deactivatePanelNav);

            const selectedMini = paneRoot.querySelector('.mini-nav-item.selected');
            let activeNav = null;
            if (selectedMini) {
                activeNav = document.getElementById('menu-right-' + selectedMini.id);
            }
            if (!activeNav || !sidebarmenu.contains(activeNav)) {
                activeNav = menus[0];
            }
            activatePanelNav(activeNav);
            collapseNavPanel(activeNav);
            expandActivePathInNav(activeNav);
        });
    }

    function initPanelMenus() {
        initSidebarmenuPanels();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPanelMenus();

        document.addEventListener('show.bs.collapse', function (e) {
            var collapseEl = e.target;
            if (!collapseEl || !collapseEl.classList.contains('collapse')) return;
            var nav = collapseEl.closest('nav.sidebar-panel-menu');
            if (!nav || !nav.classList.contains('is-active-panel')) return;
            closeSiblingCollapses(collapseEl);
            openCollapseTrigger(getCollapseTrigger(collapseEl));
        }, true);

        document.addEventListener('hidden.bs.collapse', function (e) {
            var collapseEl = e.target;
            if (!collapseEl || !collapseEl.closest('nav.sidebar-panel-menu')) return;
            resetCollapseTrigger(getCollapseTrigger(collapseEl));
        }, true);
    });

    document.addEventListener('click', function (e) {
        var miniItem = e.target.closest('.mini-nav-item');
        if (!miniItem) return;
        var paneRoot = miniItem.closest('.tab-pane') || miniItem.closest('.side-mini-panel') || document;
        setTimeout(function () {
            var targetMenu = activateSidebarPanelByMiniId(miniItem.id, paneRoot);
            if (targetMenu) {
                collapseNavPanel(targetMenu);
                expandActivePathInNav(targetMenu);
            }
        }, 0);
    });

    window.sargamCloseSiblingSidebarCollapses = closeSiblingCollapses;
    window.collapseSidebarPanelNav = collapseNavPanel;
    window.collapseAllSidebarPanelMenus = collapseAllPanelMenus;
    window.initSidebarPanelMenus = initPanelMenus;
    window.activateSidebarPanelNav = activatePanelNav;
    window.activateSidebarPanelByMiniId = activateSidebarPanelByMiniId;
    window.deactivateSidebarPanelNav = deactivatePanelNav;
})();
