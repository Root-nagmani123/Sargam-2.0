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
            <div data-feed-panel="notices" class="{{ $activeTab !== 'notices' ? 'd-none' : '' }}">
                @php
                    $noticeYears     = $notices->map(fn($n) => !empty($n->display_date) ? \Carbon\Carbon::parse($n->display_date)->year : null)->filter()->unique()->sort()->values();
                    $noticeTypes     = $notices->pluck('notice_type')->filter()->unique()->sort()->values();
                    $noticeDepts     = $notices->pluck('author_department')->filter()->unique()->sort()->values();
                    $noticeAudiences = $notices->pluck('target_audience')->filter()->unique()->sort()->values();
                @endphp

                <h2 class="notices-feed-section-title">Notices</h2>

                <div class="notices-feed-toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="notices-feed-toolbar__label">Filters</span>

                    <select class="form-select form-select-sm notices-feed-filter" id="notice-filter-year" aria-label="Filter by year">
                        <option value="">Year</option>
                        @foreach($noticeYears as $yr)
                        <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm notices-feed-filter" id="notice-filter-type" aria-label="Filter by type">
                        <option value="">Type</option>
                        @foreach($noticeTypes as $nt)
                        <option value="{{ strtolower($nt) }}">{{ $nt }}</option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm notices-feed-filter" id="notice-filter-dept" aria-label="Filter by department">
                        <option value="">Department</option>
                        @foreach($noticeDepts as $nd)
                        <option value="{{ strtolower($nd) }}">{{ $nd }}</option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm notices-feed-filter" id="notice-filter-audience" aria-label="Filter by target audience">
                        <option value="">Target Audience</option>
                        @foreach($noticeAudiences as $na)
                        <option value="{{ strtolower($na) }}">{{ $na }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="btn btn-sm notices-feed-reset-btn" id="notice-filter-reset">Reset Filters</button>

                    @if(hasRole('Admin'))
                    <a href="{{ route('admin.notice.create') }}" class="btn btn-sm notices-feed-add-btn ms-auto">
                        <i class="bi bi-plus-square me-1" aria-hidden="true"></i>Add New Notice
                    </a>
                    @endif
                </div>

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
                        data-notice-pk="{{ $feedNotice->pk }}"
                        data-notice-title="{{ e($feedNotice->notice_title ?? '') }}"
                        data-notice-desc='@json($feedNotice->description ?? "")'
                        data-notice-badge="{{ e($noticeBadgeLabel) }}"
                        data-notice-meta="{{ e('~by ' . ($feedNotice->author_name ?? 'System') . ($feedNotice->author_department ? ' (' . $feedNotice->author_department . ')' : '') . ' on ' . $feedNoticeDate) }}"
                        data-notice-doc="{{ $feedNotice->document ? asset('storage/' . $feedNotice->document) : '' }}"
                        role="button" tabindex="0" aria-label="View notice: {{ e($feedNotice->notice_title ?? 'Notice') }}">
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
                    @empty
                    <p class="dashboard-feed-empty mb-0">No notices available.</p>
                    @endforelse
                </div>
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

{{-- Notice detail modal --}}
<div class="modal fade" id="notice-detail-modal" tabindex="-1"
    aria-labelledby="notice-detail-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-1">
                <div class="flex-grow-1 min-w-0 pe-2">
                    <h5 class="modal-title fw-semibold lh-sm mb-1" id="notice-detail-modal-label"></h5>
                    <small class="text-muted" id="notice-detail-modal-meta"></small>
                </div>
                <span class="notices-feed-badge flex-shrink-0 me-2 mt-1" id="notice-detail-modal-badge"></span>
                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <hr class="mt-0 mb-3">
                <div class="notice-description-content" id="notice-detail-modal-body"></div>
                <div class="mt-3 d-none" id="notice-detail-modal-attachment">
                    <a id="notice-detail-modal-doc" href="#" target="_blank" rel="noopener noreferrer"
                        class="small text-danger text-decoration-none d-inline-flex align-items-center gap-1">
                        <i class="bi bi-paperclip" aria-hidden="true"></i> View Attachment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-feed.css') }}?v=11">
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
        const items = panel.querySelectorAll('[data-feed-search]');
        let visible = 0;
        items.forEach(function(item) {
            if (!item.classList.contains('d-none')) visible++;
        });
        const label = tabLabels[activeTab] || 'Items';
        countEl.textContent = String(visible).padStart(2, '0') + ' ' + label;
    }

    function applySearch() {
        const query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
        const panel = getActivePanel();
        if (!panel) return;

        const isNotices = activeTab === 'notices';
        const filterYear     = isNotices ? (document.getElementById('notice-filter-year')?.value     || '') : '';
        const filterType     = isNotices ? (document.getElementById('notice-filter-type')?.value     || '') : '';
        const filterDept     = isNotices ? (document.getElementById('notice-filter-dept')?.value     || '') : '';
        const filterAudience = isNotices ? (document.getElementById('notice-filter-audience')?.value || '') : '';

        panel.querySelectorAll('[data-feed-search]').forEach(function(item) {
            const haystack = (item.getAttribute('data-feed-search') || '').toLowerCase();
            let hidden = query !== '' && haystack.indexOf(query) === -1;
            if (!hidden && isNotices) {
                if (filterYear     && String(item.dataset.noticeYear     || '') !== filterYear)                         hidden = true;
                if (!hidden && filterType     && (item.dataset.noticeType     || '').indexOf(filterType)     === -1) hidden = true;
                if (!hidden && filterDept     && (item.dataset.noticeDept     || '').indexOf(filterDept)     === -1) hidden = true;
                if (!hidden && filterAudience && (item.dataset.noticeAudience || '').indexOf(filterAudience) === -1) hidden = true;
            }
            item.classList.toggle('d-none', hidden);
        });
        updateCount();
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
            searchInput.value = '';
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
        searchInput.addEventListener('input', applySearch);
    }

    setActiveTab(activeTab, false);

    window.addEventListener('popstate', function() {
        setActiveTab(tabFromUrl(), false);
    });

    document.querySelectorAll('.notices-feed-filter').forEach(function(sel) {
        sel.addEventListener('change', applySearch);
    });

    const noticeFilterReset = document.getElementById('notice-filter-reset');
    if (noticeFilterReset) {
        noticeFilterReset.addEventListener('click', function() {
            document.querySelectorAll('.notices-feed-filter').forEach(function(sel) { sel.value = ''; });
            applySearch();
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

// Notice card → open detail modal
(function () {
    const noticeModal = document.getElementById('notice-detail-modal');
    if (!noticeModal) return;

    function openNoticeModal(card) {
        document.getElementById('notice-detail-modal-label').textContent = card.dataset.noticeTitle || '';
        document.getElementById('notice-detail-modal-meta').textContent  = card.dataset.noticeMeta  || '';

        const badgeEl = document.getElementById('notice-detail-modal-badge');
        badgeEl.textContent = card.dataset.noticeBadge || '';
        badgeEl.className   = 'notices-feed-badge flex-shrink-0 me-2 mt-1 ' + (card.querySelector('.notices-feed-badge')?.className.split(' ').find(c => c.startsWith('notices-feed-badge--')) || '');

        let desc = '';
        try { desc = JSON.parse(card.dataset.noticeDesc || '""'); } catch (e) { desc = ''; }
        document.getElementById('notice-detail-modal-body').innerHTML = desc || '<p class="text-muted fst-italic mb-0">No description provided.</p>';

        const attachEl   = document.getElementById('notice-detail-modal-attachment');
        const attachLink = document.getElementById('notice-detail-modal-doc');
        const docUrl     = card.dataset.noticeDoc || '';
        if (docUrl) {
            attachLink.href = docUrl;
            attachEl.classList.remove('d-none');
        } else {
            attachEl.classList.add('d-none');
        }

        bootstrap.Modal.getOrCreateInstance(noticeModal).show();
    }

    document.addEventListener('click', function (e) {
        const card = e.target.closest('.notices-feed-item[data-notice-pk]');
        if (card) openNoticeModal(card);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const card = document.activeElement?.closest('.notices-feed-item[data-notice-pk]');
        if (card) { e.preventDefault(); openNoticeModal(card); }
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
