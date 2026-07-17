@php
    // Logos are inlined as data URIs rather than linked with asset(). `artisan down --render`
    // pre-renders this view from the console, where asset() has no incoming request to derive a
    // root from and falls back to config('app.url') — which bakes in a URL that is wrong for any
    // install not served from the domain root. Inlining also keeps the page intact when the app
    // is down, which is the only time it is ever shown.
    $embed = function (string $path) {
        $file = public_path($path);

        if (! is_file($file) || ! ($bytes = @file_get_contents($file))) {
            return null;
        }

        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'ico' => 'image/x-icon',
            default => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    };

    // Fall back to a normal asset URL if the file is missing, so a bad path degrades to a
    // broken link rather than an empty src.
    // favicon.ico, not .png: there is no favicon.png, so the embed returned null and the
    // asset() fallback baked an absolute http://localhost/... URL into the pre-rendered
    // template — the exact failure the note above warns about.
    $logoSargam = $embed('admin_assets/images/logos/logo.svg') ?? asset('admin_assets/images/logos/logo.svg');
    $favicon    = $embed('admin_assets/images/logos/favicon.ico') ?? asset('admin_assets/images/logos/favicon.ico');

    // Academy backdrop photo, re-encoded down from the login carousel original. Decorative:
    // no asset() fallback, since without it the brand wash underneath still stands alone.
    $bgPhoto = $embed('https://www.lbsnaa.gov.in/news_images/1781608933_A03A9454.JPG');

    // Seconds until the next automatic retry, from either source Laravel may supply it:
    // `artisan down --render` passes $retryAfter into the view directly, while a request-time
    // 503 carries it as a Retry-After header on the HttpException. Clamped so a stray value
    // can't produce a countdown that never ends or hammers the server. The ceiling is the
    // 105-minute maintenance window (shown as "1:45 hours"); a longer real window is still
    // shown as 1:45, and the page simply retries again when it elapses.
    $maxRetryAfter = 105 * 60;

    $retry = null;

    if (isset($retryAfter) && is_numeric($retryAfter)) {
        $retry = (int) $retryAfter;
    } elseif (isset($exception) && method_exists($exception, 'getHeaders')) {
        $retry = (int) ($exception->getHeaders()['Retry-After'] ?? 0);
    }

    $retryAfter = max(15, min($retry ?: 60, $maxRetryAfter));
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0d1829">
    <meta name="color-scheme" content="light">
    <meta name="description" content="Sargam 2.0 - Lal Bahadur Shastri National Academy of Administration Portal is temporarily unavailable for scheduled maintenance.">

    <title>Under Maintenance - Sargam 2.0 | Lal Bahadur Shastri National Academy of Administration</title>

    <link rel="icon" type="image/png" href="{{ $favicon }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Devanagari:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
    :root {
        --primary: #004a93;
        --navy: #0d1829;
        --text-heading: #1c2b47;
        --text-muted: #6b7a90;
        --surface: #ffffff;
        --btn-waiting: #7f9dc4;
        --font-sans: 'Inter', 'Noto Sans Devanagari', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        /* Driven by the A+ / A / A- controls; every type size below is relative to it. */
        --type-scale: 1;
    }

    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: var(--font-sans);
        font-size: calc(1rem * var(--type-scale));
        color: var(--text-heading);
        background: var(--navy);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        -webkit-font-smoothing: antialiased;
    }

@if ($bgPhoto)
    /* Full-bleed Academy photograph. Carried on a fixed pseudo-element rather than
       background-attachment: fixed, which janks and misbehaves on mobile Safari. */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background-image: url('{{ $bgPhoto }}');
        background-size: cover;
        background-position: center;
        pointer-events: none;
    }
@endif

    /* Brand wash over the photo. The message sits on an opaque card, so this only has to
       carry the footer text — kept light enough to leave the photograph legible. */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        background: linear-gradient(180deg, rgba(13,24,41,0.55) 0%, rgba(16,45,84,0.42) 45%, rgba(13,24,41,0.62) 100%);
        pointer-events: none;
    }

    /* ── Government bar ──────────────────────────────────────────────────── */
    .gov-bar {
        position: relative;
        z-index: 2;
        flex: none;
        background: var(--navy);
    }

    .gov-bar-inner {
        max-width: 1360px;
        margin: 0 auto;
        padding: 18px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .gov-left { display: flex; align-items: center; gap: 10px; min-width: 0; }

    .gov-flag {
        width: 26px;
        height: auto;
        flex: none;
        border-radius: 2px;
        box-shadow: 0 0 0 1px rgba(255,255,255,0.25);
    }

    .gov-text {
        color: #fff;
        font-size: calc(0.875rem * var(--type-scale));
        font-weight: 500;
        white-space: nowrap;
    }

    .gov-text [lang="hi"] { font-family: 'Noto Sans Devanagari', var(--font-sans); }
    .gov-text .sep { opacity: 0.45; margin: 0 2px; }

    .gov-right { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }

    .gov-divider { width: 1px; height: 18px; background: rgba(255,255,255,0.22); }

    .skip-link {
        color: #fff;
        font-size: calc(0.875rem * var(--type-scale));
        text-decoration: none;
        white-space: nowrap;
    }

    .skip-link:hover { text-decoration: underline; }

    .text-size { display: flex; align-items: center; gap: 2px; }

    .text-size button {
        font-family: inherit;
        background: none;
        border: 0;
        color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        cursor: pointer;
        line-height: 1.4;
    }

    .text-size button[data-size="large"]  { font-size: calc(1rem * var(--type-scale)); }
    .text-size button[data-size="normal"] { font-size: calc(0.875rem * var(--type-scale)); }
    .text-size button[data-size="small"]  { font-size: calc(0.75rem * var(--type-scale)); }

    .text-size button:hover { background: rgba(255,255,255,0.12); }
    .text-size button.is-active { background: rgba(255,255,255,0.2); font-weight: 600; }

    .lang { position: relative; }

    .lang-btn {
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: none;
        border: 0;
        color: #fff;
        font-size: calc(0.875rem * var(--type-scale));
        padding: 4px 6px;
        border-radius: 4px;
        cursor: pointer;
    }

    .lang-btn:hover { background: rgba(255,255,255,0.12); }
    .lang-btn svg { flex: none; }
    .lang-caret { transition: transform 200ms ease; }
    .lang-btn[aria-expanded="true"] .lang-caret { transform: rotate(180deg); }

    .lang-menu {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        min-width: 132px;
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.28);
        padding: 4px;
        list-style: none;
        z-index: 3;
    }

    .lang-menu[hidden] { display: none; }

    .lang-menu button {
        font-family: inherit;
        display: block;
        width: 100%;
        text-align: left;
        background: none;
        border: 0;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: calc(0.875rem * var(--type-scale));
        color: var(--text-heading);
        cursor: pointer;
    }

    .lang-menu button:hover { background: #f1f5f9; }
    .lang-menu button[aria-current="true"] { color: var(--primary); font-weight: 600; }
    .lang-menu [lang="hi"] { font-family: 'Noto Sans Devanagari', var(--font-sans); }

    /* ── Card ────────────────────────────────────────────────────────────── */
    .page {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        z-index: 1;
    }

    .card {
        background: var(--surface);
        border-radius: 10px;
        box-shadow: 0 20px 60px -12px rgba(0,0,0,0.35);
        max-width: 585px;
        width: 100%;
        padding: 44px 44px 40px;
        text-align: center;
    }

    .card:focus { outline: none; }

    .brand-logo {
        width: 168px;
        max-width: 60%;
        height: auto;
        margin: 0 auto 30px;
        display: block;
    }

    .tool-circle {
        width: 96px;
        height: 96px;
        margin: 0 auto 26px;
        border-radius: 50%;
        background: #eef4fb;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tool-icon { width: 50px; height: 50px; display: block; }
    .tool-wrench { animation: tool-nudge 3.2s ease-in-out infinite; transform-origin: 32px 32px; }

    @keyframes tool-nudge {
        0%, 62%, 100% { transform: rotate(0deg); }
        72%           { transform: rotate(-11deg); }
        84%           { transform: rotate(3deg); }
    }

    h1 {
        font-size: calc(1.625rem * var(--type-scale));
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.3;
        margin-bottom: 6px;
    }

    .alt-title {
        font-size: calc(0.9375rem * var(--type-scale));
        font-weight: 400;
        color: var(--text-heading);
        margin-bottom: 18px;
    }

    .message {
        font-size: calc(0.9375rem * var(--type-scale));
        line-height: 1.65;
        color: var(--text-muted);
        max-width: 430px;
        margin: 0 auto 30px;
    }

    /* Devanagari needs a little more leading than Inter to stay comfortable. */
    [lang="hi"] .alt-title,
    [lang="hi"] .message { line-height: 1.75; }

    .btn {
        font-family: inherit;
        font-size: calc(0.9375rem * var(--type-scale));
        font-weight: 500;
        color: #fff;
        background: var(--btn-waiting);
        padding: 12px 26px;
        border: 0;
        border-radius: 6px;
        cursor: not-allowed;
        transition: background 200ms ease;
    }

    /* Enabled only once the wait is over — until then the label is the countdown. */
    .btn:not([disabled]) {
        background: var(--primary);
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,74,147,0.35);
    }

    .btn:not([disabled]):hover { background: #003d7a; }
    .btn:focus-visible { outline: 3px solid #ff9933; outline-offset: 2px; }

    .footer {
        position: relative;
        z-index: 1;
        flex: none;
        text-align: center;
        color: rgba(255,255,255,0.9);
        font-size: calc(0.8125rem * var(--type-scale));
        padding: 0 20px 28px;
    }

    .sr-only {
        position: absolute;
        width: 1px; height: 1px;
        padding: 0; margin: -1px;
        overflow: hidden;
        clip: rect(0,0,0,0);
        white-space: nowrap;
        border: 0;
    }

    @media (max-width: 780px) {
        .gov-bar-inner { padding: 12px 16px; gap: 10px; }
        .gov-right { gap: 10px; width: 100%; justify-content: space-between; }
        .skip-link { display: none; }
    }

    @media (max-width: 600px) {
        .card { padding: 32px 22px 30px; }
        h1 { font-size: calc(1.375rem * var(--type-scale)); }
        .tool-circle { width: 82px; height: 82px; }
        .tool-icon { width: 42px; height: 42px; }
        .btn { width: 100%; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation: none !important; transition: none !important; }
    }
    </style>
</head>
<body>
    <header class="gov-bar" role="banner">
        <div class="gov-bar-inner">
            <div class="gov-left">
                {{-- Drawn inline rather than linked: this page is served precisely when the
                     app is down, so it must not depend on an image request (the login page's
                     flag points at a Wikipedia URL, which is not safe to rely on here). --}}
                <svg class="gov-flag" viewBox="0 0 36 24" role="img" aria-label="National Flag of India">
                    <rect width="36" height="24" fill="#fff"/>
                    <rect width="36" height="8" fill="#ff9933"/>
                    <rect y="16" width="36" height="8" fill="#138808"/>
                    <g stroke="#000080" fill="none">
                        <circle cx="18" cy="12" r="3.3" stroke-width="0.7"/>
                        @for ($i = 0; $i < 24; $i++)
                            <line x1="18" y1="12" x2="18" y2="8.7" stroke-width="0.25"
                                  transform="rotate({{ $i * 15 }} 18 12)"/>
                        @endfor
                    </g>
                    <circle cx="18" cy="12" r="0.65" fill="#000080"/>
                </svg>
                {{-- The national lockup is bilingual by convention and stays that way in
                     both languages — translating the English half just rendered it twice. --}}
                <span class="gov-text">
                    <span lang="hi">भारत सरकार</span>
                    <span class="sep">|</span>
                    <span lang="en">Government of India</span>
                </span>
            </div>

            <nav class="gov-right" aria-label="Accessibility Options">
                <a href="#maint-card" class="skip-link" data-i18n="skip">Skip to content</a>
                <span class="gov-divider" aria-hidden="true"></span>

                <div class="text-size" role="group" data-i18n-label="textSize" aria-label="Text Size">
                    <button type="button" data-size="large" data-i18n-label="textLarge" aria-label="Increase text size">A+</button>
                    <button type="button" data-size="normal" class="is-active" data-i18n-label="textNormal" aria-label="Normal text size">A</button>
                    <button type="button" data-size="small" data-i18n-label="textSmall" aria-label="Decrease text size">A-</button>
                </div>

                <span class="gov-divider" aria-hidden="true"></span>

                <div class="lang">
                    <button type="button" class="lang-btn" id="langBtn" aria-expanded="false" aria-haspopup="true" aria-controls="langMenu">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M8 0a8 8 0 100 16A8 8 0 008 0zM1.5 8c0-.6.08-1.18.23-1.73L3 7.5l1.5 1.5v1.5l1.5 1.5v1.86A6.51 6.51 0 011.5 8zm11.4 4.36A6.47 6.47 0 019 14.46V13a1.5 1.5 0 00-1.5-1.5v-1.5l3-3-1.5-1.5H7l-1-1V3.6a6.5 6.5 0 016.9 8.76z"/>
                        </svg>
                        <span id="langLabel">English</span>
                        <svg class="lang-caret" width="10" height="10" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M8 11L3 6h10z"/>
                        </svg>
                    </button>
                    <ul class="lang-menu" id="langMenu" role="menu" hidden>
                        <li role="none"><button type="button" role="menuitem" data-lang="en" lang="en" aria-current="true">English</button></li>
                        <li role="none"><button type="button" role="menuitem" data-lang="hi" lang="hi" aria-current="false">हिन्दी</button></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <main class="page">
        <div class="card" id="maint-card" role="status" aria-live="polite" tabindex="-1">
            <img src="{{ $logoSargam }}" alt="Sargam 2.0" class="brand-logo">

            <div class="tool-circle">
                <svg class="tool-icon" viewBox="0 0 64 64" fill="none" aria-hidden="true" focusable="false">
                    {{-- Screwdriver, laid bottom-left to top-right --}}
                    <g transform="rotate(45 32 32)">
                        <rect x="28.5" y="7" width="7" height="17" rx="3.5" fill="#1e4d8f"/>
                        <rect x="30" y="23" width="4" height="3" fill="#16375f"/>
                        <rect x="30.6" y="26" width="2.8" height="21" fill="#3d7ac4"/>
                        <path d="M30.6 47h2.8l-1.4 5z" fill="#3d7ac4"/>
                    </g>
                    {{-- Wrench, crossed over it. The -45° sits on an OUTER group: the
                         nudge animation below sets `transform` on .tool-wrench, and a CSS
                         transform replaces the element's transform attribute outright
                         rather than composing with it — so keeping both on one element
                         left the wrench standing upright. --}}
                    <g transform="rotate(-45 32 32)">
                        <g class="tool-wrench">
                            <rect x="29.8" y="24" width="4.4" height="28" rx="2.2" fill="#d92b2b"/>
                            {{-- Open-end head: a stroked circle with one dash gap, turned so
                                 the gap faces away from the handle. Cheaper and cleaner than
                                 a hand-plotted C outline. --}}
                            <circle cx="32" cy="20" r="7.5" fill="none" stroke="#d92b2b" stroke-width="4.2"
                                    stroke-dasharray="36 11" transform="rotate(-47.5 32 20)"/>
                        </g>
                    </g>
                </svg>
            </div>

            <h1 data-i18n="title">Sargam is Under Maintenance</h1>
            {{-- The counterpart language, always shown in brackets under the heading. --}}
            <p class="alt-title" lang="hi" data-i18n="altTitle">(सरगम में मेंटेनेंस का काम चल रहा है)</p>

            <p class="message" data-i18n="message">
                Sargam 2.0 is temporarily offline while we carry out prepared maintenance to improve
                performance and reliability.
            </p>

            <button type="button" class="btn" id="refreshBtn" disabled>Wait to Refresh</button>
        </div>
    </main>

    <footer class="footer" data-i18n="footer">
        &copy; {{ date('Y') }} LBSNAA Mussoorie, Government of India. All Rights Reserved
    </footer>

    <p class="sr-only" id="announcer" aria-live="polite"></p>

    <script>
        (function () {
            'use strict';

            var YEAR = {{ (int) date('Y') }};

            /* Strings live here rather than in lang/: this page renders while the app is
               down (artisan down --render), and the project has no `hi` locale to resolve
               against anyway. Swapping is client-side so no request is needed. */
            var T = {
                en: {

                    skip: 'Skip to content',
                    textSize: 'Text Size', textLarge: 'Increase text size',
                    textNormal: 'Normal text size', textSmall: 'Decrease text size',
                    langName: 'English',
                    title: 'Sargam is Under Maintenance',
                    altTitle: '(सरगम में मेंटेनेंस का काम चल रहा है)',
                    message: 'Sargam 2.0 is temporarily offline while we carry out prepared maintenance to improve performance and reliability.',
                    waitFor: 'Wait for {t} to Refresh',
                    refreshNow: 'Refresh Now',
                    hours: 'hours', minutes: 'minutes', seconds: 'seconds',
                    footer: '© ' + YEAR + ' LBSNAA Mussoorie, Government of India. All Rights Reserved',
                    announce: 'Sargam is under maintenance. The page will retry automatically.'
                },
                hi: {

                    skip: 'मुख्य सामग्री पर जाएँ',
                    textSize: 'अक्षर आकार', textLarge: 'अक्षर आकार बढ़ाएँ',
                    textNormal: 'सामान्य अक्षर आकार', textSmall: 'अक्षर आकार घटाएँ',
                    langName: 'हिन्दी',
                    title: 'सरगम में मेंटेनेंस का काम चल रहा है',
                    altTitle: '(Sargam is Under Maintenance)',
                    message: 'सरगम 2.0 अस्थायी रूप से ऑफ़लाइन है, क्योंकि प्रदर्शन और विश्वसनीयता बेहतर बनाने के लिए नियोजित रखरखाव किया जा रहा है।',
                    waitFor: 'रीफ़्रेश करने के लिए {t} प्रतीक्षा करें',
                    refreshNow: 'अभी रीफ़्रेश करें',
                    hours: 'घंटे', minutes: 'मिनट', seconds: 'सेकंड',
                    footer: '© ' + YEAR + ' एलबीएसएनएए मसूरी, भारत सरकार। सर्वाधिकार सुरक्षित।',
                    announce: 'सरगम में रखरखाव कार्य चल रहा है। पृष्ठ स्वतः पुनः प्रयास करेगा।'
                }
            };

            var SCALES = { small: 0.875, normal: 1, large: 1.125 };

            var remaining = {{ $retryAfter }};
            var lang = 'en';

            var btn = document.getElementById('refreshBtn');
            var announcer = document.getElementById('announcer');
            var langBtn = document.getElementById('langBtn');
            var langMenu = document.getElementById('langMenu');
            var langLabel = document.getElementById('langLabel');

            function store(key, value) {
                try { localStorage.setItem(key, value); } catch (e) { /* private mode */ }
            }
            function read(key) {
                try { return localStorage.getItem(key); } catch (e) { return null; }
            }

            function pad(n) { return (n < 10 ? '0' : '') + n; }

            /* "1:45 hours" over an hour, "9:05 minutes" under one, plain seconds at the end. */
            function formatWait(sec) {
                var t = T[lang];
                if (sec >= 3600) {
                    return Math.floor(sec / 3600) + ':' + pad(Math.floor((sec % 3600) / 60)) + ' ' + t.hours;
                }
                if (sec >= 60) {
                    return Math.floor(sec / 60) + ':' + pad(sec % 60) + ' ' + t.minutes;
                }
                return sec + ' ' + t.seconds;
            }

            function paintButton() {
                if (remaining > 0) {
                    btn.disabled = true;
                    btn.textContent = T[lang].waitFor.replace('{t}', formatWait(remaining));
                } else {
                    btn.disabled = false;
                    btn.textContent = T[lang].refreshNow;
                }
            }

            function applyLang(next) {
                lang = T[next] ? next : 'en';
                var t = T[lang];

                document.documentElement.setAttribute('lang', lang);

                document.querySelectorAll('[data-i18n]').forEach(function (el) {
                    var key = el.getAttribute('data-i18n');
                    if (t[key]) { el.textContent = t[key]; }
                });
                document.querySelectorAll('[data-i18n-label]').forEach(function (el) {
                    var key = el.getAttribute('data-i18n-label');
                    if (t[key]) { el.setAttribute('aria-label', t[key]); }
                });

                // The bracketed line always carries the OTHER language, so tag it as such.
                var alt = document.querySelector('.alt-title');
                if (alt) { alt.setAttribute('lang', lang === 'en' ? 'hi' : 'en'); }

                langLabel.textContent = t.langName;
                langMenu.querySelectorAll('[data-lang]').forEach(function (b) {
                    b.setAttribute('aria-current', b.getAttribute('data-lang') === lang ? 'true' : 'false');
                });

                announcer.textContent = t.announce;
                paintButton();
                store('sargamMaintLang', lang);
            }

            function applyScale(size) {
                var scale = SCALES[size] || 1;
                document.documentElement.style.setProperty('--type-scale', scale);
                document.querySelectorAll('.text-size button').forEach(function (b) {
                    b.classList.toggle('is-active', b.getAttribute('data-size') === size);
                });
                store('sargamMaintScale', size);
            }

            document.querySelectorAll('.text-size button').forEach(function (b) {
                b.addEventListener('click', function () { applyScale(b.getAttribute('data-size')); });
            });

            function closeLangMenu() {
                langMenu.hidden = true;
                langBtn.setAttribute('aria-expanded', 'false');
            }

            langBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = langMenu.hidden;
                langMenu.hidden = !open;
                langBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            langMenu.querySelectorAll('[data-lang]').forEach(function (b) {
                b.addEventListener('click', function () {
                    applyLang(b.getAttribute('data-lang'));
                    closeLangMenu();
                    langBtn.focus();
                });
            });

            document.addEventListener('click', function (e) {
                if (!langMenu.hidden && !e.target.closest('.lang')) { closeLangMenu(); }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !langMenu.hidden) { closeLangMenu(); langBtn.focus(); }
            });

            btn.addEventListener('click', function () {
                if (!btn.disabled) { location.reload(); }
            });

            // Restore the visitor's choices across the automatic reload below.
            applyScale(read('sargamMaintScale') || 'normal');
            applyLang(read('sargamMaintLang') || 'en');

            setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    remaining = 0;
                    paintButton();
                    location.reload();
                    return;
                }
                paintButton();
            }, 1000);
        })();
    </script>
</body>
</html>
