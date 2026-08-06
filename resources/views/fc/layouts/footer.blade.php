{{-- ==========================================================================
     FC portal footer — visual language ported from resources/views/auth/login.blade.php
     (tricolour strip → dark info bar → GIGW mandatory links → NeGD strip), built
     on the --ds-* design tokens from public/css/sargam-app.css (see docs/design.md).

     Styles live in the .fc-* section of public/css/sargam-app.css — one
     cacheable copy rather than inlined into all 31 FC pages (design.md rule 1).

     Structural contract kept intentionally:
       • `footer .container` — widened to the 1640px shell by
         fc/registration/partials/fc-form-theme.blade.php on the form pages.
     ========================================================================== --}}
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
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
    <script src="{{ asset('admin_assets/js/google-translate.js') }}"></script>
    <script src="{{ asset('admin_assets/js/weights.js') }}"></script>

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
