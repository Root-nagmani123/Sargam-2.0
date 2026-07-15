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
            default => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    };

    // Fall back to a normal asset URL if the file is missing, so a bad path degrades to a
    // broken link rather than an empty src.
    $logoSargam = $embed('admin_assets/images/logos/logo.svg') ?? asset('admin_assets/images/logos/logo.svg');
    $logoLbsnaa = $embed('admin_assets/images/logos/logo-web.png') ?? asset('admin_assets/images/logos/logo-web.png');
    $logoEmblem = $embed('admin_assets/images/logos/ashoka.png') ?? asset('admin_assets/images/logos/ashoka.png');
    $favicon    = $embed('admin_assets/images/logos/favicon.png') ?? asset('admin_assets/images/logos/favicon.png');

    // Academy backdrop photo, re-encoded down from the login carousel original. Decorative:
    // no asset() fallback, since without it the brand gradient underneath still stands alone.
    $bgPhoto = $embed('admin_assets/images/backgrounds/maintenance-bg.webp');

    // Seconds until the next automatic retry, from either source Laravel may supply it:
    // `artisan down --render` passes $retryAfter into the view directly, while a request-time
    // 503 carries it as a Retry-After header on the HttpException. Clamped so a stray value
    // can't produce a countdown that never ends or hammers the server.
    $retry = null;

    if (isset($retryAfter) && is_numeric($retryAfter)) {
        $retry = (int) $retryAfter;
    } elseif (isset($exception) && method_exists($exception, 'getHeaders')) {
        $retry = (int) ($exception->getHeaders()['Retry-After'] ?? 0);
    }

    $retryAfter = max(15, min($retry ?: 60, 900));
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#004a93">
    <meta name="color-scheme" content="light">
    <meta name="description" content="Sargam 2.0 - Lal Bahadur Shastri National Academy of Administration Portal is temporarily unavailable for scheduled maintenance.">

    <title>Under Maintenance - Sargam 2.0 | Lal Bahadur Shastri National Academy of Administration</title>

    <link rel="icon" type="image/png" href="{{ $favicon }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Devanagari:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
    :root {
        --primary: #004a93;
        --primary-dark: #003366;
        --primary-hover: #003d7a;
        --primary-subtle: #f0f6fc;
        --accent-saffron: #ff9933;
        --accent-green: #138808;
        --text-heading: #0f172a;
        --text-body: #334155;
        --text-muted: #64748b;
        --surface: #ffffff;
        --border: #e2e8f0;
        --radius-md: 0.5rem;
        --radius-3xl: 1rem;
        --transition: 250ms cubic-bezier(0.4, 0, 0.2, 1);
        --font-sans: 'Inter', 'Noto Sans Devanagari', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: var(--font-sans);
        font-size: 1rem;
        color: var(--text-body);
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
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

    /* Brand wash over the photo. The message itself sits on the opaque card, so this only has
       to carry the footer text — hence the heavier top and bottom and the lighter middle,
       which lets the most of the picture through where the card is not covering it. */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        background: linear-gradient(
            180deg,
            rgba(0,51,102,0.78) 0%,
            rgba(0,74,147,0.52) 42%,
            rgba(0,28,58,0.88) 100%);
        pointer-events: none;
    }

    .tricolor {
        height: 4px;
        flex: none;
        background: linear-gradient(90deg,
            var(--accent-saffron) 33.33%,
            #ffffff 33.33%, #ffffff 66.66%,
            var(--accent-green) 66.66%);
    }

    /* ── Institutional header: National Emblem + Government of India + LBSNAA ── */
    .topbar {
        position: relative;
        z-index: 1;
        flex: none;
        background: #fff;
        border-bottom: 1px solid var(--border);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .topbar-inner {
        max-width: 1140px;
        margin: 0 auto;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .emblem { height: 42px; width: auto; flex: none; }

    .govt {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-body);
        line-height: 1.35;
        white-space: nowrap;
    }

    .govt [lang="hi"] {
        font-family: 'Noto Sans Devanagari', var(--font-sans);
        display: block;
    }

    .govt-en { color: var(--text-muted); font-weight: 500; }

    .lbsnaa-logo { width: 200px; max-width: 100%; height: auto; display: block; }

    .page {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 20px;
        position: relative;
        z-index: 1;
    }

    .card {
        background: var(--surface);
        border-radius: var(--radius-3xl);
        box-shadow: 0 20px 60px -12px rgba(0,0,0,0.35);
        max-width: 640px;
        width: 100%;
        padding: 48px 44px 40px;
        text-align: center;
    }

    .brand-logo {
        width: 190px;
        max-width: 60%;
        height: auto;
        margin: 0 auto 32px;
        display: block;
    }

    /* Animated maintenance illustration */
    .illustration { width: 132px; height: 132px; margin: 0 auto 28px; display: block; }
    .gear-lg { transform-origin: 48px 48px; animation: spin 9s linear infinite; }
    .gear-sm { transform-origin: 90px 90px; animation: spin-rev 6s linear infinite; }

    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes spin-rev { to { transform: rotate(-360deg); } }

    .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--primary-subtle);
        color: var(--primary);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 6px 14px;
        border-radius: 999px;
        margin-bottom: 18px;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--accent-saffron);
        animation: pulse 1.8s ease-in-out infinite;
    }

    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.25; } }

    h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.25;
        margin-bottom: 12px;
    }

    .subtitle-hi {
        font-family: 'Noto Sans Devanagari', var(--font-sans);
        font-size: 1.0625rem;
        font-weight: 500;
        color: var(--primary);
        margin-bottom: 16px;
    }

    .message {
        font-size: 1rem;
        line-height: 1.65;
        color: var(--text-muted);
        max-width: 460px;
        margin: 0 auto 28px;
    }

    .retry-bar {
        background: var(--primary-subtle);
        border: 1px solid #dbe7f4;
        border-radius: var(--radius-md);
        padding: 14px 18px;
        font-size: 0.875rem;
        color: var(--text-body);
        margin-bottom: 28px;
    }

    .retry-bar strong { color: var(--primary); font-variant-numeric: tabular-nums; }

    .actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        font-family: inherit;
        font-size: 0.9375rem;
        font-weight: 600;
        padding: 12px 28px;
        border: 1px solid transparent;
        border-radius: var(--radius-md);
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
    }

    .btn-primary {
        background: var(--primary);
        color: #fff;
        box-shadow: 0 4px 14px rgba(0,74,147,0.35);
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,74,147,0.45);
    }

    .btn-secondary {
        background: #fff;
        color: var(--text-body);
        border-color: var(--border);
    }

    .btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

    .btn:focus-visible { outline: 3px solid #ff6b35; outline-offset: 2px; }

    .help {
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid var(--border);
        font-size: 0.8125rem;
        color: var(--text-muted);
    }

    .help a { color: var(--primary); font-weight: 600; text-decoration: none; }
    .help a:hover { text-decoration: underline; }

    .footer {
        position: relative;
        z-index: 1;
        text-align: center;
        color: rgba(255,255,255,0.85);
        font-size: 0.8125rem;
        padding: 0 20px 24px;
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
        .topbar-inner { justify-content: center; gap: 12px; padding: 10px 16px; }
        .emblem { height: 34px; }
        .govt { font-size: 0.75rem; }
        .lbsnaa-logo { width: 165px; }
    }

    @media (max-width: 600px) {
        .card { padding: 36px 24px 32px; border-radius: 0.875rem; }
        h1 { font-size: 1.5rem; }
        .illustration { width: 104px; height: 104px; }
        .brand-logo { margin-bottom: 24px; }
        .actions { flex-direction: column; }
        .btn { width: 100%; justify-content: center; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation: none !important; transition: none !important; }
    }
    </style>
</head>
<body>
    <div class="tricolor"></div>

    <header class="topbar" role="banner">
        <div class="topbar-inner">
            <div class="topbar-left">
                <img src="{{ $logoEmblem }}"
                     alt="National Emblem of India - Satyameva Jayate"
                     class="emblem">
                <span class="govt">
                    <span lang="hi">भारत सरकार</span>
                    <span class="govt-en">Government of India</span>
                </span>
            </div>
            {{-- Right-sized web variant (400px) rather than the full-resolution logo.png,
                 which stays reserved for PDF/print exports. --}}
            <img src="{{ $logoLbsnaa }}"
                 alt="Lal Bahadur Shastri National Academy of Administration, Mussoorie"
                 class="lbsnaa-logo">
        </div>
    </header>

    <main class="page">
        <div class="card" role="status" aria-live="polite">
            <img src="{{ $logoSargam }}"
                 alt="Sargam 2.0 Portal"
                 class="brand-logo">

            <svg class="illustration" viewBox="0 0 128 128" aria-hidden="true" focusable="false">
                <circle cx="64" cy="64" r="62" fill="#f0f6fc"/>
                <g class="gear-lg" fill="#004a93">
                    <path d="M71.5 48L77.85 51.01L77.32 54.36L70.35 55.26L67.01 61.81L70.38 67.98L67.98 70.38L61.81 67.01L55.26 70.35L54.36 77.32L51.01 77.85L48 71.5L40.74 70.35L35.91 75.46L32.89 73.92L34.19 67.01L28.99 61.81L22.08 63.11L20.54 60.09L25.65 55.26L24.5 48L18.15 44.99L18.68 41.64L25.65 40.74L28.99 34.19L25.62 28.02L28.02 25.62L34.19 28.99L40.74 25.65L41.64 18.68L44.99 18.15L48 24.5L55.26 25.65L60.09 20.54L63.11 22.08L61.81 28.99L67.01 34.19L73.92 32.89L75.46 35.91L70.35 40.74Z"/>
                </g>
                <circle cx="48" cy="48" r="9" fill="#f0f6fc"/>
                <g class="gear-sm" fill="#ff9933">
                    <path d="M106 90L110.83 92.63L110.26 95.54L104.78 96.12L101.31 101.31L102.87 106.59L100.4 108.24L96.12 104.78L90 106L87.37 110.83L84.46 110.26L83.88 104.78L78.69 101.31L73.41 102.87L71.76 100.4L75.22 96.12L74 90L69.17 87.37L69.74 84.46L75.22 83.88L78.69 78.69L77.13 73.41L79.6 71.76L83.88 75.22L90 74L92.63 69.17L95.54 69.74L96.12 75.22L101.31 78.69L106.59 77.13L108.24 79.6L104.78 83.88Z"/>
                </g>
                <circle cx="90" cy="90" r="6" fill="#f0f6fc"/>
            </svg>

            <span class="eyebrow"><span class="status-dot"></span> Scheduled Maintenance</span>

            <h1>We&rsquo;ll be back shortly</h1>
            <p class="subtitle-hi">सरगम पोर्टल पर रखरखाव कार्य चल रहा है</p>

            <p class="message">
                Sargam 2.0 is temporarily offline while we carry out planned maintenance to improve
                performance and reliability. No action is needed &mdash; your data is safe and the
                portal will return automatically.
            </p>

            <div class="retry-bar">
                Checking again in <strong id="countdown">{{ $retryAfter }}</strong> seconds&hellip;
            </div>

            <div class="actions">
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M8 3a5 5 0 104.546 2.914l1.36-.63A6.5 6.5 0 118 1.5V0l3 2.5L8 5V3z"/>
                    </svg>
                    Refresh Now
                </button>
                <a href="{{ url('/') }}" class="btn btn-secondary">Go to Home</a>
            </div>

            <div class="help">
                Facing an urgent issue? Contact the Sargam support desk at
                <a href="mailto:support@lbsnaa.gov.in">support@lbsnaa.gov.in</a>
            </div>
        </div>
    </main>

    <div class="footer">
        &copy; {{ date('Y') }} Lal Bahadur Shastri National Academy of Administration, Mussoorie,
        Government of India. All Rights Reserved
    </div>
    <div class="tricolor"></div>

    <p class="sr-only" id="announcer"></p>

    <script>
        (function () {
            var remaining = {{ $retryAfter }};
            var el = document.getElementById('countdown');
            var announcer = document.getElementById('announcer');

            announcer.textContent = 'Sargam is under maintenance. The page will retry automatically in '
                + remaining + ' seconds.';

            setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    location.reload();
                    return;
                }
                el.textContent = remaining;
            }, 1000);
        })();
    </script>
</body>
</html>
