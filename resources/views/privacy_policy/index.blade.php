@extends(auth()->check() ? 'admin.layouts.master' : 'privacy_policy.guest')
@section('title', 'Privacy Policy')

@push('styles')
<style>
    .pp-updated { background: rgba(var(--bs-primary-rgb), .1); color: var(--ds-primary); border-radius: var(--ds-radius-1); padding: .625rem .75rem; font-size: .75rem; font-weight: 500; white-space: nowrap; }
    .pp-card { padding: var(--ds-space-4); }
    .pp-layout { border-top: 1px solid var(--ds-line); padding-top: var(--ds-space-3); }
    .pp-index-title { font-size: .875rem; font-weight: 500; color: var(--ds-ink); margin-bottom: .75rem; }
    .pp-index ol { list-style: none; padding: 0; margin: 0; display: grid; gap: .5625rem; }
    .pp-index a { display: flex; gap: var(--ds-space-2); font-size: .75rem; line-height: 1.4; color: var(--ds-ink-muted); text-decoration: none; }
    .pp-index a span:first-child { width: 1.375rem; flex-shrink: 0; font-weight: 500; color: var(--ds-primary); }
    .pp-index a:hover, .pp-index a:focus-visible { color: var(--ds-ink); }
    .pp-scroll { position: relative; }
    .pp-section { padding-bottom: .75rem; margin-bottom: var(--ds-space-4); border-bottom: 1px solid var(--ds-line); scroll-margin-top: var(--ds-space-3); }
    .pp-section > :last-child { margin-bottom: 0; }
    .pp-section h3 { font-size: 1rem; font-weight: 500; color: var(--ds-ink); margin-bottom: .75rem; }
    .pp-section h4 { font-size: .875rem; font-weight: 500; color: var(--ds-ink); margin: .75rem 0 .5rem; }
    .pp-section p, .pp-section li { font-size: .875rem; line-height: 1.6; color: var(--ds-ink); }
    .pp-section p { margin-bottom: .75rem; }
    .pp-section ul { list-style: disc; padding-left: 1.25rem; margin-bottom: .75rem; }
    .pp-section li + li { margin-top: .25rem; }
    .pp-contact { background: rgba(var(--bs-primary-rgb), .1); color: var(--ds-primary); border-radius: var(--ds-radius-1); padding: var(--ds-space-3); font-size: .875rem; line-height: 1.7; font-style: normal; margin-bottom: .75rem; }
    .pp-contact a { color: inherit; }
    .pp-signoff { font-size: .8125rem; line-height: 1.7; color: var(--ds-ink-muted); margin: 0; }
    .pp-footer { border-top: 1px solid var(--ds-line); padding-top: var(--ds-space-3); margin-top: var(--ds-space-3); font-size: .75rem; color: var(--ds-ink-muted); }

    /* Desktop: index beside the text, and the text scrolls inside the card (as designed). */
    @media (min-width: 992px) {
        .pp-layout { display: grid; grid-template-columns: 17rem 1fr; gap: var(--ds-space-4); }
        .pp-index { border-right: 1px solid var(--ds-line); padding-right: var(--ds-space-3); }
        .pp-scroll { height: max(24rem, calc(100vh - 27rem)); overflow-y: auto; padding: 0 var(--ds-space-4) 0 var(--ds-space-2); }
    }
    @media (max-width: 991.98px) {
        .pp-index { margin-bottom: var(--ds-space-4); }
        .pp-scroll-hint { display: none; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid pp-page">
    <x-breadcrum title="Privacy Policy – SARGAM 2.0" :items="[
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Privacy Policy'],
    ]">
        <span class="pp-updated">Last Updated: {{ $lastUpdated }}</span>
    </x-breadcrum>

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="{{ route('privacy-policy.download') }}" class="btn programme-dt-btn-columns border-0 text-primary bg-white">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </a>
        <a href="{{ route('privacy-policy.print') }}" target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary bg-white">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <article class="ds-card pp-card" aria-labelledby="ppTitle">
        <h2 id="ppTitle" class="visually-hidden">Privacy Policy – SARGAM 2.0</h2>
        <div class="pp-layout">
            <nav class="pp-index" aria-label="Policy sections">
                <div class="pp-index-title">Policy sections</div>
                <ol>
                    @foreach ($sections as $i => $title)
                        <li><a href="#pp-{{ $i + 1 }}"><span>{{ sprintf('%02d', $i + 1) }}</span><span>{{ $title }}</span></a></li>
                    @endforeach
                </ol>
            </nav>
            <div class="pp-scroll" id="ppScroll" tabindex="0" aria-label="Privacy Policy text">
                @include('privacy_policy._content')
            </div>
        </div>
        <div class="pp-footer d-flex flex-wrap justify-content-between gap-2">
            <span>SARGAM 2.0 &nbsp;/&nbsp; Privacy Policy</span>
            <span class="pp-scroll-hint">Scroll within the policy to read all {{ count($sections) }} sections</span>
        </div>
    </article>
</div>

<script>
    // Desktop: jump inside the scrolling text instead of scrolling the whole page. Stacked (mobile)
    // layout has no inner scroll, so the browser's normal anchor jump is left alone.
    document.querySelectorAll('.pp-index a').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var box = document.getElementById('ppScroll');
            var target = document.querySelector(link.hash);
            if (!target || box.scrollHeight <= box.clientHeight) return;
            e.preventDefault();
            box.scrollTo({ top: target.offsetTop, behavior: 'smooth' });
            history.replaceState(null, '', link.hash);
        });
    });
</script>
@endsection
