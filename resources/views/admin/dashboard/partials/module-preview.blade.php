{{-- Dashboard Preview: on a module's own dashboard (any header tab but Home), the page a card
     opens, embedded at the top of the panels' left column, level with the right-hand panels
     (House wise Performance, ...). It is that page itself (?embed=1 hides the layout chrome and
     breadcrumb, see admin.layouts.master), so the rows, filters, permission checks and record
     scope are the page's own, not a copy. It starts on the first card that opens a page; with
     two or more such cards a tab row switches between them (the choice is remembered per module
     in this browser). With no such card the first card's tile shows. No stat card, nothing. --}}
@php
    $isModuleDashboard = ($dashboardModule ?? null) && $dashboardModule->slug !== 'home';
    $embedUrl = fn ($link) => $link.(str_contains($link, '?') ? '&' : '?').'embed=1';
    $previewTabs = $isModuleDashboard ? $cardsToRender->filter(fn ($c) => $c['link'])->values() : collect();
    $previewCard = $isModuleDashboard ? ($previewTabs->first() ?? $cardsToRender->first()) : null;
    $previewUrl = $previewCard && $previewCard['link'] ? $embedUrl($previewCard['link']) : null;
@endphp
@if($previewCard)
<section class="module-dashboard-preview mb-3 d-print-none" aria-labelledby="moduleDashboardPreviewTitle"
    data-module="{{ $dashboardModule->slug }}">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h2 id="moduleDashboardPreviewTitle" class="h5 mb-0">Dashboard Preview: <span class="module-dashboard-preview-label">{{ $previewCard['label'] }}</span></h2>
        @if($previewUrl)
        <a href="{{ $previewCard['link'] }}" class="btn btn-sm btn-outline-primary module-dashboard-preview-open">Open full page</a>
        @endif
    </div>
    @if($previewUrl && $previewTabs->count() > 1)
    <div class="module-dashboard-preview-tabs" role="group" aria-label="Choose the page to preview">
        @foreach($previewTabs as $tab)
        @php $isCurrent = $tab['key'] === $previewCard['key']; @endphp
        <button type="button" class="module-dashboard-preview-tab" aria-pressed="{{ $isCurrent ? 'true' : 'false' }}"
            data-key="{{ $tab['key'] }}" data-src="{{ $embedUrl($tab['link']) }}" data-href="{{ $tab['link'] }}" data-label="{{ $tab['label'] }}">
            <span class="module-dashboard-preview-tab__label">{{ $tab['label'] }}</span>
            @if(($tab['show_count'] ?? true) && is_numeric($tab['count']))
            <span @class(['module-dashboard-preview-tab__count', 'is-action' => ($tab['actionable'] ?? false) && $tab['count'] > 0])>{{ $tab['count'] + 0 }}</span>
            @endif
        </button>
        @endforeach
    </div>
    @endif
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
        var wrap = section.querySelector('.module-dashboard-preview-frame-wrap');
        var storeKey = 'sargamDashPreview:' + section.getAttribute('data-module');
        var observer = null;

        frame.addEventListener('load', function () {
            var loading = section.querySelector('.module-dashboard-preview-loading');
            if (loading) { loading.remove(); }
            if (observer) { observer.disconnect(); observer = null; }
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
            if (window.ResizeObserver) { observer = new ResizeObserver(fit); observer.observe(main); }
        });

        var tabs = section.querySelectorAll('.module-dashboard-preview-tab');
        if (!tabs.length) { return; }

        var show = function (tab) {
            if (tab.getAttribute('aria-pressed') === 'true') { return; }
            tabs.forEach(function (t) { t.setAttribute('aria-pressed', t === tab ? 'true' : 'false'); });
            section.querySelector('.module-dashboard-preview-label').textContent = tab.getAttribute('data-label');
            section.querySelector('.module-dashboard-preview-open').setAttribute('href', tab.getAttribute('data-href'));
            frame.setAttribute('title', tab.getAttribute('data-label'));
            if (!section.querySelector('.module-dashboard-preview-loading')) {
                var loading = document.createElement('p');
                loading.className = 'module-dashboard-preview-loading position-absolute top-0 start-0 w-100 text-muted small mb-0 py-4 text-center';
                loading.setAttribute('role', 'status');
                loading.textContent = 'Loading…';
                wrap.insertBefore(loading, frame);
            }
            frame.setAttribute('src', tab.getAttribute('data-src'));
        };

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                show(tab);
                try { localStorage.setItem(storeKey, tab.getAttribute('data-key')); } catch (e) { /* private mode */ }
            });
        });

        // Reopen on the page this browser last chose for this module, if the login still has it.
        var saved = null;
        try { saved = localStorage.getItem(storeKey); } catch (e) { /* private mode */ }
        tabs.forEach(function (tab) { if (saved && tab.getAttribute('data-key') === saved) { show(tab); } });
    })();
</script>
@endif
