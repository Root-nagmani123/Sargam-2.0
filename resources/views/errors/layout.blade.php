<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code ?? 500 }} - {{ $title ?? 'Server Error' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --error-red: #cc0000;
            --error-text: #6c5d5d;
            --bg-top: #ffeacc;
            --bg-bottom: #d0f0c0;
            --book-maroon: #8b2942;
            --book-blue: #1a365d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: linear-gradient(to bottom, var(--bg-top) 0%, var(--bg-bottom) 100%);
            position: relative;
            overflow-x: hidden;
        }

        .cloud {
            position: absolute;
            background: rgba(255,255,255,0.8);
            border-radius: 50%;
        }
        .cloud-1 { width: 80px; height: 35px; top: 8%; left: 10%; }
        .cloud-2 { width: 100px; height: 40px; top: 12%; right: 15%; }
        .cloud-3 { width: 60px; height: 28px; top: 6%; right: 35%; }
        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: inherit;
            border-radius: 50%;
        }
        .cloud::before { width: 40px; height: 40px; top: -15px; left: 10px; }
        .cloud::after { width: 50px; height: 50px; top: -20px; right: 5px; left: auto; }

        .chakra-wheel {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 280px;
            height: 280px;
            opacity: 0.15;
            pointer-events: none;
        }
        .chakra-wheel svg { width: 100%; height: 100%; }

        .float-elem { position: absolute; z-index: 1; }
        .float-book {
            width: 50px; height: 65px; border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem; font-weight: 700; color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .float-book.book-maroon { background: var(--book-maroon); }
        .float-book.book-blue { background: var(--book-blue); }
        .float-book-1 { top: 18%; left: 15%; transform: rotate(-12deg); }
        .float-book-2 { top: 22%; left: 50%; transform: translateX(-50%) rotate(8deg); }
        .float-book-3 { top: 20%; right: 18%; transform: rotate(-5deg); }

        .float-question {
            top: 15%; right: 25%;
            width: 42px; height: 42px;
            background: var(--error-red); color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; font-weight: 700;
        }

        .float-doc {
            top: 55%; right: 20%;
            width: 75px; height: 95px;
            background: white; border-radius: 4px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            position: relative; padding: 12px 8px;
        }
        .float-doc::before {
            content: ''; display: block;
            height: 8px; background: #eee; border-radius: 2px; margin-bottom: 6px;
        }
        .float-doc::after {
            content: attr(data-code);
            position: absolute; bottom: 8px; right: 8px;
            font-size: 0.75rem; font-weight: 700; color: var(--error-red);
        }

        .building-silhouette {
            position: absolute; bottom: 15%; left: 50%;
            transform: translateX(-50%);
            width: 180px; height: 100px; opacity: 0.12;
        }
        .building-silhouette svg { width: 100%; height: 100%; }

        .error-wrapper { position: relative; z-index: 2; }
        .error-code {
            font-size: clamp(5rem, 18vw, 10rem);
            font-weight: 800; color: var(--error-red);
            line-height: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .error-title {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 600; color: var(--error-text);
            margin-top: 0.5rem;
        }
        .error-message { font-size: 0.95rem; color: var(--error-text); margin-top: 1rem; opacity: 0.9; }
        .error-actions { margin-top: 2rem; }

        .btn-error-home {
            background: var(--error-red) !important; border: none;
            color: white !important; border-radius: 0.5rem;
            box-shadow: 0 4px 14px rgba(204,0,0,0.35);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-error-home:hover {
            background: #b30000 !important; color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(204,0,0,0.4);
        }
        .btn-error-contact {
            background: white !important;
            border: 2px solid var(--error-red);
            color: var(--error-red) !important;
            border-radius: 0.5rem;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            transition: transform 0.2s, background 0.2s;
        }
        .btn-error-contact:hover {
            background: rgba(204,0,0,0.05) !important;
            color: var(--error-red) !important;
            border-color: var(--error-red);
            transform: translateY(-2px);
        }
        .btn-error-secondary {
            background: rgba(108,93,93,0.12) !important;
            border: 2px solid var(--error-text);
            color: var(--error-text) !important;
            border-radius: 0.5rem;
            transition: transform 0.2s, background 0.2s;
        }
        .btn-error-secondary:hover {
            background: rgba(108,93,93,0.2) !important;
            color: var(--error-text) !important;
            border-color: var(--error-text);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .float-elem { display: none; }
            .chakra-wheel { width: 180px; height: 180px; }
        }
    </style>
</head>
<body>
    <div class="cloud cloud-1"></div>
    <div class="cloud cloud-2"></div>
    <div class="cloud cloud-3"></div>

    <div class="chakra-wheel" aria-hidden="true">
        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="45" stroke="#6c5d5d" stroke-width="1"/>
            <circle cx="50" cy="50" r="5" fill="#6c5d5d"/>
            <line x1="50" y1="8" x2="50" y2="2" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="68.82" y1="13.82" x2="72.43" y2="9.21" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="86.18" y1="13.82" x2="90.79" y2="9.21" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="92" y1="32" x2="98" y2="32" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="86.18" y1="50" x2="90.79" y2="50" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="92" y1="68" x2="98" y2="68" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="86.18" y1="86.18" x2="90.79" y2="90.79" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="68.82" y1="86.18" x2="72.43" y2="90.79" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="50" y1="92" x2="50" y2="98" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="31.18" y1="86.18" x2="27.57" y2="90.79" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="13.82" y1="86.18" x2="9.21" y2="90.79" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="8" y1="68" x2="2" y2="68" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="13.82" y1="50" x2="9.21" y2="50" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="8" y1="32" x2="2" y2="32" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="13.82" y1="13.82" x2="9.21" y2="9.21" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="31.18" y1="13.82" x2="27.57" y2="9.21" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="50" y1="58" x2="50" y2="42" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="58" y1="50" x2="42" y2="50" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="63.4" y1="63.4" x2="50.8" y2="50.8" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="63.4" y1="36.6" x2="50.8" y2="49.2" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="36.6" y1="36.6" x2="49.2" y2="49.2" stroke="#6c5d5d" stroke-width="1.5"/>
            <line x1="36.6" y1="63.4" x2="49.2" y2="50.8" stroke="#6c5d5d" stroke-width="1.5"/>
        </svg>
    </div>

    <div class="float-elem float-book float-book-1">IAS</div>
    <div class="float-elem float-book float-book-2 book-maroon">IAS</div>
    <div class="float-elem float-book float-book-3 book-blue">IAS</div>
    <div class="float-elem float-question">?</div>
    <div class="float-elem float-doc" data-code="{{ $code ?? 500 }}"></div>

    <div class="building-silhouette" aria-hidden="true">
        <svg viewBox="0 0 100 60" fill="#6c5d5d">
            <rect x="10" y="20" width="15" height="40"/>
            <rect x="28" y="10" width="15" height="50"/>
            <rect x="46" y="5" width="15" height="55"/>
            <rect x="64" y="15" width="15" height="45"/>
            <rect x="82" y="25" width="15" height="35"/>
        </svg>
    </div>

    <div class="error-wrapper min-vh-100 d-flex flex-column align-items-center justify-content-center py-4 px-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8 text-center">
                    <div class="error-code">{{ $code ?? 500 }}</div>
                    <h1 class="error-title">{{ $title ?? 'Server Error' }}</h1>
                    @if(!empty($message))
                        <p class="error-message">{{ $message }}</p>
                    @endif
                    <div class="error-actions d-flex flex-wrap gap-3 justify-content-center">
                        @yield('buttons')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
