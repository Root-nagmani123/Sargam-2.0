{{-- Service-notice preloader.

     A full-screen overlay shown from the first frame of every page load and
     hidden on window.load. Shared by admin/layouts/master and
     faculty/layouts/master — keep it here rather than inline in each shell so
     the two can't drift apart (faculty previously had its own #alphabetLoader
     with no hide logic at all, so it never went away).

     The CSS is inline on purpose: this paints before any external stylesheet is
     guaranteed to have loaded, so an @import/<link> would flash unstyled markup.

     The .sargam-loader / #sargamLoader names are load-bearing — several print
     stylesheets hide the overlay by name, and sidebar-dynamic-toggle.js calls
     window.hideSargamLoader(). Don't rename either without grepping first. --}}
<style>
    .sargam-loader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        background: #f6f2e9;
        transition: opacity 0.5s ease, visibility 0.5s ease;
        overflow: hidden;
        /* Stated outright, not inherited: this paints before the app stylesheet
           and the Google Fonts link are guaranteed to have loaded, so without it
           the copy falls back to the browser default (Times) for the first
           frames. Noto Sans matches the app once it does arrive. */
        font-family: "Noto Sans", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .sargam-loader.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    /* ── Masthead ── */
    .sargam-loader-topbar {
        flex: 0 0 auto;
        display: flex;
        align-items: baseline;
        gap: 0.85rem;
        padding: 1.4rem 2.25rem;
        background: #0e2749;
        border-bottom: 3px solid #e07c24;
    }

    .sargam-loader-brand {
        margin: 0;
        color: #fff;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        line-height: 1;
    }

    .sargam-loader-brand-sub {
        color: #9fb6d0;
        font-size: 0.8125rem;
        font-weight: 500;
        letter-spacing: 0.02em;
    }

    /* ── Centred notice card ── */
    .sargam-loader-body {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .sargam-loader-card {
        width: 100%;
        max-width: 560px;
        padding: 2.75rem 3rem 2.25rem;
        background: #fff;
        border: 1px solid #ece7db;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(14, 39, 73, 0.04);
        text-align: center;
    }

    .sargam-loader-eyebrow {
        margin: 0;
        color: #c26a1b;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.6875rem;
        font-weight: 500;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .sargam-loader-art {
        display: block;
        width: 200px;
        height: auto;
        margin: 1.75rem auto 1.5rem;
    }

    .sargam-loader-title {
        margin: 0 0 0.85rem;
        color: #0e2749;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .sargam-loader-text {
        max-width: 430px;
        margin: 0 auto;
        color: #55636f;
        font-size: 0.9375rem;
        line-height: 1.65;
    }

    .sargam-loader-rule {
        margin: 2rem 0 1.15rem;
        border: 0;
        border-top: 1px solid #ece7db;
    }

    .sargam-loader-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        color: #8b94a0;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.6875rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .sargam-loader-dots {
        display: inline-flex;
        gap: 0.3rem;
    }

    .sargam-loader-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #e07c24;
        opacity: 0.25;
        animation: sargamLoaderDot 1.4s ease-in-out infinite;
    }

    .sargam-loader-dot:nth-child(2) { animation-delay: 0.2s; }
    .sargam-loader-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes sargamLoaderDot {
        0%, 100% { opacity: 0.25; }
        50%      { opacity: 1; }
    }

    /* The rails stay still — the motion is the folder traveling along them,
       which is the whole point of "your request is on its way". Those paths are
       animated with SMIL <animateMotion> in the markup rather than a CSS
       offset-path, so the route is read straight off the <path> it follows and
       the coordinates aren't duplicated here. */
    .sargam-loader-wire {
        stroke-dasharray: 3 3;
    }

    /* ── Footer hint ── */
    .sargam-loader-foot {
        flex: 0 0 auto;
        padding: 0 1.5rem 2rem;
        color: #9aa4ae;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.6875rem;
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .sargam-loader-topbar { padding: 1.1rem 1.25rem; }
        .sargam-loader-brand { font-size: 1.25rem; }
        .sargam-loader-brand-sub { font-size: 0.6875rem; }
        .sargam-loader-card { padding: 2rem 1.5rem 1.75rem; }
        .sargam-loader-title { font-size: 1.4rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sargam-loader,
        .sargam-loader-dot {
            transition-duration: 0.01ms;
            animation: none;
        }
        .sargam-loader-dot { opacity: 0.7; }
        /* SMIL ignores CSS animation:none, so the travellers have to be removed
           outright — the static nodes and rails still tell the story. */
        .sargam-loader-travel { display: none; }
    }
</style>

<div class="sargam-loader" id="sargamLoader" role="status" aria-live="polite" aria-label="Loading Sargam 2.0">
    <div class="sargam-loader-topbar">
        <span class="sargam-loader-brand">SARGAM 2.0</span>
        <span class="sargam-loader-brand-sub">LBSNAA &middot; e-Governance Portal</span>
    </div>

    <div class="sargam-loader-body">
        <div class="sargam-loader-card">
            <p class="sargam-loader-eyebrow">Service Notice</p>

            {{-- A request travelling from the origin node out to the downstream
                 nodes. Decorative, so hidden from AT. The two folders ride the
                 SAME <path> elements that draw the dashed rails (via <mpath>), so
                 the route can never drift from the line it is drawn on. --}}
            <svg class="sargam-loader-art" viewBox="0 0 200 86" fill="none"
                xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                aria-hidden="true" focusable="false">
                <g stroke="#cbd3dc" stroke-width="1.25">
                    <path id="sargamWireLeft" class="sargam-loader-wire" d="M92 32 L58 62" />
                    <path id="sargamWireRight" class="sargam-loader-wire" d="M108 32 L142 62" />
                </g>

                {{-- Origin node (the one currently working) --}}
                <circle cx="100" cy="6" r="2" fill="#8f9bab" />
                <path d="M100 9 L100 12" stroke="#cbd3dc" stroke-width="1.25" />
                <rect x="76" y="12" width="48" height="20" rx="3" fill="#fff" stroke="#aab4c0" stroke-width="1.25" />
                <circle cx="83" cy="22" r="1.6" fill="#8f9bab" />
                <path d="M88 22 L98 22" stroke="#cbd3dc" stroke-width="1.5" stroke-linecap="round" />
                <rect x="103" y="17" width="17" height="10" rx="1.5" fill="#e07c24" />

                {{-- Downstream nodes --}}
                <rect x="28" y="62" width="34" height="17" rx="3" fill="#fff" stroke="#cbd3dc" stroke-width="1.25" />
                <circle cx="34" cy="68" r="1.4" fill="#c2cad4" />
                <path d="M38 73 L56 73" stroke="#e4e9ee" stroke-width="1.5" stroke-linecap="round" />

                <rect x="138" y="62" width="34" height="17" rx="3" fill="#fff" stroke="#cbd3dc" stroke-width="1.25" />
                <circle cx="144" cy="68" r="1.4" fill="#c2cad4" />
                <path d="M148 73 L166 73" stroke="#e4e9ee" stroke-width="1.5" stroke-linecap="round" />

                {{-- Travelling folders. Drawn around (0,0) so animateMotion can
                     place them, and kept small relative to the rail so they read as
                     moving rather than as sitting on top of the nodes. Staggered so
                     the two rails don't fire in lockstep. xlink:href is kept
                     alongside href for Safari's SMIL.

                     opacity="0" is the load-bearing bit, not a default: until its
                     begin= fires, SMIL paints the element at its authored spot —
                     which is (0,0), the corner of the card — so the staggered one
                     showed up as a stray mark outside the illustration. --}}
                <g class="sargam-loader-travel" opacity="0">
                    <path d="M-4.5 -2.8 h2.9 l0.9 1.2 h5.2 v4.6 h-9 z" fill="#e07c24" stroke-linejoin="round" />
                    <animateMotion dur="1.9s" begin="0s" repeatCount="indefinite" keyPoints="0;1" keyTimes="0;1" calcMode="linear">
                        <mpath xlink:href="#sargamWireLeft" href="#sargamWireLeft" />
                    </animateMotion>
                    <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;0.2;0.8;1"
                        dur="1.9s" begin="0s" repeatCount="indefinite" />
                </g>
                <g class="sargam-loader-travel" opacity="0">
                    <path d="M-4.5 -2.8 h2.9 l0.9 1.2 h5.2 v4.6 h-9 z" fill="#e07c24" stroke-linejoin="round" />
                    <animateMotion dur="1.9s" begin="0.95s" repeatCount="indefinite" keyPoints="0;1" keyTimes="0;1" calcMode="linear">
                        <mpath xlink:href="#sargamWireRight" href="#sargamWireRight" />
                    </animateMotion>
                    <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;0.2;0.8;1"
                        dur="1.9s" begin="0.95s" repeatCount="indefinite" />
                </g>
            </svg>

            <h1 class="sargam-loader-title">Your request is on its way</h1>

            <p class="sargam-loader-text">
                We apologise for the inconvenience. Some pages in SARGAM may take longer
                than usual to load while we sort this out. Thank you for your patience.
            </p>

            <hr class="sargam-loader-rule">

            <p class="sargam-loader-status">
                <span>Status: Processing</span>
                <span class="sargam-loader-dots" aria-hidden="true">
                    <span class="sargam-loader-dot"></span>
                    <span class="sargam-loader-dot"></span>
                    <span class="sargam-loader-dot"></span>
                </span>
            </p>
        </div>
    </div>

    <p class="sargam-loader-foot">If a page doesn't load within a minute, refresh or try again shortly.</p>
</div>

<script>
    (function () {
        function hideSargamLoader() {
            var loader = document.getElementById('sargamLoader');
            if (!loader || loader.classList.contains('hidden')) return;
            loader.classList.add('hidden');
            setTimeout(function () { loader.style.display = 'none'; }, 500);
        }

        // sidebar-dynamic-toggle.js and the shells call this by name.
        window.hideSargamLoader = hideSargamLoader;

        window.addEventListener('load', hideSargamLoader);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(hideSargamLoader, 300);
            });
        } else {
            setTimeout(hideSargamLoader, 0);
        }
        // Backstop: never trap the user behind the overlay if load never fires.
        setTimeout(hideSargamLoader, 8000);
    })();
</script>
