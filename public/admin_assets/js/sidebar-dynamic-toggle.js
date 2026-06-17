/**
 * Dynamic sidebar: mini-nav collapse/expand toggle for the menu panel.
 */
(function (global) {
    'use strict';

    var STORAGE_KEY = 'SidebarType';

    /** Prevents master.blade MutationObserver feedback loop on data-sidebartype */
    function setSuppressSidebarObserver(suppress) {
        global.__sargamSuppressSidebarObserver = !!suppress;
    }

    function adjustAllDataTables() {
        try {
            if (global.jQuery && global.jQuery.fn && global.jQuery.fn.dataTable) {
                var api = global.jQuery.fn.dataTable.tables({ visible: true, api: true });
                if (api && api.columns) {
                    api.columns.adjust();
                    if (api.responsive && api.responsive.recalc) {
                        api.responsive.recalc();
                    }
                }
            }
        } catch (err) {
            console.warn('DataTables adjust failed after sidebar menu toggle:', err);
        }
    }

    function getToggleElements() {
        return {
            btn: global.document.getElementById('sidebarMenuCollapse'),
            icon: global.document.getElementById('sidebarToggleIcon'),
            label: global.document.querySelector('#sidebarMenuCollapse .sidebar-google-label'),
            menu: global.document.getElementById('sidebar-setup-menu'),
        };
    }

    function isMenuExpanded() {
        return global.document.body.getAttribute('data-sidebartype') !== 'mini-sidebar';
    }

    function syncToggleUi(expanded) {
        var body = global.document.body;
        var els = getToggleElements();

        body.classList.toggle('dynamic-sidebar-menu-expanded', expanded);
        body.classList.toggle('dynamic-sidebar-menu-collapsed', !expanded);

        if (els.btn) {
            els.btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            els.btn.setAttribute(
                'aria-label',
                expanded ? 'Collapse sidebar menu' : 'Expand sidebar menu'
            );
        }
        if (els.icon) {
            els.icon.textContent = expanded ? 'left_panel_close' : 'right_panel_open';
            els.icon.classList.toggle('rotated', expanded);
        }
        if (els.label) {
            els.label.textContent = expanded ? 'Close' : 'Open';
        }
        if (els.menu) {
            els.menu.classList.toggle('close', !expanded);
        }
        global.document.querySelectorAll('#sidebar-setup .sidebarmenu').forEach(function (el) {
            el.classList.toggle('close', !expanded);
        });
        global.document.querySelectorAll('#sidebar-setup .sidebarmenu .sidebar-nav').forEach(function (nav) {
            if (expanded) {
                nav.classList.add('d-block', 'left-none');
                nav.style.removeProperty('display');
                nav.style.removeProperty('visibility');
            } else {
                nav.classList.remove('d-block');
                nav.style.display = 'none';
                nav.style.visibility = 'hidden';
            }
        });
    }

    function setDynamicSidebarMenuExpanded(expanded, persist) {
        if (!global.document.body.classList.contains('has-dynamic-sidebar')) {
            return;
        }
        var type = expanded ? 'full' : 'mini-sidebar';
        var body = global.document.body;
        var current = body.getAttribute('data-sidebartype');
        if (current !== type) {
            setSuppressSidebarObserver(true);
            try {
                body.setAttribute('data-sidebartype', type);
            } finally {
                setSuppressSidebarObserver(false);
            }
        }
        if (persist !== false) {
            try {
                global.localStorage.setItem(STORAGE_KEY, type);
            } catch (e) { /* ignore */ }
        }
        syncToggleUi(expanded);
        global.setTimeout(adjustAllDataTables, 300);
    }

    function toggleDynamicSidebarMenu() {
        setDynamicSidebarMenuExpanded(!isMenuExpanded());
    }

    global.setDynamicSidebarMenuExpanded = setDynamicSidebarMenuExpanded;
    global.toggleDynamicSidebarMenu = toggleDynamicSidebarMenu;

    function init() {
        if (!global.document.body.classList.contains('has-dynamic-sidebar')) {
            return;
        }
        var els = getToggleElements();
        if (!els.btn) {
            return;
        }

        var saved = 'full';
        try {
            saved = global.localStorage.getItem(STORAGE_KEY) || 'full';
        } catch (e) { /* ignore */ }

        setDynamicSidebarMenuExpanded(saved !== 'mini-sidebar', false);

        els.btn.addEventListener(
            'click',
            function (e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleDynamicSidebarMenu();
            },
            true
        );

        if (typeof global.hideSargamLoader === 'function') {
            global.hideSargamLoader();
        }
    }

    if (global.document.readyState === 'loading') {
        global.document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(typeof window !== 'undefined' ? window : this);
