@extends('admin.layouts.master')

@section('title', 'Dashboard Feed')

@section('content')
@php
    // One list for the tab buttons, the breadcrumb and the script's labels.
    $feedTabs = ['notifications' => 'Notifications', 'notices' => 'Notices', 'birthdays' => 'Birthdays', 'wishes' => 'Wishes'];
    $activeTabLabel = $feedTabs[$activeTab] ?? $feedTabs['notifications'];
    $feedTabCounts = [
        'notifications' => count($feedExpandedNotifications),
        'notices' => $notices->total(),
        'birthdays' => count($emp_dob_data) + count($upcomingBirthdays),
        'wishes' => count($feedExpandedWishes),
    ];
    // Controls that belong to some tabs only carry data-feed-only; the script
    // toggles them on a tab switch, this sets the first paint.
    $feedOnly = fn (string $tabs) => in_array($activeTab, explode(' ', $tabs), true) ? '' : 'd-none';
    $noticeIsArchive = ($noticeFilters['scope'] ?? 'live') === 'archive';
@endphp
<div class="container-fluid dashboard-feed-page">
    {{-- Named after the open tab; the script below renames it on a tab switch. --}}
    <x-breadcrum
        :title="$activeTabLabel"
        :show-back="true"
        :items="[
            ['label' => 'Home', 'url' => route('admin.dashboard')],
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => $activeTabLabel],
        ]" />
    {{-- Tabs and their actions sit ABOVE the card (new design §1). --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            id="dashboard-feed-page-tabs" role="tablist" aria-label="Feed">
            @foreach($feedTabs as $tabKey => $tabLabel)
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $activeTab === $tabKey ? 'active' : '' }}"
                    data-dashboard-feed-tab="{{ $tabKey }}"
                    role="tab"
                    aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                    id="dashboard-feed-tab-{{ $tabKey }}">
                    {{ $tabLabel }}
                    <span class="dashboard-feed-pill-count">{{ number_format($feedTabCounts[$tabKey]) }}</span>
                </button>
            </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn programme-dt-btn-columns border-0 text-primary {{ $feedOnly('notifications wishes') }}"
                id="dashboard-feed-page-mark-all" data-feed-only="notifications wishes">
                <i class="bi bi-check2-all" aria-hidden="true"></i><span>Mark all as read</span>
            </button>
            {{-- The notice screens' own gate (PR #334 F-003), so the button shows
                 for exactly the accounts those routes admit. --}}
            @if(canAuthorNotices())
            <a href="{{ route('admin.notice.create') }}" id="dashboard-feed-add-notice"
                class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-1 {{ $feedOnly('notices') }}"
                data-feed-only="notices">
                <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add New Notice</span>
            </a>
            @endif
        </div>
    </div>

    <div class="card dashboard-feed-panel">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar (§2): count or the notice filters on the left, search on the right. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3 min-w-0">
                    <span class="programme-dt-filters-label dashboard-feed-count {{ $feedOnly('notifications birthdays wishes') }}"
                        id="dashboard-feed-page-count" data-feed-only="notifications birthdays wishes" aria-live="polite">00 Items</span>

                    {{-- Notice filters run in SQL, so this is a plain GET form: changing a
                         select reloads with the filter in the URL, which keeps deep links,
                         back/forward and pagination consistent and never renders more
                         than one page of notices. --}}
                    <form method="GET" action="{{ route('admin.dashboard.feed') }}"
                        class="d-flex flex-wrap align-items-center gap-3 {{ $feedOnly('notices') }}"
                        id="notice-filter-form" data-feed-only="notices">
                        <input type="hidden" name="tab" value="notices">
                        <input type="hidden" name="q" id="notice-filter-q" value="{{ $noticeFilters['q'] }}">

                        <span class="programme-dt-filters-label">Filters</span>

                        {{-- Current / Archive decide which years exist, so changing it
                             clears Year (script below). --}}
                        <div class="programme-dt-filter-select dashboard-feed-filter">
                            <select class="form-select notices-feed-filter select2" name="notice_scope"
                                id="notice-filter-scope" aria-label="Notice scope">
                                <option value="" @selected(!$noticeIsArchive)>Current notices</option>
                                <option value="archive" @selected($noticeIsArchive)>Archive ({{ number_format($noticeFilterOptions['archiveCount'] ?? 0) }})</option>
                            </select>
                        </div>

                        <div class="programme-dt-filter-select dashboard-feed-filter">
                            <select class="form-select notices-feed-filter select2" name="notice_year"
                                id="notice-filter-year" aria-label="Filter by year">
                                <option value="">Year</option>
                                @foreach($noticeFilterOptions['years'] as $ny)
                                <option value="{{ $ny }}" {{ (string) $noticeFilters['year'] === (string) $ny ? 'selected' : '' }}>{{ $ny }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select dashboard-feed-filter">
                            <select class="form-select notices-feed-filter select2" name="notice_type"
                                id="notice-filter-type" aria-label="Filter by type">
                                <option value="">Type</option>
                                @foreach($noticeFilterOptions['types'] as $nt)
                                <option value="{{ $nt }}" {{ $noticeFilters['type'] === $nt ? 'selected' : '' }}>{{ $nt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select dashboard-feed-filter">
                            <select class="form-select notices-feed-filter select2" name="notice_dept"
                                id="notice-filter-dept" aria-label="Filter by department">
                                <option value="">Department</option>
                                @foreach($noticeFilterOptions['depts'] as $nd)
                                <option value="{{ $nd }}" {{ $noticeFilters['dept'] === $nd ? 'selected' : '' }}>{{ $nd }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select dashboard-feed-filter">
                            <select class="form-select notices-feed-filter select2" name="notice_audience"
                                id="notice-filter-audience" aria-label="Filter by target audience">
                                <option value="">Target Audience</option>
                                @foreach($noticeFilterOptions['audiences'] as $na)
                                <option value="{{ $na }}" {{ $noticeFilters['audience'] === $na ? 'selected' : '' }}>{{ $na }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Clears the filters but keeps the scope. --}}
                        <button type="button" class="btn programme-dt-btn-reset" id="notice-filter-reset"
                            data-href="{{ route('admin.dashboard.feed', $noticeIsArchive ? ['tab' => 'notices', 'notice_scope' => 'archive'] : ['tab' => 'notices']) }}">
                            Reset Filters
                        </button>
                    </form>
                </div>

                {{-- Same box as the grids' search (.dataTables_filter markup, no DataTable). --}}
                <div class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label class="mb-0">
                            <input type="search" id="dashboard-feed-page-search" placeholder="Search"
                                autocomplete="off" aria-label="Search feed items">
                        </label>
                    </div>
                </div>
            </div>

            {{-- Notifications --}}
            <div data-feed-panel="notifications" class="{{ $activeTab !== 'notifications' ? 'd-none' : '' }}">
                <div id="dashboard-feed-page-list-notifications">
                    @forelse($feedExpandedNotifications as $feedNotification)
                    @php
                    $feedSenderName = 'System';
                    if ($feedNotification->sender) {
                        $feedSenderName = trim(($feedNotification->sender->first_name ?? '') . ' ' . ($feedNotification->sender->last_name ?? ''));
                        if ($feedSenderName === '') {
                            $feedSenderName = $feedNotification->sender->name ?? 'System';
                        }
                    }
                    $feedMessage = \App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($feedNotification->message ?? '');
                    $feedSearchText = strtolower(($feedNotification->title ?? '') . ' ' . $feedMessage . ' ' . $feedSenderName);
                    @endphp
                    <button type="button"
                        class="dashboard-feed-expanded-card dashboard-feed-expanded-card--clickable dashboard-notification-item {{ empty($feedNotification->is_read) ? 'dashboard-feed-expanded-card--unread' : '' }}"
                        data-notification-id="{{ $feedNotification->pk }}"
                        data-feed-search="{{ $feedSearchText }}">
                        <div class="dashboard-feed-expanded-card__head">
                            <p class="dashboard-feed-expanded-card__title mb-0">{{ $feedNotification->title ?? 'Notification' }}</p>
                            <span class="dashboard-feed-expanded-card__meta">~by <strong>{{ $feedSenderName }}</strong>
                                on {{ $feedNotification->created_at ? \Carbon\Carbon::parse($feedNotification->created_at)->format('d/m/Y h:i A') : '—' }}</span>
                        </div>
                        <p class="dashboard-feed-expanded-card__body mb-0">{{ $feedMessage }}</p>
                    </button>
                    @empty
                    <p class="dashboard-feed-empty mb-0">No notifications available.</p>
                    @endforelse
                </div>
            </div>

            {{-- Notices (with filter bar) --}}
            <div data-feed-panel="notices" class="{{ $activeTab !== 'notices' ? 'd-none' : '' }}"
                data-notice-total="{{ $notices->total() }}">
                @if($noticeFilters['focusMissing'] ?? false)
                <div class="alert alert-warning small py-2 mb-3" role="status">
                    <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>The notice you opened is no longer available. It may have been withdrawn or deleted.
                </div>
                @endif

                @if($noticeIsArchive)
                <p class="text-body-secondary small mb-3">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Notices that have passed their expiry date. Only the ones addressed to you are listed.
                </p>
                @endif

                <div id="dashboard-feed-page-list-notices"
                    @if(!empty($noticeFilters['focus'])) data-focus-notice="{{ (int) $noticeFilters['focus'] }}" @endif>
                    @forelse($notices as $feedNotice)
                    @php
                        // Still used below to pick the badge class/label.
                        $noticeTypeLower     = strtolower((string) ($feedNotice->notice_type ?? ''));
                        $feedNoticeDate      = !empty($feedNotice->display_date) ? \Carbon\Carbon::parse($feedNotice->display_date)->format('d/m/Y h:i A') : '—';
                        $feedNoticeSearch    = strtolower(($feedNotice->notice_title ?? '') . ' ' . ($feedNotice->notice_type ?? '') . ' ' . ($feedNotice->author_name ?? '') . ' ' . ($feedNotice->author_department ?? ''));

                        // The badge shows the notice's OWN type. It used to relabel
                        // "Course notice" as "Work Allocations", so an OT was told
                        // a notice was something the author never selected.
                        // Only the colour is derived from the type now.
                        $noticeBadgeLabel = $feedNotice->notice_type ?: 'Notice';

                        if (str_contains($noticeTypeLower, 'office order')) {
                            $noticeBadgeClass = 'notices-feed-badge--order';
                        } elseif (str_contains($noticeTypeLower, 'course notice') || str_contains($noticeTypeLower, 'work allocation')) {
                            $noticeBadgeClass = 'notices-feed-badge--work';
                        } else {
                            $noticeBadgeClass = 'notices-feed-badge--notice';
                        }
                    @endphp
                    {{-- data-notice-year/-type/-dept/-audience used to drive client-side
                         filtering; that now happens in SQL, and nothing read them. Only
                         data-feed-search (search box) and data-notice-pk (accordion) are live. --}}
                    <div class="notices-feed-item"
                        data-feed-search="{{ $feedNoticeSearch }}"
                        data-notice-pk="{{ $feedNotice->pk }}">
                        <div class="notices-feed-item__header" role="button" tabindex="0"
                            id="notice-head-{{ $feedNotice->pk }}"
                            aria-expanded="false" aria-controls="notice-panel-{{ $feedNotice->pk }}"
                            {{-- no e() here: {{ }} already escapes, and Laravel's e() re-encodes
                                 entities by default, so e() inside {{ }} double-escapes. --}}
                            aria-label="View notice: {{ $feedNotice->notice_title ?? 'Notice' }}">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div class="notices-feed-item__body min-w-0">
                                    <span class="notices-feed-item__title d-block mb-1">{{ $feedNotice->notice_title }}</span>
                                    <small class="notices-feed-item__meta">
                                        ~by <strong>{{ $feedNotice->author_name ?? 'System' }}@if($feedNotice->author_department ?? '') ({{ $feedNotice->author_department }})@endif</strong>
                                        on {{ $feedNoticeDate }}
                                        @if($noticeIsArchive && !empty($feedNotice->expiry_date))
                                        <span class="text-body-secondary">· expired {{ \Carbon\Carbon::parse($feedNotice->expiry_date)->format('d/m/Y') }}</span>
                                        @endif
                                    </small>
                                </div>
                                <span class="notices-feed-badge {{ $noticeBadgeClass }} flex-shrink-0">{{ $noticeBadgeLabel }}</span>
                            </div>
                        </div>
                        <div class="notices-feed-item__panel" id="notice-panel-{{ $feedNotice->pk }}"
                            role="region" aria-labelledby="notice-head-{{ $feedNotice->pk }}">
                            <div class="notices-feed-item__panel-inner">
                                @if(trim(strip_tags((string) ($feedNotice->description ?? ''))) !== '')
                                {{-- Sanitised at render as well as on save: older rows were stored verbatim (PR #334 F-003). --}}
                                <div class="notice-description-content">{!! notice_safe_html($feedNotice->description) !!}</div>
                                @else
                                <p class="text-muted fst-italic mb-0">No description provided.</p>
                                @endif
                                @if($feedNotice->document)
                                <div class="mt-3">
                                    {{-- `notices-feed-item__attachment` carries no CSS — it is the hook the
                                         accordion click handler uses to let this link through instead of
                                         collapsing the panel. Do not remove it as an "unused" class. --}}
                                    <a href="{{ route('admin.notice.attachment', $feedNotice->pk) }}" target="_blank"
                                        rel="noopener noreferrer"
                                        class="notices-feed-item__attachment small text-danger text-decoration-none d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-paperclip" aria-hidden="true"></i> View Attachment
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="dashboard-feed-empty mb-0">
                        @if($noticeFilters['q'] !== '' || $noticeFilters['type'] !== '' || $noticeFilters['dept'] !== '' || $noticeFilters['audience'] !== '' || $noticeFilters['year'] !== '')
                        No notices match these filters.
                        @elseif($noticeIsArchive)
                        No archived notices yet.
                        @else
                        No notices available.
                        @endif
                    </p>
                    @endforelse
                </div>

                @if($notices->total() > 0)
                {{-- Footer variant B (Laravel paginator): pager left, count right. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $notices->onEachSide(1)->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_info">Showing {{ $notices->firstItem() }}–{{ $notices->lastItem() }} of {{ number_format($notices->total()) }} items</div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Birthdays: today + upcoming --}}
            <div data-feed-panel="birthdays" class="{{ $activeTab !== 'birthdays' ? 'd-none' : '' }}">
                <section class="dashboard-birthdays-block mb-4 pb-1">
                    <h2 class="dashboard-birthdays-block__title h6 fw-semibold text-body mb-3">
                        <i class="bi bi-cake2-fill text-danger opacity-75 me-1" aria-hidden="true"></i>Today's Birthdays
                    </h2>
                    <div class="d-flex flex-column gap-2" id="dashboard-feed-birthdays-today">
                        @forelse($emp_dob_data as $employee)
                        @include('admin.dashboard.partials.birthday-item', [
                            'employee' => $employee,
                            'loopIndex' => $loop->index,
                            'birthdayWishCounts' => $birthdayWishCounts,
                            'showWishButton' => true,
                            'variant' => 'today',
                        ])
                        @empty
                        <p class="dashboard-feed-empty mb-0 py-3">No birthdays today.</p>
                        @endforelse
                    </div>
                </section>

                <section class="dashboard-birthdays-block">
                    <h2 class="dashboard-birthdays-block__title h6 fw-semibold text-body mb-3">
                        <i class="bi bi-calendar-event text-primary opacity-75 me-1" aria-hidden="true"></i>Upcoming Birthdays
                    </h2>
                    <div class="d-flex flex-column gap-2" id="dashboard-feed-birthdays-upcoming">
                        @forelse($upcomingBirthdays as $employee)
                        @include('admin.dashboard.partials.birthday-item', [
                            'employee' => $employee,
                            'loopIndex' => $loop->index,
                            'birthdayWishCounts' => $birthdayWishCounts,
                            'showWishButton' => true,
                            'variant' => 'upcoming',
                        ])
                        @empty
                        <p class="dashboard-feed-empty mb-0 py-3">No upcoming birthdays in the next 7 days.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- Wishes --}}
            <div data-feed-panel="wishes" class="{{ $activeTab !== 'wishes' ? 'd-none' : '' }}">
                <div id="dashboard-feed-page-list-wishes">
                    @forelse($feedExpandedWishes as $feedWish)
                    @include('admin.dashboard.partials.wish-received-item', ['wish' => $feedWish, 'layout' => 'feed'])
                    @empty
                    <p class="dashboard-feed-empty mb-0">No wishes available.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.dashboard.partials.wish-modal')
@endsection

@push('styles')
{{-- The notice filters are Select2 (every dropdown is searchable, new design §2). --}}
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-feed.css') }}?v={{ @filemtime(public_path('admin_assets/css/dashboard-feed.css')) ?: time() }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLabels = @json($feedTabs);
    const crumbTitle = document.querySelector('.dashboard-feed-page .modern-breadcrumb-title');
    const crumbLast = document.querySelector('.dashboard-feed-page .modern-breadcrumb-trail .breadcrumb-item.active span');
    const feedBaseUrl = @json(route('admin.dashboard.feed'));
    const allowedTabs = Object.keys(tabLabels);

    function tabFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        return allowedTabs.indexOf(tab) !== -1 ? tab : @json($activeTab);
    }

    let activeTab = tabFromUrl();
    // The notices search term the server actually applied (drives the search box).
    const noticeServerQuery = @json($noticeFilters['q'] ?? '');
    const searchInput = document.getElementById('dashboard-feed-page-search');
    const countEl = document.getElementById('dashboard-feed-page-count');
    const markAllBtn = document.getElementById('dashboard-feed-page-mark-all');
    const tabOnlyEls = document.querySelectorAll('[data-feed-only]');
    const tabButtons = document.querySelectorAll('[data-dashboard-feed-tab]');
    const tabPanels = document.querySelectorAll('[data-feed-panel]');

    function getActivePanel() {
        return document.querySelector('[data-feed-panel="' + activeTab + '"]');
    }

    function updateCount() {
        const panel = getActivePanel();
        if (!panel || !countEl) return;
        const label = tabLabels[activeTab] || 'Items';

        // Notices are paginated server-side, so the visible-row count would only
        // ever report the current page. Use the server's total instead.
        if (activeTab === 'notices') {
            const total = parseInt(panel.dataset.noticeTotal || '0', 10) || 0;
            countEl.textContent = String(total).padStart(2, '0') + ' ' + label;
            return;
        }

        const items = panel.querySelectorAll('[data-feed-search]');
        let visible = 0;
        items.forEach(function(item) {
            if (!item.classList.contains('d-none')) visible++;
        });
        countEl.textContent = String(visible).padStart(2, '0') + ' ' + label;
    }

    function applySearch() {
        const panel = getActivePanel();
        if (!panel) return;

        // The notices tab searches in SQL (submitNoticeSearch below). Filtering the
        // rendered page here as well would silently narrow one page of results and
        // contradict the count, so it is skipped.
        if (activeTab === 'notices') {
            updateCount();
            return;
        }

        const query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
        panel.querySelectorAll('[data-feed-search]').forEach(function(item) {
            const haystack = (item.getAttribute('data-feed-search') || '').toLowerCase();
            item.classList.toggle('d-none', query !== '' && haystack.indexOf(query) === -1);
        });
        updateCount();
    }

    // Notices search runs in SQL. The typed text is mirrored into the filter
    // form's hidden `q` on every keystroke, so whichever control the user reaches
    // for next — Enter, a filter select, Reset — carries the search with it.
    function syncNoticeSearchField() {
        const qField = document.getElementById('notice-filter-q');
        if (qField && searchInput) qField.value = searchInput.value.trim();
    }

    function submitNoticeSearch() {
        const form = document.getElementById('notice-filter-form');
        if (!form || !searchInput) return;
        // Compare against what the server actually applied, not the hidden field:
        // that one tracks the box live, so it would never look different.
        if (searchInput.value.trim() === noticeServerQuery) return;
        syncNoticeSearchField();
        form.submit();
    }

    function updateFeedUrl(tab) {
        // Carry the current query string over: switching tabs and coming back
        // used to rewrite the URL to a bare ?tab=notices, so a refresh silently
        // dropped the scope and filters the page was still displaying.
        const url = new URL(feedBaseUrl, window.location.origin);
        new URLSearchParams(window.location.search).forEach(function(value, key) {
            if (key !== 'tab' && key !== 'page') url.searchParams.set(key, value);
        });
        url.searchParams.set('tab', tab);
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ feedTab: tab }, '', url.pathname + url.search);
        }
    }

    function setActiveTab(tab, updateUrl) {
        if (allowedTabs.indexOf(tab) === -1) {
            tab = 'notifications';
        }
        activeTab = tab;

        tabButtons.forEach(function(btn) {
            const isActive = btn.dataset.dashboardFeedTab === activeTab;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        tabPanels.forEach(function(panel) {
            const isActive = panel.dataset.feedPanel === activeTab;
            panel.classList.toggle('d-none', !isActive);
        });

        // Tabs switch without a reload, so the breadcrumb follows here.
        if (crumbTitle) crumbTitle.textContent = tabLabels[activeTab];
        if (crumbLast) crumbLast.textContent = tabLabels[activeTab];

        tabOnlyEls.forEach(function(el) {
            el.classList.toggle('d-none', el.dataset.feedOnly.split(' ').indexOf(activeTab) === -1);
        });

        if (searchInput) {
            // On the notices tab the box mirrors the query the server actually
            // applied, so it stays in step with the rendered (paginated) list.
            searchInput.value = (activeTab === 'notices') ? noticeServerQuery : '';
        }

        if (updateUrl !== false) {
            updateFeedUrl(activeTab);
        }

        applySearch();
    }

    tabButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            setActiveTab(btn.dataset.dashboardFeedTab || 'notifications');
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (activeTab === 'notices') syncNoticeSearchField();
            applySearch();
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter' || activeTab !== 'notices') return;
            e.preventDefault();
            submitNoticeSearch();
        });

        searchInput.addEventListener('blur', function(e) {
            if (activeTab !== 'notices') return;
            // Reset Filters and the selects live INSIDE the filter form, and Add
            // New Notice sits above the card. Submitting here when focus moves to
            // one of them would navigate first and swallow that click — a select
            // would not even open. Their own action already carries the synced `q`.
            const form = document.getElementById('notice-filter-form');
            const to = e.relatedTarget;
            if (to && ((form && form.contains(to)) || to.id === 'dashboard-feed-add-notice')) return;
            submitNoticeSearch();
        });
    }

    setActiveTab(activeTab, false);

    window.addEventListener('popstate', function() {
        setActiveTab(tabFromUrl(), false);
    });

    // Each select is a form field — changing one reloads with the filter applied.
    // They are Select2, which fires a jQuery change that addEventListener never hears.
    if (window.jQuery) {
        window.jQuery(document).on('change', '.notices-feed-filter', function() {
            const form = document.getElementById('notice-filter-form');
            if (!form) return;
            // Current and Archive offer different years.
            if (this.id === 'notice-filter-scope') {
                const year = document.getElementById('notice-filter-year');
                if (year) year.value = '';
            }
            syncNoticeSearchField();
            form.submit();
        });
    }

    const noticeReset = document.getElementById('notice-filter-reset');
    if (noticeReset) {
        noticeReset.addEventListener('click', function() {
            window.location.assign(noticeReset.dataset.href);
        });
    }

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch('{{ route('admin.notifications.mark-all-read') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data && data.success) window.location.reload();
            });
        });
    }
});

if (typeof window.markAsReadDashboard !== 'function') {
    window.markAsReadDashboard = function(notificationId, clickedElement) {
        if (clickedElement && clickedElement.dataset.processing === 'true') return;
        if (clickedElement) clickedElement.dataset.processing = 'true';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        fetch('/admin/notifications/mark-read-redirect/' + notificationId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
            if (res.data.success && res.data.redirect_url) {
                window.location.href = res.data.redirect_url;
            } else if (res.data.success) {
                location.reload();
            }
        }).catch(function() {
            if (clickedElement) clickedElement.dataset.processing = 'false';
        });
    };
}

document.addEventListener('click', function(e) {
    // Let reply button and wish button handle their own clicks
    if (e.target && e.target.closest && e.target.closest('.btn-wish-reply')) return;
    if (e.target && e.target.closest && e.target.closest('.btn-custom-wish')) return;

    // Birthday item card click → open wish modal via the "Wish them" button
    const birthdayItem = e.target && e.target.closest ? e.target.closest('.dashboard-birthday-item') : null;
    if (birthdayItem) {
        const wishBtn = birthdayItem.querySelector('.btn-custom-wish');
        if (wishBtn) { wishBtn.click(); }
        return;
    }

    const notifItem = e.target && e.target.closest ? e.target.closest('.dashboard-notification-item[data-notification-id]') : null;
    if (!notifItem) return;

    // Wish card click → open reply modal via the "Reply" button
    if (notifItem.classList.contains('dashboard-feed-wish-card')) {
        const replyBtn = notifItem.querySelector('.btn-wish-reply');
        if (replyBtn) { replyBtn.click(); return; }
    }

    window.markAsReadDashboard(notifItem.dataset.notificationId, notifItem);
});

// Notices accordion: hover expands, click pins open/closed. One notice open at a time.
(function () {
    const list = document.getElementById('dashboard-feed-page-list-notices');
    if (!list) return;

    const OPEN_DELAY  = 120;   // ignore pointers just passing over the list
    const CLOSE_DELAY = 180;   // let the pointer travel into the panel without it snapping shut
    let hoverTimer = null;

    function setOpen(item, open) {
        const panel = item.querySelector('.notices-feed-item__panel');
        const head  = item.querySelector('.notices-feed-item__header');
        if (!panel) return;
        // Collapsing an already-collapsed panel would re-run the animation and flicker
        // (happens when the pointer leaves before the open delay elapsed).
        if (!open && !item.classList.contains('is-open')) {
            item.classList.remove('is-pinned');
            return;
        }

        if (open) {
            item.classList.add('is-open');
            panel.style.maxHeight = panel.scrollHeight + 'px';
        } else {
            item.classList.remove('is-open', 'is-pinned');
            // maxHeight may be 'none' (see transitionend below); pin it to a number so it can animate down
            panel.style.maxHeight = panel.scrollHeight + 'px';
            void panel.offsetHeight;
            panel.style.maxHeight = '';
        }
        if (head) head.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function closeOthers(except) {
        list.querySelectorAll('.notices-feed-item.is-open').forEach(function (other) {
            if (other !== except) setOpen(other, false);
        });
    }

    // Once open, drop the fixed height so late-loading images/tables aren't clipped.
    list.addEventListener('transitionend', function (e) {
        if (e.propertyName !== 'max-height') return;
        const panel = e.target;
        if (!panel.classList || !panel.classList.contains('notices-feed-item__panel')) return;
        const item = panel.closest('.notices-feed-item');
        if (item && item.classList.contains('is-open')) panel.style.maxHeight = 'none';
    });

    // A pinned notice owns the list: hover neither closes it nor opens another,
    // so "pinned" survives the pointer moving elsewhere. Only a click changes it.
    function hasPinned() {
        return !!list.querySelector('.notices-feed-item.is-pinned');
    }

    list.querySelectorAll('.notices-feed-item').forEach(function (item) {
        item.addEventListener('mouseenter', function () {
            clearTimeout(hoverTimer);
            if (hasPinned()) return;
            hoverTimer = setTimeout(function () {
                closeOthers(item);
                setOpen(item, true);
            }, OPEN_DELAY);
        });

        item.addEventListener('mouseleave', function () {
            clearTimeout(hoverTimer);
            if (item.classList.contains('is-pinned') || hasPinned()) return;
            hoverTimer = setTimeout(function () { setOpen(item, false); }, CLOSE_DELAY);
        });
    });

    list.addEventListener('click', function (e) {
        if (e.target.closest('.notices-feed-item__attachment')) return; // let the attachment link open
        const head = e.target.closest('.notices-feed-item__header');
        if (!head) return;
        const item = head.closest('.notices-feed-item');
        if (!item) return;

        clearTimeout(hoverTimer);
        if (item.classList.contains('is-pinned')) {
            setOpen(item, false);
        } else {
            closeOthers(item);
            setOpen(item, true);
            item.classList.add('is-pinned');
        }
    });

    list.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const head = e.target.closest ? e.target.closest('.notices-feed-item__header') : null;
        if (!head) return;
        e.preventDefault();
        head.click();
    });

    // Arrived from a bell notification (?notice=<pk>): the server has already
    // picked the page holding it, so pin its card open and bring it into view.
    const focusPk = list.dataset.focusNotice;
    if (focusPk) {
        const focusItem = list.querySelector('.notices-feed-item[data-notice-pk="' + focusPk + '"]');
        if (focusItem) {
            closeOthers(focusItem);
            setOpen(focusItem, true);
            focusItem.classList.add('is-pinned');
            focusItem.scrollIntoView({ block: 'center' });
            const focusHead = focusItem.querySelector('.notices-feed-item__header');
            if (focusHead) focusHead.focus({ preventScroll: true });
        }
    }
}());
document.addEventListener('click', function(e) {
    const toggleBtn = e.target && e.target.closest ? e.target.closest('.notice-desc-toggle') : null;
    if (!toggleBtn) return;
    e.preventDefault();
    e.stopPropagation();
    const container = toggleBtn.closest('.notice-description-content');
    if (!container) return;
    const preview = container.querySelector('.notice-desc-preview');
    const full = container.querySelector('.notice-desc-full');
    if (!preview || !full) return;
    const isCollapsed = full.classList.contains('d-none');
    if (isCollapsed) {
        preview.classList.add('d-none');
        full.classList.remove('d-none');
    } else {
        full.classList.add('d-none');
        preview.classList.remove('d-none');
    }
});
</script>
@endpush
