@extends('fc.layouts.master')

@section('title', 'Status of Registrations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/fc-registration-status.css') }}?v={{ @filemtime(public_path('css/fc-registration-status.css')) ?: time() }}">
@endpush

@section('content')
@php
    use App\Services\FC\FcRegistrationStatusService;
    $tabs = [
        FcRegistrationStatusService::TAB_NOT_RESPONDED => ['theme' => 'not-responded', 'label' => 'Not Responded'],
        FcRegistrationStatusService::TAB_REGISTERED => ['theme' => 'registered', 'label' => 'CSE 2024 Registered'],
        FcRegistrationStatusService::TAB_SERVICE => ['theme' => 'service', 'label' => 'Service wise List'],
        FcRegistrationStatusService::TAB_EXEMPTION => ['theme' => 'exemption', 'label' => 'Applied for Exemption'],
        FcRegistrationStatusService::TAB_INCOMPLETE => ['theme' => 'incomplete', 'label' => 'Incomplete'],
    ];
@endphp

<main class="fc-status-page" id="fcStatusPage"
    data-data-url="{{ route('fc.status.data') }}"
    data-initial-tab="{{ $activeTab }}"
    data-initial-page="{{ $participants?->currentPage() ?? 1 }}">
    <div class="fc-status-shell py-2">
            <header class="fc-status-hero text-center">
                <div class="row align-items-center g-3">
                    <div class="col-lg-3 text-lg-start text-center">
                        <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA" height="76" class="img-fluid" style="max-height:76px;">
                    </div>
                    <div class="col-lg-6">
                        <h1 class="course-title">{{ $courseMeta['title'] }}</h1>
                        @if($courseMeta['date_line'])
                            <p class="course-dates">{{ $courseMeta['date_line'] }}</p>
                        @endif
                    </div>
                    <div class="col-lg-3 text-lg-end text-center">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3e/75th_Independence_Day_of_India_Logo.png" alt="Azadi Ka Amrit Mahotsav" height="58" class="img-fluid" style="max-height:58px;" onerror="this.style.display='none'">
                    </div>
                </div>
            </header>

            <section class="fc-status-board" aria-labelledby="fcStatusBoardTitle">
                <h2 id="fcStatusBoardTitle" class="fc-status-board__title mb-0">Status of Registrations</h2>
                <nav class="fc-status-tabs" aria-label="Registration status categories" role="tablist">
                    @foreach($tabs as $tabKey => $tabDef)
                        <a href="#"
                           role="tab"
                           class="fc-status-tab {{ $activeTab === $tabKey ? 'is-active' : '' }}"
                           data-tab="{{ $tabKey }}"
                           data-theme="{{ $tabDef['theme'] }}"
                           aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                           @if($activeTab === $tabKey) aria-current="true" @endif>
                            <span>{{ $tabDef['label'] }}</span>
                            <span class="fc-status-badge fc-status-badge--{{ $tabDef['theme'] }}">{{ number_format($counts[$tabKey] ?? 0) }}</span>
                        </a>
                    @endforeach
                </nav>
            </section>

            <h3 class="fc-status-list-title" id="fcStatusListTitle">{{ $tabMeta['list_title'] }}</h3>

            <div id="fcStatusPanel" class="fc-status-panel-host" aria-live="polite" aria-busy="false">
                @include('fc.status._results', [
                    'activeTab' => $activeTab,
                    'tabMeta' => $tabMeta,
                    'serviceList' => $serviceList,
                    'participants' => $participants,
                ])
            </div>
    </div>

    <footer class="fc-status-footer">
        <div class="fc-status-shell d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Copyright © {{ date('Y') }}. LBSNAA. All Rights Reserved.</span>
                <span>Logged user: {{ (int) $loggedUserCount }}</span>
        </div>
    </footer>
</main>
@endsection

@push('scripts')
<script>
(function () {
    var page = document.getElementById('fcStatusPage');
    var panel = document.getElementById('fcStatusPanel');
    var listTitle = document.getElementById('fcStatusListTitle');
    if (!page || !panel) return;

    var dataUrl = page.getAttribute('data-data-url');
    var currentTab = page.getAttribute('data-initial-tab') || 'not-responded';
    var loading = false;

    function setBusy(busy) {
        panel.setAttribute('aria-busy', busy ? 'true' : 'false');
        panel.classList.toggle('is-loading', busy);
    }

    function setActiveTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.fc-status-tab').forEach(function (el) {
            var on = el.getAttribute('data-tab') === tab;
            el.classList.toggle('is-active', on);
            el.setAttribute('aria-selected', on ? 'true' : 'false');
            if (on) {
                el.setAttribute('aria-current', 'true');
            } else {
                el.removeAttribute('aria-current');
            }
        });
    }

    function updateHistory(tab, pageNum, push) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        if (pageNum > 1) {
            url.searchParams.set('page', String(pageNum));
        } else {
            url.searchParams.delete('page');
        }
        var state = { tab: tab, page: pageNum };
        if (push) {
            history.pushState(state, '', url);
        } else {
            history.replaceState(state, '', url);
        }
    }

    function scrollToPanel(smooth) {
        var target = document.getElementById('fcStatusResults') || panel;
        var top = target.getBoundingClientRect().top + window.pageYOffset - 12;
        window.scrollTo({ top: Math.max(0, top), behavior: smooth ? 'smooth' : 'auto' });
    }

    function bindPagination() {
        panel.querySelectorAll('a.fc-status-page-link[href]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var u = new URL(link.href, window.location.origin);
                var tab = u.searchParams.get('tab') || currentTab;
                var p = parseInt(u.searchParams.get('page') || '1', 10);
                loadFragment(tab, p, true, true);
            });
        });
    }

    function loadFragment(tab, pageNum, pushHistory, scroll) {
        if (loading) return;
        loading = true;
        setBusy(true);

        var url = new URL(dataUrl, window.location.origin);
        url.searchParams.set('tab', tab);
        url.searchParams.set('page', String(pageNum || 1));

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load data');
                return res.json();
            })
            .then(function (data) {
                panel.innerHTML = data.html;
                if (listTitle && data.list_title) {
                    listTitle.textContent = data.list_title;
                }
                setActiveTab(data.tab || tab);
                updateHistory(data.tab || tab, pageNum || 1, pushHistory);
                bindPagination();
                if (scroll) {
                    requestAnimationFrame(function () { scrollToPanel(true); });
                }
            })
            .catch(function () {
                panel.innerHTML = '<div class="alert alert-danger m-3 mb-0">Could not load data. Please refresh the page.</div>';
            })
            .finally(function () {
                loading = false;
                setBusy(false);
            });
    }

    document.querySelectorAll('.fc-status-tab[data-tab]').forEach(function (tabEl) {
        tabEl.addEventListener('click', function (e) {
            e.preventDefault();
            var tab = tabEl.getAttribute('data-tab');
            if (!tab || tab === currentTab) return;
            loadFragment(tab, 1, true, false);
        });
    });

    bindPagination();

    window.addEventListener('popstate', function (e) {
        var tab = (e.state && e.state.tab) || new URL(window.location.href).searchParams.get('tab') || 'not-responded';
        var p = parseInt((e.state && e.state.page) || new URL(window.location.href).searchParams.get('page') || '1', 10);
        loadFragment(tab, p, false, false);
    });

    document.documentElement.classList.add('fc-status-smooth-scroll');

    var initialPage = parseInt(page.getAttribute('data-initial-page') || '1', 10);
    history.replaceState({ tab: currentTab, page: initialPage }, '', window.location.href);
})();
</script>
@endpush
