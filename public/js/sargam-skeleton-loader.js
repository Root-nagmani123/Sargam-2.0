/**
 * Sargam 2.0 — app-shell skeleton loader.
 *
 * Drives the #sargamLoader overlay (markup: admin/layouts/partials/skeleton-shell,
 * styles: css/sargam-app.css LAYER D) for every layout that includes it.
 *
 * Exposes:
 *   window.hideSargamLoader()  — also called by sidebar-dynamic-toggle.js
 *   window.showSargamLoader()
 *
 * The .sargam-loader / #sargamLoader names are load-bearing: several print
 * stylesheets hide the overlay by name.
 */
(function () {
    'use strict';

    /* `collapseTimer` finishes a hide (display:none after the fade); `capTimer`
       is the dead-man switch that guarantees the skeleton can never be left
       covering a working page. Both are tracked so a show() that lands mid-fade
       can cancel them — otherwise the in-flight display:none would blank the
       skeleton we just raised. */
    var collapseTimer = null;
    var capTimer = null;
    var SAFETY_CAP_MS = 15000;

    function hideSargamLoader() {
        var loader = document.getElementById('sargamLoader');
        if (!loader || loader.classList.contains('hidden')) return;
        if (capTimer) { clearTimeout(capTimer); capTimer = null; }
        loader.classList.add('hidden');
        if (collapseTimer) clearTimeout(collapseTimer);
        collapseTimer = setTimeout(function () {
            loader.style.display = 'none';
            collapseTimer = null;
        }, 500);
    }

    /* Re-show the shell skeleton. This app is server-rendered: after a link
       click the browser keeps painting the OLD page until the server responds,
       so a slow screen looks frozen/ignored and users click again. Painting the
       skeleton acknowledges the click. */
    function showSargamLoader() {
        var loader = document.getElementById('sargamLoader');
        if (!loader) return;
        if (collapseTimer) { clearTimeout(collapseTimer); collapseTimer = null; }
        loader.style.display = '';
        // Force a reflow so removing .hidden animates instead of snapping.
        void loader.offsetWidth;
        loader.classList.remove('hidden');
        /* If the navigation we're covering never actually happens, get out of
           the user's way rather than trapping them behind it. */
        if (capTimer) clearTimeout(capTimer);
        capTimer = setTimeout(hideSargamLoader, SAFETY_CAP_MS);
    }

    window.hideSargamLoader = hideSargamLoader;
    window.showSargamLoader = showSargamLoader;

    window.addEventListener('load', hideSargamLoader);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(hideSargamLoader, 300);
        });
    } else {
        setTimeout(hideSargamLoader, 0);
    }
    setTimeout(hideSargamLoader, 8000);

    /* Restoring from the bfcache (Back button) replays neither `load` nor
       DOMContentLoaded, so a skeleton left up by the outgoing navigation would
       be frozen on screen. Clear it on restore. */
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hideSargamLoader();
    });

    /* ── Navigation skeleton ────────────────────────────────────────────────
       Deliberately conservative: anything that might NOT end in a real page
       load is skipped, because a skeleton over a page that never navigates is
       stuck until the safety cap. Opt out per-link with data-no-skeleton. */
    var NAV_GRACE_MS = 120;

    function isPlainLeftClick(e) {
        return e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey;
    }

    function navigatesAway(a) {
        if (!a || !a.getAttribute('href')) return false;
        if (a.hasAttribute('download')) return false;
        if (a.hasAttribute('data-no-skeleton')) return false;
        // Bootstrap-driven links (tabs, modals, dropdowns, collapse) stay put.
        if (a.hasAttribute('data-bs-toggle')) return false;
        if (a.getAttribute('role') === 'button') return false;
        if (a.target && a.target !== '' && a.target !== '_self') return false;

        var url;
        try { url = new URL(a.href, window.location.href); } catch (err) { return false; }

        // javascript:, mailto:, tel:, blob: …
        if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;
        if (url.origin !== window.location.origin) return false;
        // Same-page anchor / hash-only link: no navigation, no skeleton.
        if (url.pathname === window.location.pathname &&
            url.search === window.location.search &&
            url.hash) return false;
        return true;
    }

    document.addEventListener('click', function (e) {
        if (!isPlainLeftClick(e)) return;
        var a = e.target.closest ? e.target.closest('a[href]') : null;
        if (!navigatesAway(a)) return;

        /* Wait out the grace window before painting, for two reasons:
           1. defaultPrevented is only trustworthy once the whole dispatch has
              finished — plenty of handlers here (sidebar AJAX, SweetAlert
              confirms, jQuery-validate) cancel the click after this listener
              runs.
           2. A server that answers in 40ms would otherwise flash the skeleton,
              which reads as jank rather than progress. */
        setTimeout(function () {
            if (e.defaultPrevented) return;
            showSargamLoader();
        }, NAV_GRACE_MS);
    });
})();
