@extends('admin.layouts.master')

@section('title', 'Dashboard Feed')

@section('content')
<div class="container-fluid dashboard-feed-page py-3 py-md-4">
    <x-breadcrum
        title="Notifications"
        :show-back="true"
        :items="[
            ['label' => 'Home', 'url' => route('admin.dashboard')],
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Notifications'],
        ]" />
    <div class="card dashboard-feed-panel">
        <div class="card-body p-3 p-md-4">
            <div
                class="dashboard-feed-expanded__toolbar d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center mb-3">
                <ul class="nav dashboard-feed-expanded-tabs" id="dashboard-feed-page-tabs" role="tablist">
                    @foreach(['notifications' => 'Notifications', 'notices' => 'Notices', 'birthdays' => 'Birthdays', 'wishes' => 'Wishes'] as $tabKey => $tabLabel)
                    <li class="nav-item" role="presentation">
                        <button type="button"
                            class="nav-link rounded-1 {{ $activeTab === $tabKey ? 'active' : '' }}"
                            data-dashboard-feed-tab="{{ $tabKey }}"
                            role="tab"
                            aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                            id="dashboard-feed-tab-{{ $tabKey }}">
                            {{ $tabLabel }}
                        </button>
                    </li>
                    @endforeach
                </ul>
                <div class="input-group dashboard-feed-expanded-search">
                    <span class="input-group-text border-end-0 rounded-start-1 ps-3">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">search</i>
                    </span>
                    <input type="search" class="form-control border-start-0 rounded-end-1"
                        id="dashboard-feed-page-search" placeholder="Search" autocomplete="off"
                        aria-label="Search feed items">
                </div>
            </div>

            <div class="dashboard-feed-expanded-meta d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h1 class="dashboard-feed-expanded-count h5 mb-0" id="dashboard-feed-page-count">00 Items</h1>
                <button type="button"
                    class="dashboard-feed-mark-all-read {{ in_array($activeTab, ['notifications', 'wishes'], true) ? '' : 'd-none' }}"
                    id="dashboard-feed-page-mark-all">
                    Mark all as read
                </button>
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

                <h2 class="notices-feed-section-title">Notices</h2>

                {{-- Filters are applied in SQL, so the toolbar is a plain GET form:
                     changing a select reloads the page with the filter in the URL.
                     That keeps deep links, browser back/forward and pagination
                     consistent, and means the page never renders more than one
                     page of notices. --}}
                <form method="GET" action="{{ route('admin.dashboard.feed') }}"
                    class="notices-feed-toolbar d-flex flex-wrap align-items-center gap-2 mb-3"
                    id="notice-filter-form">
                    <input type="hidden" name="tab" value="notices">
                    <input type="hidden" name="q" id="notice-filter-q" value="{{ $noticeFilters['q'] }}">

                    <span class="notices-feed-toolbar__label">Filters</span>

                    <select class="form-select form-select-sm notices-feed-filter" name="notice_year"
                        id="notice-filter-year" aria-label="Filter by year">
                        <option value="">Year</option>
                        @foreach($noticeFilterOptions['years'] as $yr)
                        <option value="{{ $yr }}" {{ (string) $noticeFilters['year'] === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm notices-feed-filter" name="notice_type"
                        id="notice-filter-type" aria-label="Filter by type">
                        <option value="">Type</option>
                        @foreach($noticeFilterOptions['types'] as $nt)
                        <option value="{{ $nt }}" {{ $noticeFilters['type'] === $nt ? 'selected' : '' }}>{{ $nt }}</option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm notices-feed-filter" name="notice_dept"
                        id="notice-filter-dept" aria-label="Filter by department">
                        <option value="">Department</option>
                        @foreach($noticeFilterOptions['depts'] as $nd)
                        <option value="{{ $nd }}" {{ $noticeFilters['dept'] === $nd ? 'selected' : '' }}>{{ $nd }}</option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm notices-feed-filter" name="notice_audience"
                        id="notice-filter-audience" aria-label="Filter by target audience">
                        <option value="">Target Audience</option>
                        @foreach($noticeFilterOptions['audiences'] as $na)
                        <option value="{{ $na }}" {{ $noticeFilters['audience'] === $na ? 'selected' : '' }}>{{ $na }}</option>
                        @endforeach
                    </select>

                    <a href="{{ route('admin.dashboard.feed', ['tab' => 'notices']) }}"
                        class="btn btn-sm notices-feed-reset-btn" id="notice-filter-reset">Reset Filters</a>

                    @if(hasRole('Admin'))
                    <a href="{{ route('admin.notice.create') }}" class="btn btn-sm notices-feed-add-btn ms-auto">
                        <i class="bi bi-plus-square me-1" aria-hidden="true"></i>Add New Notice
                    </a>
                    @endif
                </form>

                <div id="dashboard-feed-page-list-notices">
                    @forelse($notices as $feedNotice)
                    @php
                        $noticeTypeLower     = strtolower((string) ($feedNotice->notice_type ?? ''));
                        $noticeDeptLower     = strtolower((string) ($feedNotice->author_department ?? ''));
                        $noticeAudienceLower = strtolower((string) ($feedNotice->target_audience ?? ''));
                        $noticeYear          = !empty($feedNotice->display_date) ? \Carbon\Carbon::parse($feedNotice->display_date)->year : '';
                        $feedNoticeDate      = !empty($feedNotice->display_date) ? \Carbon\Carbon::parse($feedNotice->display_date)->format('d/m/Y h:i A') : '—';
                        $feedNoticeSearch    = strtolower(($feedNotice->notice_title ?? '') . ' ' . ($feedNotice->notice_type ?? '') . ' ' . ($feedNotice->author_name ?? '') . ' ' . ($feedNotice->author_department ?? ''));

                        if (str_contains($noticeTypeLower, 'office order')) {
                            $noticeBadgeClass = 'notices-feed-badge--order';
                            $noticeBadgeLabel = $feedNotice->notice_type;
                        } elseif (str_contains($noticeTypeLower, 'course notice')) {
                            $noticeBadgeClass = 'notices-feed-badge--work';
                            $noticeBadgeLabel = 'Work Allocations';
                        } elseif (str_contains($noticeTypeLower, 'work allocation')) {
                            $noticeBadgeClass = 'notices-feed-badge--work';
                            $noticeBadgeLabel = $feedNotice->notice_type;
                        } else {
                            $noticeBadgeClass = 'notices-feed-badge--notice';
                            $noticeBadgeLabel = $feedNotice->notice_type ?? 'Notice';
                        }
                    @endphp
                    <div class="notices-feed-item"
                        data-feed-search="{{ $feedNoticeSearch }}"
                        data-notice-year="{{ $noticeYear }}"
                        data-notice-type="{{ $noticeTypeLower }}"
                        data-notice-dept="{{ $noticeDeptLower }}"
                        data-notice-audience="{{ $noticeAudienceLower }}"
                        data-notice-pk="{{ $feedNotice->pk }}">
                        <div class="notices-feed-item__header" role="button" tabindex="0"
                            id="notice-head-{{ $feedNotice->pk }}"
                            aria-expanded="false" aria-controls="notice-panel-{{ $feedNotice->pk }}"
                            aria-label="View notice: {{ e($feedNotice->notice_title ?? 'Notice') }}">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div class="notices-feed-item__body min-w-0">
                                    <span class="notices-feed-item__title d-block mb-1">{{ $feedNotice->notice_title }}</span>
                                    <small class="notices-feed-item__meta">
                                        ~by <strong>{{ $feedNotice->author_name ?? 'System' }}@if($feedNotice->author_department ?? '') ({{ $feedNotice->author_department }})@endif</strong>
                                        on {{ $feedNoticeDate }}
                                    </small>
                                </div>
                                <span class="notices-feed-badge {{ $noticeBadgeClass }} flex-shrink-0">{{ $noticeBadgeLabel }}</span>
                            </div>
                        </div>
                        <div class="notices-feed-item__panel" id="notice-panel-{{ $feedNotice->pk }}"
                            role="region" aria-labelledby="notice-head-{{ $feedNotice->pk }}">
                            <div class="notices-feed-item__panel-inner">
                                @if(trim(strip_tags((string) ($feedNotice->description ?? ''))) !== '')
                                <div class="notice-description-content">{!! $feedNotice->description !!}</div>
                                @else
                                <p class="text-muted fst-italic mb-0">No description provided.</p>
                                @endif
                                @if($feedNotice->document)
                                <div class="mt-3">
                                    {{-- `notices-feed-item__attachment` carries no CSS — it is the hook the
                                         accordion click handler uses to let this link through instead of
                                         collapsing the panel. Do not remove it as an "unused" class. --}}
                                    <a href="{{ asset('storage/' . $feedNotice->document) }}" target="_blank"
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
                        @if($noticeFilters['q'] !== '' || $noticeFilters['year'] !== '' || $noticeFilters['type'] !== '' || $noticeFilters['dept'] !== '' || $noticeFilters['audience'] !== '')
                        No notices match these filters.
                        @else
                        No notices available.
                        @endif
                    </p>
                    @endforelse
                </div>

                @if($notices->hasPages())
                <div class="notices-feed-pagination d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                    <small class="text-body-secondary">
                        Showing {{ $notices->firstItem() }}–{{ $notices->lastItem() }} of {{ number_format($notices->total()) }}
                    </small>
                    {{ $notices->onEachSide(1)->links('vendor.pagination.custom') }}
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
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-feed.css') }}?v=15">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLabels = {
        notifications: 'Notifications',
        notices: 'Notices',
        birthdays: 'Birthdays',
        wishes: 'Wishes'
    };
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
        const url = new URL(feedBaseUrl, window.location.origin);
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

        if (markAllBtn) {
            markAllBtn.classList.toggle('d-none', activeTab !== 'notifications' && activeTab !== 'wishes');
        }

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
            // Reset Filters, Add New Notice and the four selects all live INSIDE
            // the filter form. Submitting here when focus moves to one of them
            // would navigate first and swallow that click — the select would not
            // even open. Their own action already carries the synced `q`.
            const form = document.getElementById('notice-filter-form');
            if (form && e.relatedTarget && form.contains(e.relatedTarget)) return;
            submitNoticeSearch();
        });
    }

    setActiveTab(activeTab, false);

    window.addEventListener('popstate', function() {
        setActiveTab(tabFromUrl(), false);
    });

    // Each select is a form field now — changing one reloads with the filter applied.
    // "Reset Filters" is a plain link back to the unfiltered tab, so it needs no JS.
    document.querySelectorAll('.notices-feed-filter').forEach(function(sel) {
        sel.addEventListener('change', function() {
            const form = document.getElementById('notice-filter-form');
            if (form) form.submit();
        });
    });

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
