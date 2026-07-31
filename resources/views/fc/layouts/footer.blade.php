{{-- ==========================================================================
     FC portal footer — visual language ported from resources/views/auth/login.blade.php
     (tricolour strip → dark info bar → GIGW mandatory links → NeGD strip), built
     on the --ds-* design tokens from public/css/sargam-app.css (see docs/design.md).

     Structural contract kept intentionally:
       • `footer .container` — widened to the 1640px shell by
         fc/registration/partials/fc-form-theme.blade.php on the form pages.
     ========================================================================== --}}
<style>
    .fc-footer {
        /* National accents + the slate surfaces the login footer uses; everything
           else comes from the --ds-* token layer. */
        --fc-saffron: #ff9933;
        --fc-green: #138808;
        --fc-footer-bg: rgba(15, 23, 42, 0.97);
        --fc-footer-bg-2: rgba(15, 23, 42, 0.95);
        --fc-footer-bg-3: rgba(10, 15, 30, 0.97);
        --fc-transition: 250ms cubic-bezier(0.4, 0, 0.2, 1);

        background: transparent;
        padding: 0;
        color: #fff;
        /* body is a min-height:100vh flex column — this pins the footer to the
           bottom on pages whose content is shorter than the viewport. */
        margin-top: auto;
    }

    .fc-footer-tricolor {
        height: 4px;
        background: linear-gradient(90deg,
                var(--fc-saffron) 33.33%,
                #ffffff 33.33%, #ffffff 66.66%,
                var(--fc-green) 66.66%);
    }

    /* ===== Primary info bar ===== */
    .fc-footer-main {
        background: var(--fc-footer-bg);
        padding: 0.875rem 0;
    }

    .fc-footer-info {
        font-size: 0.8125rem;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
    }

    .fc-footer-info a {
        color: rgba(255, 255, 255, 0.95);
        text-decoration: none;
        font-weight: 500;
    }

    .fc-footer-info a:hover,
    .fc-footer-info a:focus {
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .fc-footer-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        /* keeps the badge right-aligned even when the info line wraps it to its own row */
        margin-left: auto;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 0.375rem 0.875rem;
        border-radius: var(--ds-radius-2);
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all var(--fc-transition);
    }

    .fc-footer-badge:hover,
    .fc-footer-badge:focus {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
    }

    /* ===== GIGW mandatory links ===== */
    .fc-footer-links {
        background: var(--fc-footer-bg-2);
        padding: 0.625rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .fc-footer-links > .container {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
    }

    .fc-footer-links a {
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.75rem;
        font-weight: 400;
        text-decoration: none;
        padding: 0.125rem 0;
        transition: color var(--fc-transition);
    }

    .fc-footer-links a:hover,
    .fc-footer-links a:focus {
        color: #fff;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .fc-footer-links .fc-link-sep {
        color: rgba(255, 255, 255, 0.3);
        font-size: 0.625rem;
    }

    .fc-footer-updated {
        width: 100%;
        text-align: center;
        font-size: 0.6875rem;
        color: rgba(255, 255, 255, 0.6);
        padding-top: 0.375rem;
    }

    /* ===== NeGD / Digital India strip ===== */
    .fc-footer-negd {
        background: var(--fc-footer-bg-3);
        padding: 0.5rem 0;
    }

    .fc-footer-negd a {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.8125rem;
        font-weight: 400;
        text-decoration: none;
        transition: color var(--fc-transition);
    }

    .fc-footer-negd a:hover,
    .fc-footer-negd a:focus {
        color: #fff;
    }

    /* The Digital India mark is dark-on-transparent, so it needs a light chip to
       stay legible against the slate strip. */
    .fc-footer-negd img {
        height: 26px;
        width: auto;
        background: #fff;
        padding: 2px 6px;
        border-radius: var(--ds-radius-1);
    }

    /* Keyboard focus — WCAG, mirrors the login page */
    .fc-footer :focus-visible {
        outline: 3px solid #ff6b35;
        outline-offset: 3px;
    }

    body.high-contrast .fc-footer-main,
    body.high-contrast .fc-footer-links,
    body.high-contrast .fc-footer-negd {
        background: #000;
    }

    body.high-contrast .fc-footer-info,
    body.high-contrast .fc-footer-links a,
    body.high-contrast .fc-footer-negd a,
    body.high-contrast .fc-footer-updated {
        color: #fff;
    }

    @media (max-width: 575.98px) {
        .fc-footer-main {
            text-align: center;
        }

        /* Centre the badge with the stacked text. Auto margins are used rather than
           `justify-content`, which Bootstrap's .justify-content-between utility wins
           on `!important`. */
        .fc-footer-badge {
            margin-right: auto;
        }

        .fc-footer-links > .container {
            gap: 0.25rem 0.75rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .fc-footer * {
            transition-duration: 0.01ms !important;
        }
    }
</style>

<footer class="fc-footer" role="contentinfo">
    <div class="fc-footer-tricolor"></div>

    <div class="fc-footer-main">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 w-100">
                <div class="fc-footer-info">
                    <span>&copy; {{ date('Y') }} Lal Bahadur Shastri National Academy of Administration,
                        Mussoorie, Government of India. All Rights Reserved</span>
                    <span class="d-none d-md-inline mx-2">|</span>
                    <span class="d-none d-sm-inline">Support:
                        <a href="mailto:support.lbsnaa@nic.in">support[dot]lbsnaa[at]nic[dot]in</a></span>
                    <span class="d-none d-sm-inline mx-2">|</span>
                    {{-- own line on phones, where the separators above are hidden --}}
                    <span class="d-block d-sm-inline">Phone: 0135-2222346 (Mon–Fri, 9:00 AM–5:30 PM)</span>
                </div>
                <a href="{{ route('fc.faqs.all') }}" class="fc-footer-badge">
                    <i class="bi bi-life-preserver" aria-hidden="true"></i> Need Help
                </a>
            </div>
        </div>
    </div>

    <!-- GIGW: mandatory footer links -->
    <div class="fc-footer-links">
        <div class="container">
            <a href="#" title="Website Policies">Web Information Manager</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="#" title="Privacy Policy">Privacy Policy</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="#" title="Terms and Conditions">Terms &amp; Conditions</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="#" title="Copyright Policy">Copyright Policy</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="#" title="Hyperlinking Policy">Hyperlinking Policy</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="#" title="Accessibility Statement">Accessibility Statement</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="#" title="Disclaimer">Disclaimer</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="{{ route('fc.faqs.all') }}" title="Help &amp; FAQs">Help</a>
            <span class="fc-link-sep" aria-hidden="true">|</span>
            <a href="#" title="Sitemap">Sitemap</a>
            <div class="fc-footer-updated">
                <span>Last Updated: {{ date('d M Y') }}</span>
            </div>
        </div>
    </div>

    <div class="fc-footer-negd">
        <div class="container text-center">
            <a href="https://negd.gov.in/" target="_blank" rel="noopener noreferrer"
                aria-label="Powered by National e-Governance Division">
                <img src="{{ asset('images/digital.png') }}" alt="Digital India" loading="lazy"
                    onerror="this.style.display='none'">
                <span>Powered by <strong>National e-Governance Division</strong>, MeitY</span>
            </a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
    crossorigin="anonymous"></script>
<script src="{{ asset('admin_assets/js/google-translate.js') }}"></script>
<script src="{{ asset('admin_assets/js/weights.js') }}"></script>

<!-- Google Translate callback (invoked by the widget loader when a
     #google_translate_element target is present on the page) -->
<script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en'
        }, 'google_translate_element');
    }
</script>
