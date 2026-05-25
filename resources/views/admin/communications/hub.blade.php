@extends('admin.layouts.master')

@section('title', $sectionTitles[$activeSection] ?? 'Communications')

@push('styles')
@include('admin.NoticeNotification.partials.module-styles')
<style>
.comms-hub-section { display: none; }
.comms-hub-section.active { display: block; }
.comms-hub-item {
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 0.75rem;
    padding: 1rem 1.15rem;
    margin-bottom: 0.75rem;
    background: #fff;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.comms-hub-item:hover { box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.06); }
.comms-hub-item.unread { border-color: rgba(0, 74, 147, 0.25); background: #f8fbff; }
.comms-hub-birthday-today { background: #fff5f5; border-color: rgba(0, 74, 147, 0.08); }
.comms-hub-birthday-upcoming { background: #fff; }
.comms-hub-item.comms-hub-filter-hidden,
.notice-feed-card.comms-hub-filter-hidden { display: none !important; }
#commsHubMainTabs .nav-link {
    border: none;
    cursor: pointer;
}
</style>
@endpush

@section('content')
<div class="container-fluid notice-module-page" id="comms-hub-root" data-initial-section="{{ $activeSection }}">
    <x-breadcrum
        title="Communications"
        :items="[
            ['label' => 'Home', 'url' => route('admin.dashboard')],
            ['label' => $sectionTitles[$activeSection] ?? 'Communications'],
        ]"
    />
    <x-session_message />

    <div class="card notice-card border-0 shadow-sm rounded-1 mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-stretch align-items-md-center justify-content-between gap-3 notice-feed-toolbar mb-4">
                <ul class="nav nav-pills flex-nowrap mb-0" role="tablist" aria-label="Communications sections" id="commsHubMainTabs">
                    @foreach(['notifications', 'notices', 'birthdays', 'wishes'] as $key)
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            class="nav-link {{ $activeSection === $key ? 'active' : '' }}"
                            data-comms-tab="{{ $key }}"
                            role="tab"
                            aria-selected="{{ $activeSection === $key ? 'true' : 'false' }}"
                        >{{ $sectionTitles[$key] }}</button>
                    </li>
                    @endforeach
                </ul>
                <div class="notice-feed-search flex-shrink-0 w-100 w-md-auto" style="min-width: 220px; max-width: 320px;">
                    <input type="hidden" id="commsHubSectionInput" value="{{ $activeSection }}">
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text"><i class="bi bi-search text-muted" aria-hidden="true"></i></span>
                        <input type="search" id="commsHubSearch" value="{{ $q }}" class="form-control" placeholder="Search" autocomplete="off" aria-label="Search in current tab">
                    </div>
                </div>
            </div>

            {{-- Notifications --}}
            <div id="comms-section-notifications" class="comms-hub-section {{ $activeSection === 'notifications' ? 'active' : '' }}" role="tabpanel" data-comms-section-pane="notifications">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <h2 class="h6 mb-0 fw-bold" data-comms-section-title="notifications">
                        {{ str_pad((string) $notifications->count(), 2, '0', STR_PAD_LEFT) }} Notifications
                    </h2>
                    <button type="button" class="btn btn-link btn-sm p-0 text-primary fw-semibold text-decoration-underline {{ $notificationUnreadCount > 0 ? '' : 'd-none' }}" id="commsMarkAllRead">
                        Mark all as read
                    </button>
                </div>
                @forelse($notifications as $notification)
                @php
                    $whenLabel = isset($notification->created_at) ? \Carbon\Carbon::parse($notification->created_at)->format('d/m/Y h:i A') : '—';
                    $plainMsg = \App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? '');
                    $searchText = mb_strtolower(($notification->title ?? '') . ' ' . $plainMsg . ' ' . ($notification->sender_display ?? ''));
                @endphp
                <article class="comms-hub-item {{ empty($notification->is_read) ? 'unread' : '' }}" data-comms-searchable data-search-text="{{ e($searchText) }}">
                    <button type="button"
                        class="btn btn-link text-start text-decoration-none w-100 p-0 border-0 comms-notification-open"
                        data-notification-id="{{ $notification->pk }}">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                            <h3 class="notice-feed-card-title mb-0">{{ $notification->title ?? 'Notification' }}</h3>
                            <div class="notice-feed-card-meta text-md-end">
                                ~by {{ $notification->sender_display ?? '—' }} on {{ $whenLabel }}
                            </div>
                        </div>
                        <div class="notice-feed-card-body text-body text-start">
                            {{ \Illuminate\Support\Str::limit($plainMsg, 600) }}
                        </div>
                    </button>
                </article>
                @empty
                <div class="text-center text-muted py-5 comms-hub-empty" data-comms-empty="notifications">
                    <i class="bi bi-bell-slash display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
                    <p class="mb-0">No notifications found.</p>
                </div>
                @endforelse
            </div>

            {{-- Notices --}}
            <div id="comms-section-notices" class="comms-hub-section {{ $activeSection === 'notices' ? 'active' : '' }}" role="tabpanel" data-comms-section-pane="notices">
                @if($noticeCategoryTabs->isEmpty())
                <div class="text-center text-muted py-5 comms-hub-empty" data-comms-empty="notices">
                    <i class="bi bi-file-earmark-text display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
                    <p class="mb-0">No notices available.</p>
                </div>
                @else
                <div id="comms-notice-feed-root">
                    <ul class="nav nav-pills notice-feed-pills flex-nowrap mb-3" role="tablist" aria-label="Notice categories">
                        @foreach($noticeCategoryTabs as $tab)
                        <li class="nav-item" role="presentation">
                            <button type="button"
                                class="nav-link {{ $tab['key'] === $activeNoticeTabKey ? 'active' : '' }}"
                                data-notice-sub-tab="{{ $tab['key'] }}"
                                role="tab">{{ $tab['label'] }}</button>
                        </li>
                        @endforeach
                    </ul>
                    @foreach($noticeCategoryTabs as $tab)
                    @php $isActive = $tab['key'] === $activeNoticeTabKey; @endphp
                    <div class="comms-notice-pane {{ $isActive ? '' : 'd-none' }}" data-notice-pane="{{ $tab['key'] }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                            <h2 class="h6 mb-0 fw-bold" data-comms-section-title="notices">
                                {{ str_pad((string) $tab['total'], 2, '0', STR_PAD_LEFT) }} {{ $tab['total'] === 1 ? 'Notice' : 'Notices' }}
                            </h2>
                            @if(hasRole('Admin') || hasRole('Super Admin'))
                            <a href="{{ route('admin.notice.index') }}" class="small text-primary fw-semibold text-decoration-underline">
                                <i class="bi bi-gear me-1" aria-hidden="true"></i>Manage notices
                            </a>
                            @endif
                        </div>
                        @foreach($tab['notices'] as $notice)
                        @php
                            $when = $notice->display_date ?? $notice->created_at ?? null;
                            $whenLabel = $when ? \Carbon\Carbon::parse($when)->format('d/m/Y h:i A') : '—';
                            $plainDesc = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($notice->description ?? ''))));
                            $searchText = mb_strtolower(($notice->notice_title ?? '') . ' ' . $plainDesc . ' ' . ($notice->creator_display ?? '') . ' ' . ($notice->subcategory_name ?? ''));
                        @endphp
                        <article id="notice-feed-card-{{ $notice->pk }}"
                            class="notice-feed-card {{ !empty($highlightNoticePk) && (int) $notice->pk === (int) $highlightNoticePk ? 'notice-feed-card-highlight' : '' }}"
                            data-comms-searchable data-search-text="{{ e($searchText) }}">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                <h3 class="notice-feed-card-title">{{ $notice->notice_title }}</h3>
                                <div class="notice-feed-card-meta text-md-end">
                                    ~by {{ $notice->creator_display ?? '—' }} on {{ $whenLabel }}
                                </div>
                            </div>
                            @if(!empty($notice->subcategory_name))
                            <div class="small text-muted mt-1"><i class="bi bi-tag me-1" aria-hidden="true"></i>{{ $notice->subcategory_name }}</div>
                            @endif
                            <div class="notice-feed-card-body">{{ \Illuminate\Support\Str::limit($plainDesc, 600) }}</div>
                            @if(!empty($notice->document))
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $notice->document) }}" target="_blank" rel="noopener" class="text-danger text-decoration-none small fw-semibold">
                                    <i class="bi bi-paperclip me-1" aria-hidden="true"></i>View attachment
                                </a>
                            </div>
                            @endif
                        </article>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Birthdays --}}
            <div id="comms-section-birthdays" class="comms-hub-section {{ $activeSection === 'birthdays' ? 'active' : '' }}" role="tabpanel" data-comms-section-pane="birthdays">
                <h2 class="h6 fw-bold mb-3">Today's Birthdays</h2>
                @forelse($todayBirthdays as $person)
                @php
                    $photo = !empty($person->profile_picture) ? asset('storage/' . $person->profile_picture) : null;
                    $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
                    $searchText = mb_strtolower($fullName . ' ' . ($person->designation_name ?? ''));
                @endphp
                <div class="comms-hub-item comms-hub-birthday-today" data-comms-searchable data-search-text="{{ e($searchText) }}">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            @if($photo)
                            <img src="{{ $photo }}" alt="" class="rounded-circle object-fit-cover flex-shrink-0" width="48" height="48" loading="lazy">
                            @else
                            <div class="rounded-circle bg-primary-subtle text-primary fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                                {{ strtoupper(substr((string)($person->first_name ?? ''), 0, 1)) }}
                            </div>
                            @endif
                            <div class="min-w-0">
                                <div class="notice-birthday-name text-truncate">{{ $fullName }}</div>
                                <div class="small text-muted text-truncate">{{ $person->designation_name ?? '' }}</div>
                            </div>
                        </div>
                        <a href="{{ route('admin.birthday-wish.index') }}" class="btn btn-sm btn-outline-primary rounded-1 flex-shrink-0">Wish them</a>
                    </div>
                </div>
                @empty
                <p class="text-muted small mb-4 comms-hub-empty" data-comms-empty="birthdays-today">No birthdays today.</p>
                @endforelse

                <h2 class="h6 fw-bold mb-3 mt-4">Upcoming Birthdays</h2>
                @forelse($upcomingBirthdays as $person)
                @php
                    $photo = !empty($person->profile_picture) ? asset('storage/' . $person->profile_picture) : null;
                    $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
                    $searchText = mb_strtolower($fullName . ' ' . ($person->designation_name ?? '') . ' ' . ($person->birthday_date ?? ''));
                @endphp
                <div class="comms-hub-item comms-hub-birthday-upcoming" data-comms-searchable data-search-text="{{ e($searchText) }}">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            @if($photo)
                            <img src="{{ $photo }}" alt="" class="rounded-circle object-fit-cover flex-shrink-0" width="48" height="48" loading="lazy">
                            @else
                            <div class="rounded-circle bg-secondary-subtle text-secondary fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                                {{ strtoupper(substr((string)($person->first_name ?? ''), 0, 1)) }}
                            </div>
                            @endif
                            <div class="min-w-0">
                                <div class="notice-birthday-name text-truncate">{{ $fullName }}</div>
                                <div class="small text-muted">{{ $person->designation_name ?? '' }} · {{ $person->birthday_date ?? '' }}</div>
                            </div>
                        </div>
                        <a href="{{ route('admin.birthday-wish.index') }}" class="btn btn-sm btn-outline-primary rounded-1 flex-shrink-0">Wish them Advanced</a>
                    </div>
                </div>
                @empty
                <p class="text-muted small mb-0 comms-hub-empty" data-comms-empty="birthdays-upcoming">No upcoming birthdays in the next 30 days.</p>
                @endforelse
            </div>

            {{-- Wishes --}}
            <div id="comms-section-wishes" class="comms-hub-section {{ $activeSection === 'wishes' ? 'active' : '' }}" role="tabpanel" data-comms-section-pane="wishes">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <h2 class="h6 mb-0 fw-bold" data-comms-section-title="wishes">
                        {{ str_pad((string) $wishes->count(), 2, '0', STR_PAD_LEFT) }} Wishes
                    </h2>
                </div>
                @forelse($wishes as $wish)
                @php
                    $whenLabel = isset($wish->created_at) ? \Carbon\Carbon::parse($wish->created_at)->format('d/m/Y h:i A') : '—';
                    $plainMsg = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($wish->message ?? ''))));
                    $searchText = mb_strtolower(($wish->title ?? '') . ' ' . $plainMsg . ' ' . ($wish->sender_display ?? ''));
                @endphp
                <article class="comms-hub-item {{ empty($wish->is_read) ? 'unread' : '' }}" data-comms-searchable data-search-text="{{ e($searchText) }}">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <h3 class="notice-feed-card-title mb-0">{{ $wish->title ?? 'Happy birthday' }}</h3>
                        <div class="notice-feed-card-meta text-md-end">
                            ~by {{ $wish->sender_display ?? '—' }} on {{ $whenLabel }}
                        </div>
                    </div>
                    <div class="notice-feed-card-body">{{ \Illuminate\Support\Str::limit($plainMsg, 600) }}</div>
                </article>
                @empty
                <div class="text-center text-muted py-5 comms-hub-empty" data-comms-empty="wishes">
                    <i class="bi bi-gift display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
                    <p class="mb-0">No wishes found.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var hubRoot = document.getElementById('comms-hub-root');
    if (!hubRoot) return;

    var sectionTitles = @json($sectionTitles);
    var initialSection = hubRoot.getAttribute('data-initial-section') || 'notifications';
    var sectionInput = document.getElementById('commsHubSectionInput');
    var searchInput = document.getElementById('commsHubSearch');
    var markAllBtn = document.getElementById('commsMarkAllRead');

    function ensureHomeGeneralSidebarActive() {
        var sidebarHome = document.getElementById('sidebar-home');
        if (!sidebarHome) return;
        var mini1 = document.getElementById('mini-1');
        if (!mini1) return;
        sidebarHome.querySelectorAll('.mini-nav-item').forEach(function (el) {
            el.classList.remove('selected');
        });
        mini1.classList.add('selected');
        var miniNav = sidebarHome.querySelector('.mini-nav');
        if (miniNav && typeof window.sargamActivateMiniNavItem === 'function') {
            window.sargamActivateMiniNavItem(miniNav, mini1, true);
        }
    }
    ensureHomeGeneralSidebarActive();
    setTimeout(ensureHomeGeneralSidebarActive, 250);

    function updateUrlSection(key, extra) {
        try {
            var u = new URL(window.location.href);
            u.searchParams.set('section', key);
            if (extra && extra.tab) {
                u.searchParams.set('tab', extra.tab);
            } else if (key !== 'notices') {
                u.searchParams.delete('tab');
            }
            if (extra && extra.q !== undefined) {
                if (extra.q) u.searchParams.set('q', extra.q);
                else u.searchParams.delete('q');
            }
            window.history.replaceState({ commsSection: key }, '', u.toString());
        } catch (e) {}
    }

    function getActivePane() {
        return hubRoot.querySelector('.comms-hub-section.active');
    }

    function applySearchFilter() {
        var pane = getActivePane();
        if (!pane || !searchInput) return;
        var needle = (searchInput.value || '').trim().toLowerCase();
        var visible = 0;
        pane.querySelectorAll('[data-comms-searchable]').forEach(function (el) {
            var hay = (el.getAttribute('data-search-text') || el.textContent || '').toLowerCase();
            var show = !needle || hay.indexOf(needle) !== -1;
            el.classList.toggle('comms-hub-filter-hidden', !show);
            if (show) visible++;
        });
        pane.querySelectorAll('.comms-hub-empty').forEach(function (el) {
            el.classList.toggle('d-none', visible > 0);
        });
        updateUrlSection(sectionInput ? sectionInput.value : initialSection, { q: needle });
    }

    function activateMainSection(key) {
        if (!sectionTitles[key]) return;
        hubRoot.querySelectorAll('[data-comms-tab]').forEach(function (btn) {
            var on = btn.getAttribute('data-comms-tab') === key;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        hubRoot.querySelectorAll('.comms-hub-section').forEach(function (pane) {
            pane.classList.toggle('active', pane.getAttribute('data-comms-section-pane') === key);
        });
        if (sectionInput) sectionInput.value = key;
        if (markAllBtn) {
            markAllBtn.classList.toggle('d-none', key !== 'notifications');
        }
        var crumb = document.querySelector('.breadcrumb-item.active, .page-breadcrumb .active');
        if (crumb) crumb.textContent = sectionTitles[key];
        updateUrlSection(key, { q: (searchInput && searchInput.value) ? searchInput.value.trim() : '' });
        applySearchFilter();
    }

    hubRoot.querySelectorAll('[data-comms-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateMainSection(btn.getAttribute('data-comms-tab'));
        });
    });

    var noticeRoot = document.getElementById('comms-notice-feed-root');
    if (noticeRoot) {
        noticeRoot.querySelectorAll('[data-notice-sub-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-notice-sub-tab');
                noticeRoot.querySelectorAll('[data-notice-sub-tab]').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                noticeRoot.querySelectorAll('[data-notice-pane]').forEach(function (pane) {
                    pane.classList.toggle('d-none', pane.getAttribute('data-notice-pane') !== key);
                });
                updateUrlSection('notices', { tab: key, q: (searchInput && searchInput.value) ? searchInput.value.trim() : '' });
                applySearchFilter();
            });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            applySearchFilter();
        });
    }

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            if (typeof markAllAsRead === 'function') {
                markAllAsRead();
                return;
            }
            fetch('{{ route('admin.notifications.mark-all-read') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function () { window.location.reload(); });
        });
    }

    document.querySelectorAll('.comms-notification-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-notification-id');
            if (!id) return;
            if (typeof markAsReadDashboard === 'function') {
                markAsReadDashboard(id, btn);
                return;
            }
            if (typeof markAsRead === 'function') {
                markAsRead(id, btn);
            }
        });
    });

    activateMainSection(initialSection);

    @if(!empty($highlightNoticePk))
    var highlightEl = document.getElementById('notice-feed-card-{{ (int) $highlightNoticePk }}');
    if (highlightEl) {
        setTimeout(function () {
            highlightEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 200);
    }
    @endif
});
</script>
@endpush
