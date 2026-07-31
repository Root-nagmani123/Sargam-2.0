{{-- ==========================================================================
     FC portal header — visual language ported from resources/views/auth/login.blade.php
     (GIGW toolbar → tricolour strip → white sticky brand bar), built on the
     --ds-* design tokens from public/css/sargam-app.css (see docs/design.md).

     Structural contracts kept intentionally:
       • `.top-header > .container` / `.header > .container` — widened by
         fc/registration/partials/fc-form-theme.blade.php on the form pages.
       • `#uw-widget-custom-trigger` — admin_assets/js/weights.js binds to this
         id without a null guard; removing it breaks the rest of that file.
       • `#navbarNav` fixed bottom bar on phones/tablets.
     ========================================================================== --}}
@php
    $fcAssetV = fn (string $p) => asset($p) . '?v=' . (@filemtime(public_path($p)) ?: 1);
@endphp

<style>
    /* ── Header-local vars: national accents + surfaces, everything else --ds-* ── */
    .fc-topbar,
    .fc-header {
        --fc-saffron: #ff9933;
        --fc-green: #138808;
        --fc-focus: #ff6b35;
    }

    /* ===== GIGW accessibility toolbar ===== */
    .top-header.fc-topbar {
        background: #004a93;
        color: #fff;
        padding: 0.375rem 0;
        position: relative;
        z-index: 1031;
    }

    /* pre_header.blade.php hides .top-header under 768px; the toolbar carries the
       accessibility controls, so keep it visible (higher specificity wins). */
    @media (max-width: 767.98px) {
        .top-header.fc-topbar {
            display: block !important;
        }
    }

    .fc-topbar > .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .fc-topbar-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .fc-topbar-left a {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.75rem;
        font-weight: 500;
        text-decoration: none;
        transition: color 250ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .fc-topbar-left a:hover,
    .fc-topbar-left a:focus {
        color: #fff;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .fc-topbar-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .fc-font-controls {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .fc-font-controls > span {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.6875rem;
        font-weight: 500;
        margin-right: 0.25rem;
    }

    .fc-font-btn,
    .fc-contrast-btn,
    .fc-a11y-btn {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #fff;
        border-radius: var(--ds-radius-1);
        cursor: pointer;
        transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }

    .fc-font-btn {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.75rem;
    }

    .fc-contrast-btn,
    .fc-a11y-btn {
        padding: 0.25rem 0.625rem;
        font-size: 0.6875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .fc-font-btn:hover,
    .fc-font-btn:focus,
    .fc-contrast-btn:hover,
    .fc-contrast-btn:focus,
    .fc-a11y-btn:hover,
    .fc-a11y-btn:focus {
        background: rgba(255, 255, 255, 0.3);
        color: #fff;
    }

    .fc-font-btn.active {
        background: #fff;
        color: var(--ds-primary);
    }

    /* ===== Tricolour strip ===== */
    .fc-header-tricolor {
        height: 4px;
        background: linear-gradient(90deg,
                var(--fc-saffron) 33.33%,
                #ffffff 33.33%, #ffffff 66.66%,
                var(--fc-green) 66.66%);
    }

    /* ===== White sticky brand bar ===== */
    /* NOTE: deliberately no `backdrop-filter` here. A filtered element becomes the
       containing block for its `position: fixed` descendants, which would pin the
       mobile bottom nav (#navbarNav) to the header instead of the viewport. */
    .header.fc-header {
        padding: 0;
        background: rgba(255, 255, 255, 0.97);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: none;
    }

    .fc-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        padding: 0.625rem 0;
    }

    .fc-brand {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        min-width: 0;
    }

    .fc-emblem {
        height: 36px;
        width: auto;
    }

    .fc-govt {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-right: 1.25rem;
        border-right: 1px solid var(--ds-line);
    }

    .fc-govt img {
        height: 28px;
        width: auto;
    }

    .fc-govt span {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--ds-ink);
        white-space: nowrap;
        letter-spacing: -0.01em;
    }

    .fc-lbsnaa img {
        height: 40px;
        max-width: 280px;
        width: auto;
    }

    /* ===== Navigation ===== */
    .fc-header .navbar-nav {
        align-items: center;
        gap: 0.25rem;
    }

    .fc-nav-link {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.4rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--ds-ink-muted);
        text-decoration: none;
        border-radius: var(--ds-radius-2);
        transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .fc-nav-link:hover,
    .fc-nav-link:focus {
        background: #f0f6fc;
        color: #004a93;
    }

    .fc-btn-auth {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.125rem;
        background: #004a93;
        color: #fff;
        border: none;
        border-radius: var(--ds-radius-2);
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(0, 74, 147, 0.35);
        transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .fc-btn-auth:hover,
    .fc-btn-auth:focus {
        background: #003d7a;
        color: #fff;
        box-shadow: 0 8px 24px rgba(0, 74, 147, 0.45);
        transform: translateY(-1px);
    }

    /* Keyboard focus — WCAG, mirrors the login page */
    .fc-topbar :focus-visible,
    .fc-header :focus-visible {
        outline: 3px solid var(--fc-focus);
        outline-offset: 3px;
    }

    /* ===== Mobile: nav becomes a fixed bottom bar ===== */
    @media (max-width: 991.98px) {
        #navbarNav {
            display: flex !important;
            visibility: visible !important;
            position: fixed !important;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: var(--ds-surface);
            border-top: 1px solid var(--ds-line);
            box-shadow: 0 -2px 12px rgba(16, 24, 40, 0.12);
            padding-bottom: env(safe-area-inset-bottom, 0);
        }

        #navbarNav .navbar-nav {
            flex-direction: row;
            justify-content: space-around;
            width: 100%;
            gap: 0;
        }

        #navbarNav .nav-item {
            flex: 1 1 0;
            text-align: center;
        }

        /* Inside the bottom bar every item reads as an icon + label tile,
           including the auth action. */
        #navbarNav .fc-nav-link,
        #navbarNav .fc-btn-auth {
            width: 100%;
            flex-direction: column;
            justify-content: center;
            gap: 0.125rem;
            padding: 0.5rem 0.25rem;
            font-size: 0.6875rem;
            background: transparent;
            color: var(--ds-ink-muted);
            box-shadow: none;
            border-radius: 0;
            transform: none;
        }

        #navbarNav .fc-btn-auth {
            color: var(--ds-primary);
        }

        #navbarNav .fc-nav-link i,
        #navbarNav .fc-btn-auth i {
            font-size: 1.125rem;
        }

        body {
            /* the fixed bottom bar must never sit on top of page content */
            padding-bottom: 72px;
        }

        .fc-topbar > .container {
            justify-content: center;
        }

        /* the control cluster wraps instead of forcing the page to scroll sideways */
        .fc-topbar-right {
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
        }

        .fc-emblem {
            height: 28px;
        }

        .fc-brand {
            gap: 0.75rem;
        }

        .fc-govt {
            padding-right: 0.75rem;
        }

        .fc-lbsnaa img {
            height: 32px;
        }
    }

    @media (max-width: 575.98px) {
        .fc-topbar-left {
            display: none;
        }

        .fc-font-controls > span {
            display: none;
        }
    }

    @media (min-width: 992px) {
        #navbarNav {
            position: static !important;
            box-shadow: none !important;
        }

        body {
            padding-bottom: 0;
        }
    }

    /* ===== GIGW text-size + high-contrast modes ===== */
    html.fc-font-small { font-size: 87.5%; }
    html.fc-font-normal { font-size: 100%; }
    html.fc-font-large { font-size: 112.5%; }

    body.high-contrast .fc-topbar {
        background: #000;
    }

    body.high-contrast .fc-header > .container {
        background: #fff;
    }

    body.high-contrast .fc-header-inner {
        border-bottom: 2px solid #000;
    }

    body.high-contrast .fc-nav-link {
        color: #000;
    }

    body.high-contrast .fc-btn-auth {
        background: #000;
        box-shadow: none;
    }

    @media (prefers-reduced-motion: reduce) {
        .fc-topbar *,
        .fc-header * {
            transition-duration: 0.01ms !important;
            animation-duration: 0.01ms !important;
        }
    }
</style>

<!-- GIGW accessibility toolbar -->
<div class="top-header fc-topbar" role="navigation" aria-label="Accessibility Options">
    <div class="container">
        <div class="fc-topbar-left">
            <a href="#content">Skip to Main Content</a>
            <a href="https://screenreaderaccess.com" target="_blank" rel="noopener noreferrer"
                title="Screen Reader Access Information">
                <i class="bi bi-ear" aria-hidden="true"></i> Screen Reader Access
            </a>
            <a href="https://www.india.gov.in" target="_blank" rel="noopener noreferrer"
                title="National Portal of India">
                <i class="bi bi-globe2" aria-hidden="true"></i> india.gov.in
            </a>
        </div>
        <div class="fc-topbar-right">
            <div class="fc-font-controls" role="group" aria-label="Text Size Controls">
                <span>Text Size:</span>
                <button type="button" class="fc-font-btn" data-size="small" aria-label="Decrease text size"
                    title="Decrease Text Size">A-</button>
                <button type="button" class="fc-font-btn active" data-size="normal" aria-label="Normal text size"
                    title="Normal Text Size">A</button>
                <button type="button" class="fc-font-btn" data-size="large" aria-label="Increase text size"
                    title="Increase Text Size">A+</button>
            </div>
            <button type="button" class="fc-contrast-btn" id="fcContrastToggle" aria-pressed="false"
                aria-label="Toggle high contrast" title="High Contrast Mode">
                <i class="bi bi-circle-half" aria-hidden="true"></i> Contrast
            </button>
            {{-- id required by admin_assets/js/weights.js (binds without a null check) --}}
            <a class="fc-a11y-btn" id="uw-widget-custom-trigger" role="button" tabindex="0" contenteditable="false"
                style="cursor: pointer;" title="Accessibility Options">
                <i class="bi bi-universal-access" aria-hidden="true"></i> Accessibility
            </a>
        </div>
    </div>
</div>

<!-- Sticky brand bar -->
<div class="header fc-header sticky-top container-fluid" role="banner">
    <div class="fc-header-tricolor"></div>
    <div class="container">
        <div class="fc-header-inner">
            <div class="fc-brand">
                <img src="{{ $fcAssetV('admin_assets/images/logos/ashoka.png') }}"
                    alt="National Emblem of India - Satyameva Jayate" class="fc-emblem" loading="eager"
                    onerror="this.style.display='none'">
                <div class="fc-govt">
                    <img src="{{ asset('images/flag-of-india.svg') }}" alt="National Flag of India" loading="eager"
                        onerror="this.style.display='none'">
                    <span lang="hi">भारत सरकार</span>
                    <span class="d-none d-sm-inline">| Government of India</span>
                </div>
                <a href="{{ url('/') }}" class="fc-lbsnaa d-flex align-items-center text-decoration-none"
                    aria-label="LBSNAA Home">
                    {{-- Right-sized web variants (400px); the 1193px logo.png stays for PDF/print. --}}
                    <picture>
                        <source srcset="{{ $fcAssetV('admin_assets/images/logos/logo-web.webp') }}" type="image/webp">
                        <img src="{{ $fcAssetV('admin_assets/images/logos/logo-web.png') }}"
                            alt="LBSNAA - Lal Bahadur Shastri National Academy of Administration" loading="eager"
                            onerror="this.style.display='none'">
                    </picture>
                </a>
            </div>

            <nav class="navbar navbar-expand-lg navbar-light p-0" aria-label="Primary Navigation">
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto mb-0">
                        <li class="nav-item">
                            <a class="fc-nav-link" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa" target="_blank"
                                rel="noopener noreferrer">
                                <i class="bi bi-info-circle" aria-hidden="true"></i> About Us
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="fc-nav-link" href="https://www.lbsnaa.gov.in/footer_menu/contact-us"
                                target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-envelope" aria-hidden="true"></i> Contact
                            </a>
                        </li>
                        <li class="nav-item">
                            @php($fcHeaderFormQuery = $fcHeaderFormQuery ?? [])
                            @if (auth()->check())
                                <a class="fc-btn-auth" href="{{ route('fc.logout', $fcHeaderFormQuery) }}">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout
                                </a>
                            @else
                                <a class="fc-btn-auth" href="{{ route('fc.login', $fcHeaderFormQuery) }}">
                                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Login
                                </a>
                            @endif
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        // Live-region helper for the accessibility controls below.
        function announce(message) {
            var el = document.createElement('div');
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            el.className = 'visually-hidden';
            el.textContent = message;
            document.body.appendChild(el);
            setTimeout(function () { el.remove(); }, 2000);
        }

        // ── Text size (root font-size scaling, remembered) ──
        var fontBtns = document.querySelectorAll('.fc-font-btn');
        var SIZES = ['small', 'normal', 'large'];

        function applySize(size) {
            var html = document.documentElement;
            SIZES.forEach(function (s) { html.classList.remove('fc-font-' + s); });
            html.classList.add('fc-font-' + size);
            fontBtns.forEach(function (b) { b.classList.toggle('active', b.dataset.size === size); });
        }

        fontBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var size = this.dataset.size;
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
    })();
</script>
