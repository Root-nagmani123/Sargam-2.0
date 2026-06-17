/**
 * Floating sidebar flyout: show submenu panel on mini-nav item hover.
 */
(function (global) {
    'use strict';

    var HIDE_DELAY_MS = 280;

    function getGroupFromItem(item) {
        var link = item.querySelector('.sidebar-group-link, .mini-nav-link, a[data-id]');
        if (link && link.dataset && link.dataset.id) {
            return { id: link.dataset.id, name: link.dataset.name || '' };
        }
        if (item.dataset && item.dataset.id) {
            return { id: item.dataset.id, name: '' };
        }
        return { id: item.id || null, name: '' };
    }

    function showLegacyMenu(panel, itemId) {
        if (!itemId) {
            return;
        }
        var targetMenuId = 'menu-right-' + itemId;
        var targetMenu = panel.querySelector('#' + CSS.escape(targetMenuId));
        if (!targetMenu) {
            targetMenu = global.document.getElementById(targetMenuId);
        }
        if (!targetMenu) {
            return;
        }
        panel.querySelectorAll('.sidebarmenu nav').forEach(function (nav) {
            nav.classList.remove('d-block');
            nav.style.display = 'none';
        });
        targetMenu.classList.add('d-block');
        targetMenu.style.display = 'block';
    }

    function initFlyoutPanel(panel) {
        if (panel.dataset.sidebarFlyoutInit === '1') {
            return panel._sidebarFlyoutApi || null;
        }
        panel.dataset.sidebarFlyoutInit = '1';

        var miniNav = panel.querySelector('.mini-nav');
        var sidebarmenu = panel.querySelector('.sidebarmenu');
        if (!miniNav || !sidebarmenu) {
            return null;
        }

        var sidebarNav = sidebarmenu.querySelector('.sidebar-nav');
        var hideTimer = null;
        var activeItem = null;

        function navItems() {
            return miniNav.querySelectorAll('.sidebar-group-item, .mini-nav-item');
        }

        function setFlyoutVisible(visible) {
            if (visible) {
                sidebarmenu.classList.add('hovermenus', 'is-visible');
                if (sidebarNav) {
                    sidebarNav.classList.add('left-none');
                }
                return;
            }
            sidebarmenu.classList.remove('hovermenus', 'is-visible');
            if (sidebarNav) {
                sidebarNav.classList.remove('left-none');
            }
            panel.querySelectorAll('.sidebarmenu nav.sidebar-nav').forEach(function (nav) {
                nav.classList.remove('left-none', 'd-block');
                nav.style.display = '';
            });
        }

        function activateItem(item) {
            clearTimeout(hideTimer);
            activeItem = item;

            navItems().forEach(function (el) {
                el.classList.remove('is-hover-active');
            });
            item.classList.add('is-hover-active');

            setFlyoutVisible(true);

            var group = getGroupFromItem(item);
            if (group.id && typeof global.selectSidebarGroupVisual === 'function') {
                global.selectSidebarGroupVisual(group.id);
            }
            if (group.id && typeof global.loadSidebarMenusForGroup === 'function') {
                global.loadSidebarMenusForGroup(group.id, group.name);
            } else if (group.id) {
                showLegacyMenu(panel, group.id);
                var legacyNav = panel.querySelector('#menu-right-' + CSS.escape(group.id));
                if (legacyNav) {
                    legacyNav.classList.add('left-none');
                }
            }
        }

        function scheduleHide() {
            clearTimeout(hideTimer);
            hideTimer = global.setTimeout(function () {
                setFlyoutVisible(false);
                navItems().forEach(function (el) {
                    el.classList.remove('is-hover-active');
                });
                activeItem = null;
            }, HIDE_DELAY_MS);
        }

        function cancelHide() {
            clearTimeout(hideTimer);
        }

        miniNav.addEventListener(
            'mouseover',
            function (e) {
                var item = e.target.closest('.sidebar-group-item, .mini-nav-item');
                if (!item || !miniNav.contains(item)) {
                    return;
                }
                if (item === activeItem && sidebarmenu.classList.contains('is-visible')) {
                    cancelHide();
                    return;
                }
                activateItem(item);
            },
            true
        );

        miniNav.addEventListener('mouseleave', function (e) {
            var related = e.relatedTarget;
            if (related && (sidebarmenu.contains(related) || miniNav.contains(related))) {
                return;
            }
            scheduleHide();
        });

        sidebarmenu.addEventListener('mouseenter', cancelHide);
        sidebarmenu.addEventListener('mouseleave', function (e) {
            var related = e.relatedTarget;
            if (related && miniNav.contains(related)) {
                return;
            }
            scheduleHide();
        });

        var api = { activateItem: activateItem, scheduleHide: scheduleHide };
        panel._sidebarFlyoutApi = api;
        return api;
    }

    function init() {
        global.document.querySelectorAll('.side-mini-panel').forEach(function (panel) {
            if (!panel.querySelector('.mini-nav')) {
                return;
            }
            panel.classList.add('sidebar-hover-flyout');
            initFlyoutPanel(panel);
        });

        if (global.document.body) {
            global.document.body.classList.add('sidebar-flyout-mode');
        }
    }

    function hideAllFlyouts() {
        global.document.querySelectorAll('.sidebarmenu').forEach(function (el) {
            el.classList.remove('is-visible', 'hovermenus', 'has-pinned-menu');
        });
        global.document.querySelectorAll('.is-hover-active').forEach(function (el) {
            el.classList.remove('is-hover-active');
        });
    }

    function onSidebarTypeChange() {
        hideAllFlyouts();
    }

    if (global.document.readyState === 'loading') {
        global.document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    if (global.MutationObserver && global.document.body) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.attributeName === 'data-sidebartype') {
                    onSidebarTypeChange();
                }
            });
        }).observe(global.document.body, {
            attributes: true,
            attributeFilter: ['data-sidebartype'],
        });
    }
})(typeof window !== 'undefined' ? window : this);
