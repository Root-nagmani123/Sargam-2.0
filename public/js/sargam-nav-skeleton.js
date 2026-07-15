/**
 * Sargam 2.0 — in-place navigation skeleton.
 *
 * Raises #sargamNavSkeleton (markup: layouts/partials/nav-skeleton, styles:
 * css/sargam-app.css LAYER D) over the sidebar + content while an in-app
 * navigation is in flight. The real topbar stays live above it.
 *
 * Exposes:
 *   window.showSargamNavSkeleton()
 *   window.hideSargamNavSkeleton()
 *
 * NOT the same thing as #sargamLoader: that is the full-page service notice,
 * shown only on a fresh load / refresh. Nothing here touches it.
 */
(function (global) {
    'use strict';

    var SAFETY_CAP_MS = 15000;
    var capTimer = null;

    function el() {
        return document.getElementById('sargamNavSkeleton');
    }

    /* Measure rather than hard-code: the topbar height and sidebar width are
       owned by the real chrome (and change when the sidebar is collapsed or the
       viewport is narrow), so reading them here keeps the silhouette aligned
       without duplicating those constants in CSS. */
    function alignToChrome(node) {
        var topbar = document.querySelector('header.topbar');
        node.style.top = topbar
            ? Math.max(0, Math.round(topbar.getBoundingClientRect().bottom)) + 'px'
            : '0px';

        var side = node.querySelector('.ds-nav-skel-side');
        if (!side) { return; }

        var sidebar = document.querySelector('aside.side-mini-panel, #main-wrapper > aside');
        var width = sidebar ? Math.round(sidebar.getBoundingClientRect().width) : 0;

        // Off-canvas / absent sidebar → no side column, content spans the width.
        if (width > 0) {
            side.style.width = width + 'px';
            side.style.display = '';
        } else {
            side.style.display = 'none';
        }
    }

    function showSargamNavSkeleton() {
        var node = el();
        if (!node || node.classList.contains('is-visible')) { return; }

        alignToChrome(node);
        node.classList.add('is-visible');

        /* Dead-man switch. If the navigation we are covering never actually
           happens — cancelled download, blocked popup, server error swallowed —
           get out of the user's way rather than trapping them behind it. */
        if (capTimer) { clearTimeout(capTimer); }
        capTimer = setTimeout(hideSargamNavSkeleton, SAFETY_CAP_MS);
    }

    function hideSargamNavSkeleton() {
        var node = el();
        if (capTimer) { clearTimeout(capTimer); capTimer = null; }
        if (!node) { return; }
        node.classList.remove('is-visible');
    }

    global.showSargamNavSkeleton = showSargamNavSkeleton;
    global.hideSargamNavSkeleton = hideSargamNavSkeleton;

    /* ── Sidebar AJAX skeletons ──────────────────────────────────────────────
       The nav skeleton above only covers full page navigations. The sidebar has
       a second, separate loading state that it does not touch: the icon rail is
       re-fetched from route('sidebar.groups') on a category switch, and the menu
       items are ALWAYS fetched from route('sidebar.menu') — <ul id="sidebarnav">
       ships empty, so on every page load the menu panel is blank until that
       request lands. These fill both while their request is in flight.

       Markup mirrors what the server returns closely enough that the swap to
       real items doesn't jump; both reuse the .ds-skel-* shapes from LAYER D. */
    function sidebarMenuSkeletonHtml(rows) {
        var n = rows || 7;
        var html = '';
        for (var i = 0; i < n; i++) {
            html += '<li class="sidebar-item list-unstyled ds-skel-menu-item" aria-hidden="true">' +
                '<span class="ds-skeleton"></span><span class="ds-skeleton"></span></li>';
        }
        return html;
    }

    function sidebarRailSkeletonHtml(rows) {
        var n = rows || 6;
        var html = '<ul class="sidebar-groups-list list-unstyled mb-0">';
        for (var i = 0; i < n; i++) {
            html += '<li class="ds-skel-rail-item" aria-hidden="true">' +
                '<span class="ds-skeleton"></span><span class="ds-skeleton"></span></li>';
        }
        return html + '</ul>';
    }

    global.sidebarMenuSkeletonHtml = sidebarMenuSkeletonHtml;
    global.sidebarRailSkeletonHtml = sidebarRailSkeletonHtml;

    /* Restoring from the bfcache (Back button) replays neither `load` nor
       DOMContentLoaded, so a skeleton left up by the outgoing navigation would
       still be covering the restored page. */
    global.addEventListener('pageshow', hideSargamNavSkeleton);
    global.addEventListener('pagehide', hideSargamNavSkeleton);

    function isPlainLeftClick(e) {
        return !e.defaultPrevented && e.button === 0 &&
            !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey;
    }

    /* Only real, same-tab, same-origin navigations. Anything that leaves the
       page painted where it is (hash jump, new tab, download, mailto, JS href)
       must not raise a skeleton that then has nothing to clear it. */
    function navigatesAway(a) {
        if (!a || !a.getAttribute) { return false; }

        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#') { return false; }
        if (/^(javascript|mailto|tel|sms):/i.test(href)) { return false; }
        if (a.hasAttribute('download')) { return false; }
        if (a.target && a.target !== '' && a.target !== '_self') { return false; }
        if (a.dataset && a.dataset.noNavSkeleton !== undefined) { return false; }
        // Bootstrap uses <a> for tabs/dropdowns/modals — those never navigate.
        if (a.getAttribute('data-bs-toggle')) { return false; }
        if (a.origin && a.origin !== global.location.origin) { return false; }

        // Same page + same query = no paint coming; only the hash differs.
        if (a.pathname === global.location.pathname && a.search === global.location.search) {
            return false;
        }
        return true;
    }

    document.addEventListener('click', function (e) {
        if (!isPlainLeftClick(e)) { return; }

        var a = e.target.closest ? e.target.closest('a') : null;
        if (!navigatesAway(a)) { return; }

        showSargamNavSkeleton();
    }, true);

    // A full-page form post (filters, search) is a navigation too.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || e.defaultPrevented) { return; }
        if (form.hasAttribute('data-no-nav-skeleton')) { return; }
        if (form.target && form.target !== '' && form.target !== '_self') { return; }
        showSargamNavSkeleton();
    }, true);
})(window);
