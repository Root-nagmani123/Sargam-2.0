{{-- ==========================================================================
     FC portal header — visual language ported from resources/views/auth/login.blade.php
     (GIGW toolbar → tricolour strip → white sticky brand bar), built on the
     --ds-* design tokens from public/css/sargam-app.css (see docs/design.md).

     Styles live in the .fc-* section of public/css/sargam-app.css and the
     accessibility controls in public/js/fc-chrome.js — one cacheable copy each,
     rather than ~17 KB inlined into all 31 FC pages (docs/design.md rule 1).

     Structural contracts kept intentionally:
       • `.top-header > .container` / `.header > .container` — widened by
         fc/registration/partials/fc-form-theme.blade.php on the form pages.
       • `#uw-widget-custom-trigger` — admin_assets/js/weights.js binds to this
         id at line 778 without a null guard. That line is currently unreachable
         (the script throws at line 117 on FC pages, where the widget panel it
         expects does not exist), but the hidden anchor below is kept so that
         repairing weights.js cannot turn into a TypeError here.
       • `#navbarNav` fixed bottom bar on phones/tablets.
     ========================================================================== --}}
@php
    $fcAssetV = fn (string $p) => asset($p) . '?v=' . (@filemtime(public_path($p)) ?: 1);
@endphp

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
            {{-- Not rendered as a control: the widget it would open (#uw-main) exists
                 nowhere in the app, and weights.js throws before it can bind a handler,
                 so a visible "Accessibility" button would do nothing while sitting next
                 to two that work. Kept hidden purely to satisfy that unguarded
                 getElementById. The real accessibility controls are the text-size and
                 contrast buttons above. --}}
            <a id="uw-widget-custom-trigger" hidden aria-hidden="true" tabindex="-1"></a>
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

