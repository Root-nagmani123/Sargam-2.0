/**
 * FC portal chrome — GIGW accessibility controls for the shared FC header.
 *
 * Paired with the .fc-* section of public/css/sargam-app.css and rendered by
 * resources/views/fc/layouts/{header,footer}.blade.php. Loaded once (cacheable)
 * from fc/layouts/pre_header.blade.php with `defer`, rather than inlined into
 * the partial on all 31 FC pages.
 *
 * Preferences share the login page's localStorage keys (gigw-font-size,
 * gigw-contrast) so a choice made on either surface carries across.
 */
(function () {
    'use strict';

    var SIZES = ['small', 'normal', 'large'];

    /** Announce a state change to assistive tech without moving focus. */
    function announce(message) {
        var el = document.createElement('div');
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.className = 'visually-hidden';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 2000);
    }

    function init() {
        var fontBtns = document.querySelectorAll('.fc-font-btn');

        // ── Text size (root font-size scaling, remembered) ──
        function applySize(size) {
            var html = document.documentElement;
            SIZES.forEach(function (s) { html.classList.remove('fc-font-' + s); });
            html.classList.add('fc-font-' + size);
            Array.prototype.forEach.call(fontBtns, function (b) {
                b.classList.toggle('active', b.dataset.size === size);
            });
        }

        Array.prototype.forEach.call(fontBtns, function (btn) {
            btn.addEventListener('click', function () {
                var size = this.dataset.size;
                if (SIZES.indexOf(size) === -1) return;
                applySize(size);
                try { localStorage.setItem('gigw-font-size', size); } catch (e) {}
                announce('Text size changed to ' + size);
            });
        });

        try {
            var saved = localStorage.getItem('gigw-font-size');
            if (saved && SIZES.indexOf(saved) !== -1) applySize(saved);
        } catch (e) {}

        // ── High contrast ──
        var contrastBtn = document.getElementById('fcContrastToggle');
        if (contrastBtn) {
            contrastBtn.addEventListener('click', function () {
                var on = document.body.classList.toggle('high-contrast');
                this.setAttribute('aria-pressed', on ? 'true' : 'false');
                try { localStorage.setItem('gigw-contrast', on ? 'high' : 'normal'); } catch (e) {}
                announce(on ? 'High contrast mode enabled' : 'Normal contrast mode enabled');
            });

            try {
                if (localStorage.getItem('gigw-contrast') === 'high') {
                    document.body.classList.add('high-contrast');
                    contrastBtn.setAttribute('aria-pressed', 'true');
                }
            } catch (e) {}
        }

        // ── Skip link ──
        // "Skip to Main Content" targets #content, which fc/layouts/master.blade.php
        // puts on the <main> wrapper. Moving focus (not just scrolling) is what makes
        // the link actually work for keyboard and screen-reader users.
        var main = document.getElementById('content');
        if (main) {
            var skip = document.querySelector('.fc-topbar-left a[href="#content"]');
            if (skip) {
                skip.addEventListener('click', function () {
                    main.focus({ preventScroll: false });
                });
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
