{{-- Dashboard Preview: on a module's own dashboard (any header tab but Home), the page its
     first card opens, embedded at the top of the panels' left column, level with the
     right-hand panels (House wise Performance, ...). It is that page itself (?embed=1 hides the layout chrome and breadcrumb, see admin.layouts.master), so the
     rows, filters, permission checks and record scope are the page's own, not a copy. A first
     card with no link falls back to its tile. A dashboard with no stat card shows nothing. --}}
@php
    $previewCard = ($dashboardModule ?? null) && $dashboardModule->slug !== 'home'
        ? $cardsToRender->first()
        : null;
    $previewUrl = $previewCard && $previewCard['link']
        ? $previewCard['link'].(str_contains($previewCard['link'], '?') ? '&' : '?').'embed=1'
        : null;
@endphp
@if($previewCard)
<section class="module-dashboard-preview mb-3 d-print-none" aria-labelledby="moduleDashboardPreviewTitle">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h2 id="moduleDashboardPreviewTitle" class="h5 mb-0">Dashboard Preview: {{ $previewCard['label'] }}</h2>
        @if($previewUrl)
        <a href="{{ $previewCard['link'] }}" class="btn btn-sm btn-outline-primary">Open full page</a>
        @endif
    </div>
    @if($previewUrl)
    <div class="module-dashboard-preview-frame-wrap position-relative">
        <p class="module-dashboard-preview-loading position-absolute top-0 start-0 w-100 text-muted small mb-0 py-4 text-center" role="status">Loading…</p>
        {{-- A starting height: a lazy frame of height 0 is never in view, so never loads. --}}
        <iframe src="{{ $previewUrl }}" title="{{ $previewCard['label'] }}" class="module-dashboard-preview-frame w-100 border-0 d-block" style="height: 320px;" loading="lazy"></iframe>
    </div>
    @else
    <div class="dashboard-stats-grid row g-3">
        <div class="col">
            @include('admin.dashboard.partials.stat-card', ['card' => $previewCard])
        </div>
    </div>
    @endif
</section>
<script>
    (function () {
        'use strict';
        var section = document.querySelector('.module-dashboard-preview');
        var frame = section && section.querySelector('.module-dashboard-preview-frame');
        if (!frame) { return; }
        frame.addEventListener('load', function () {
            var loading = section.querySelector('.module-dashboard-preview-loading');
            if (loading) { loading.remove(); }
            var doc;
            try { doc = frame.contentDocument; } catch (e) { return; }
            var main = doc && doc.querySelector('main');
            if (!main) { return; }
            // Fit the frame to the page content, and keep fitting as its table loads or
            // pages. Measured on <main>: the embedded html/body take the frame's height,
            // so their scrollHeight never grows past it.
            var fit = function () {
                frame.style.height = Math.ceil(main.getBoundingClientRect().bottom + doc.defaultView.scrollY + 8) + 'px';
            };
            fit();
            if (window.ResizeObserver) { new ResizeObserver(fit).observe(main); }
        });
    })();
</script>
@endif
