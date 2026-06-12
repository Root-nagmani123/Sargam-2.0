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

            {{-- Notices (with category tabs like dashboard) --}}
            <div data-feed-panel="notices" class="{{ $activeTab !== 'notices' ? 'd-none' : '' }}">
                @if(count($notices) > 0)
                <div class="dashboard-notice-tabs" role="tablist" aria-label="Notice categories">
                    @foreach($noticeTabKeys as $tabKey)
                    <button type="button"
                        class="dashboard-notice-tab rounded-1 {{ $tabKey === $defaultNoticeTab ? 'active' : '' }}"
                        role="tab" data-notice-tab="{{ $tabKey }}">
                        {{ $noticeTabLabels[$tabKey] }}@if($noticeTabCounts[$tabKey] > 0): {{ $noticeTabCounts[$tabKey] }}@endif
                    </button>
                    @endforeach
                </div>
                <p class="dashboard-feed-empty d-none mb-3" id="dashboard-feed-notice-tab-empty">No notices in this category.</p>
                @endif
                <div id="dashboard-feed-page-list-notices">
                    @forelse($notices as $feedNotice)
                    @php
                    $noticeType = $feedNotice->notice_type ?? '';
                    $noticeTypeLower = strtolower((string) $noticeType);
                    if (str_contains($noticeTypeLower, 'office order')) {
                        $noticeTab = 'office-orders';
                    } elseif (str_contains($noticeTypeLower, 'course notice')) {
                        $noticeTab = 'work-allocation';
                    } else {
                        $noticeTab = 'notice-circular';
                    }
                    $feedNoticeFrom = !empty($feedNotice->display_date) ? \Carbon\Carbon::parse($feedNotice->display_date)->format('j F, Y') : null;
                    $feedNoticeTo = !empty($feedNotice->expiry_date) ? \Carbon\Carbon::parse($feedNotice->expiry_date)->format('j F, Y') : null;
                    $feedNoticeDates = ($feedNoticeFrom && $feedNoticeTo) ? $feedNoticeFrom . ' to ' . $feedNoticeTo : ($feedNoticeFrom ?? '—');
                    $feedNoticeSearch = strtolower(($feedNotice->notice_title ?? '') . ' ' . ($feedNotice->notice_type ?? '') . ' ' . $feedNoticeDates);
                    @endphp
                    @php
                        $plainDesc = !empty($feedNotice->description) ? strip_tags($feedNotice->description) : '';
                        $descWordCount = $plainDesc !== '' ? str_word_count($plainDesc) : 0;
                        $needsTruncation = $descWordCount > 50;
                        $truncatedText = $needsTruncation ? Str::words($plainDesc, 50, '') : null;
                    @endphp
                    <div class="dashboard-notice-item {{ $noticeTab !== $defaultNoticeTab ? 'd-none' : '' }}"
                        data-notice-tab-item="{{ $noticeTab }}"
                        data-feed-search="{{ $feedNoticeSearch }}">
                        <span class="dashboard-notice-title">{{ $feedNotice->notice_title }}</span>
                        <small class="dashboard-notice-date">{{ $feedNoticeDates }}</small>
                        @if(!empty($feedNotice->description))
                        <div class="notice-description-content mt-2">
                            @if($needsTruncation)
                            <span class="notice-desc-preview">{{ $truncatedText }}<span class="notice-desc-ellipsis">... </span><button type="button" class="btn btn-link btn-sm p-0 notice-desc-toggle align-baseline">See more</button></span>
                            <span class="notice-desc-full d-none">@clean($feedNotice->description)<button type="button" class="btn btn-link btn-sm p-0 ms-1 notice-desc-toggle align-baseline">See less</button></span>
                            @else
                            @clean($feedNotice->description)
                            @endif
                        </div>
                        @endif
                        @if($feedNotice->document)
                        <a href="{{ asset('storage/' . $feedNotice->document) }}" target="_blank"
                            class="small text-danger text-decoration-none d-inline-flex align-items-center gap-1 mt-2">
                            <i class="bi bi-paperclip" aria-hidden="true"></i> View attachment
                        </a>
                        @endif
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
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-feed.css') }}?v=6">
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
        panel.querySelectorAll('[data-feed-search]').forEach(function(item) {
            const haystack = (item.getAttribute('data-feed-search') || '').toLowerCase();
            item.classList.toggle('d-none', query !== '' && haystack.indexOf(query) === -1);
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

    document.querySelectorAll('.dashboard-notice-tab[data-notice-tab]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tab = btn.dataset.noticeTab;
            document.querySelectorAll('.dashboard-notice-tab').forEach(function(b) {
                b.classList.toggle('active', b.dataset.noticeTab === tab);
            });
            let visible = 0;
            document.querySelectorAll('[data-notice-tab-item]').forEach(function(item) {
                const show = item.dataset.noticeTabItem === tab;
                item.classList.toggle('d-none', !show);
                if (show && !item.classList.contains('d-none')) visible++;
            });
            const emptyEl = document.getElementById('dashboard-feed-notice-tab-empty');
            if (emptyEl) emptyEl.classList.toggle('d-none', visible > 0);
            applySearch();
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

// Notice description See more / See less toggle
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
