<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Preview</title>
    {{-- The admin theme (Bootstrap + brand palette), as the admin layout loads it. --}}
    <link rel="stylesheet" href="{{ asset('admin_assets/css/styles.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- The design-system layer (docs/design.md) - tokens and .ds-* components. --}}
    <link rel="stylesheet" href="{{ asset('css/sargam-app.css') }}?v={{ @filemtime(public_path('css/sargam-app.css')) ?: time() }}">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--ds-canvas);
            color: var(--ds-ink);
        }
        .pv-bar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: var(--ds-space-2);
            padding: var(--ds-space-2) var(--ds-space-3);
            background: var(--ds-surface);
            border-bottom: 1px solid var(--ds-line);
            box-shadow: var(--ds-shadow-sm);
        }
        .pv-bar .ds-page-header { margin: 0; }
        .pv-icon {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--ds-radius);
            background: var(--ds-surface-2);
            color: var(--ds-primary);
            font-size: 1.125rem;
        }
        .pv-actions { display: flex; gap: var(--ds-space-2); }
        .pv-actions .btn { border-radius: var(--ds-radius); min-height: var(--ds-control-h-sm); }
        .pv-main {
            flex: 1;
            display: flex;
            padding: var(--ds-space-3);
        }
        .pv-main .ds-card { flex: 1; display: flex; }
        .pv-main iframe {
            display: block;
            width: 100%;
            min-height: calc(100vh - 7rem);
            border: 0;
            background: var(--ds-surface-2);
        }
        @media (max-width: 575.98px) {
            .pv-main { padding: var(--ds-space-2); }
        }
    </style>
</head>
<body>
    <header class="pv-bar">
        <div class="d-flex align-items-center gap-2">
            <span class="pv-icon" aria-hidden="true"><i class="bi bi-file-earmark-pdf"></i></span>
            <div class="ds-page-header">
                <div>
                    <h1 class="ds-page-title">{{ $title }}</h1>
                    <p class="ds-page-subtitle">Preview — check the sheet, then download it.</p>
                </div>
            </div>
        </div>
        <div class="pv-actions">
            <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i><span>Back</span>
            </a>
            <a href="{{ $downloadUrl }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-download"></i><span>Download PDF</span>
            </a>
        </div>
    </header>

    <main class="pv-main">
        <div class="ds-card">
            <iframe src="{{ $streamUrl }}" title="{{ $title }} Preview"></iframe>
        </div>
    </main>
</body>
</html>
