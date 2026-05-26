@extends('admin.layouts.master')

@section('title', $sectionTitles[$activeSection] ?? 'Communications')

@push('styles')
@include('admin.NoticeNotification.partials.module-styles')
@include('admin.communications.partials.birthday-card-styles')
@include('admin.communications.partials.comms-feed-cards-styles')
<style>
.comms-hub-section { display: none; }
.comms-hub-section.active { display: block; }
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

    <div class="card notice-card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-stretch align-items-md-center justify-content-between gap-3 notice-feed-toolbar mb-4">
                <ul class="nav nav-pills rounded-1 flex-nowrap mb-0" role="tablist" aria-label="Communications sections" id="commsHubMainTabs">
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
                    <div class="input-group input-group-sm shadow-sm rounded-1 overflow-hidden">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted" aria-hidden="true"></i></span>
                        <input type="search" id="commsHubSearch" value="{{ $q }}" class="form-control border-start-0" placeholder="Search" autocomplete="off" aria-label="Search in current tab">
                    </div>
                </div>
            </div>

            {{-- Notifications --}}
            <div id="comms-section-notifications" class="comms-hub-section {{ $activeSection === 'notifications' ? 'active' : '' }}" role="tabpanel" data-comms-section-pane="notifications">
                <div class="comms-hub-section-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="comms-hub-feed-icon rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                            <i class="bi bi-bell-fill"></i>
                        </span>
                        <h2 class="h6 mb-0 fw-bold" data-comms-section-title="notifications">
                            <span class="badge text-bg-primary rounded-1 me-1">{{ str_pad((string) $notifications->count(), 2, '0', STR_PAD_LEFT) }}</span>
                            Notifications
                        </h2>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-1 {{ $notificationUnreadCount > 0 ? '' : 'd-none' }}" id="commsMarkAllRead">
                        <i class="bi bi-check2-all me-1" aria-hidden="true"></i>Mark all as read
                    </button>
                </div>
                <div class="comms-hub-feed-list vstack gap-2">
                @forelse($notifications as $notification)
                @php
                    $whenLabel = isset($notification->created_at) ? \Carbon\Carbon::parse($notification->created_at)->format('d/m/Y h:i A') : '—';
                    $plainMsg = \App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? '');
                    $searchText = mb_strtolower(($notification->title ?? '') . ' ' . $plainMsg . ' ' . ($notification->sender_display ?? ''));
                    $isUnread = empty($notification->is_read);
                    $msgPreview = \Illuminate\Support\Str::words($plainMsg, 20, '…');
                    $hasMoreMsg = $plainMsg !== '' && \Illuminate\Support\Str::wordCount($plainMsg) > 20;
                @endphp
                <article class="comms-hub-item comms-hub-notification-card comms-hub-desc-expandable shadow-sm {{ $isUnread ? 'unread' : '' }}" data-comms-searchable data-search-text="{{ e($searchText) }}" tabindex="0">
                    <button type="button"
                        class="btn btn-link text-start text-decoration-none w-100 border-0 comms-notification-open"
                        data-notification-id="{{ $notification->pk }}">
                        <div class="d-flex align-items-start gap-3">
                            <span class="comms-hub-feed-icon rounded-circle {{ $isUnread ? 'bg-primary text-white' : 'bg-body-secondary bg-opacity-25 text-secondary' }} d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                                <i class="bi bi-bell{{ $isUnread ? '-fill' : '' }}"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                    <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
                                        <h3 class="notice-feed-card-title h6 fw-semibold mb-0 text-truncate">{{ $notification->title ?? 'Notification' }}</h3>
                                        @if($isUnread)
                                        <span class="badge rounded-1 text-bg-primary">New</span>
                                        @endif
                                    </div>
                                    <div class="notice-feed-card-meta small text-muted text-md-end flex-shrink-0">
                                        <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $notification->sender_display ?? '—' }}
                                        <span class="mx-1 opacity-50">·</span>
                                        <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ $whenLabel }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </button>
                    @if($plainMsg !== '')
                    <div class="comms-hub-desc-wrap">
                        <p class="comms-hub-desc-preview notice-feed-card-body small text-body-secondary lh-base mb-0">{{ $msgPreview }}</p>
                        @if($hasMoreMsg)
                        <div class="comms-hub-desc-detail-block">
                            <div class="small text-muted mb-1 fw-semibold">Full description</div>
                            <div class="notice-feed-card-body small text-body-secondary lh-base">{{ $plainMsg }}</div>
                        </div>
                        @endif
                    </div>
                    @endif
                </article>
                @empty
                <div class="comms-hub-empty-state text-center text-muted py-5 comms-hub-empty" data-comms-empty="notifications">
                    <i class="bi bi-bell-slash display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
                    <p class="mb-0 fw-semibold">No notifications found</p>
                    <p class="small mb-0 mt-1">You are all caught up.</p>
                </div>
                @endforelse
                </div>
            </div>

            {{-- Notices --}}
            <div id="comms-section-notices" class="comms-hub-section {{ $activeSection === 'notices' ? 'active' : '' }}" role="tabpanel" data-comms-section-pane="notices">
                @if($noticeCategoryTabs->isEmpty())
                <div class="comms-hub-empty-state text-center text-muted py-5 comms-hub-empty" data-comms-empty="notices">
                    <i class="bi bi-file-earmark-text display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
                    <p class="mb-0 fw-semibold">No notices available</p>
                </div>
                @else
                <div id="comms-notice-feed-root">
                    <div class="comms-hub-notice-category-bar mb-3">
                        <ul class="nav nav-pills rounded-1 flex-nowrap mb-0" role="tablist" aria-label="Notice categories">
                            @foreach($noticeCategoryTabs as $tab)
                            <li class="nav-item" role="presentation">
                                <button type="button"
                                    class="nav-link {{ $tab['key'] === $activeNoticeTabKey ? 'active' : '' }}"
                                    data-notice-sub-tab="{{ $tab['key'] }}"
                                    role="tab"
                                    aria-selected="{{ $tab['key'] === $activeNoticeTabKey ? 'true' : 'false' }}">
                                    {{ $tab['label'] }}
                                    <span class="badge rounded-1 ms-1 {{ $tab['key'] === $activeNoticeTabKey ? 'text-bg-light text-primary' : 'text-bg-secondary' }}">{{ $tab['total'] }}</span>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @foreach($noticeCategoryTabs as $tab)
                    @php $isActive = $tab['key'] === $activeNoticeTabKey; @endphp
                    <div class="comms-notice-pane {{ $isActive ? '' : 'd-none' }}" data-notice-pane="{{ $tab['key'] }}" role="tabpanel">
                        <div class="comms-hub-section-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="comms-hub-feed-icon rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                                    <i class="bi bi-megaphone-fill"></i>
                                </span>
                                <h2 class="h6 mb-0 fw-bold" data-comms-section-title="notices">
                                    <span class="badge text-bg-primary rounded-1 me-1">{{ str_pad((string) $tab['total'], 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $tab['total'] === 1 ? 'Notice' : 'Notices' }}
                                </h2>
                            </div>
                            @if(hasRole('Admin') || hasRole('Super Admin'))
                            <a href="{{ route('admin.notice.index') }}" class="btn btn-sm btn-outline-primary rounded-1">
                                <i class="bi bi-gear me-1" aria-hidden="true"></i>Manage notices
                            </a>
                            @endif
                        </div>
                        <div class="comms-hub-feed-list vstack gap-2">
                        @foreach($tab['notices'] as $notice)
                        @php
                            $when = $notice->display_date ?? $notice->created_at ?? null;
                            $whenLabel = $when ? \Carbon\Carbon::parse($when)->format('d/m/Y h:i A') : '—';
                            $plainDesc = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($notice->description ?? ''))));
                            $searchText = mb_strtolower(($notice->notice_title ?? '') . ' ' . $plainDesc . ' ' . ($notice->creator_display ?? '') . ' ' . ($notice->subcategory_name ?? ''));
                            $isHighlighted = !empty($highlightNoticePk) && (int) $notice->pk === (int) $highlightNoticePk;
                            $descPreview = \Illuminate\Support\Str::words($plainDesc, 20, '…');
                            $hasMoreDesc = $plainDesc !== '' && \Illuminate\Support\Str::wordCount($plainDesc) > 20;
                        @endphp
                        <article id="notice-feed-card-{{ $notice->pk }}"
                            class="notice-feed-card comms-hub-notice-card comms-hub-desc-expandable shadow-sm {{ $isHighlighted ? 'notice-feed-card-highlight' : '' }}"
                            data-comms-searchable data-search-text="{{ e($searchText) }}" tabindex="0">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                <div class="min-w-0 flex-grow-1">
                                    <h3 class="notice-feed-card-title h6 fw-semibold mb-1">{{ $notice->notice_title }}</h3>
                                    @if(!empty($notice->subcategory_name))
                                    <span class="badge rounded-1 text-bg-info border border-info-subtle">
                                        <i class="bi bi-tag-fill me-1" aria-hidden="true"></i>{{ $notice->subcategory_name }}
                                    </span>
                                    @endif
                                </div>
                                <div class="notice-feed-card-meta small text-muted text-md-end flex-shrink-0">
                                    <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $notice->creator_display ?? '—' }}
                                    <span class="mx-1 opacity-50">·</span>
                                    <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ $whenLabel }}
                                </div>
                            </div>
                            @if($plainDesc !== '')
                            <div class="comms-hub-desc-wrap">
                                <p class="comms-hub-desc-preview notice-feed-card-body small text-body-secondary lh-base mb-0">{{ $descPreview }}</p>
                                @if($hasMoreDesc)
                                <div class="comms-hub-desc-detail-block">
                                    <div class="small text-muted mb-1 fw-semibold">Full description</div>
                                    <div class="notice-feed-card-body small text-body-secondary lh-base">{{ $plainDesc }}</div>
                                </div>
                                @endif
                            </div>
                            @endif
                            @if(!empty($notice->document))
                            <div class="mt-3 pt-2 border-top border-light-subtle">
                                <a href="{{ asset('storage/' . $notice->document) }}" target="_blank" rel="noopener"
                                    class="btn btn-sm btn-outline-danger rounded-1">
                                    <i class="bi bi-paperclip me-1" aria-hidden="true"></i>View attachment
                                </a>
                            </div>
                            @endif
                        </article>
                        @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Birthdays --}}
            <div id="comms-section-birthdays" class="comms-hub-section {{ $activeSection === 'birthdays' ? 'active' : '' }}" role="tabpanel" data-comms-section-pane="birthdays">
                <h2 class="h6 fw-bold mb-3">Today's Birthdays</h2>
                <div class="d-grid gap-2 gap-md-3 comms-hub-birthday-list comms-hub-birthday-today">
                    @forelse($todayBirthdays as $person)
                    @include('admin.communications.partials.birthday-person-card', [
                        'person' => $person,
                        'loopIndex' => $loop->index,
                        'wishBtnLabel' => 'Wish them',
                    ])
                    @empty
                    <p class="text-muted small mb-0 comms-hub-empty" data-comms-empty="birthdays-today">No birthdays today.</p>
                    @endforelse
                </div>

                <h2 class="h6 fw-bold mb-3 mt-4">Upcoming Birthdays</h2>
                <div class="d-grid gap-2 gap-md-3 comms-hub-birthday-list">
                    @forelse($upcomingBirthdays as $person)
                    @include('admin.communications.partials.birthday-person-card', [
                        'person' => $person,
                        'loopIndex' => $loop->index,
                        'wishBtnLabel' => 'Wish them Advanced',
                        'showBirthdayDate' => true,
                    ])
                    @empty
                    <p class="text-muted small mb-0 comms-hub-empty" data-comms-empty="birthdays-upcoming">No upcoming birthdays in the next 30 days.</p>
                    @endforelse
                </div>
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
                <article class="comms-hub-wish-card {{ empty($wish->is_read) ? 'unread' : '' }}" data-comms-searchable data-search-text="{{ e($searchText) }}" tabindex="0">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <h3 class="notice-feed-card-title mb-0">{{ $wish->title ?? 'Happy birthday' }}</h3>
                        <div class="notice-feed-card-meta text-md-end">
                            ~by {{ $wish->sender_display ?? '—' }} on {{ $whenLabel }}
                        </div>
                    </div>
                    <div class="notice-feed-card-body text-truncate" style="max-height: 3.6em; overflow: hidden;">
                        {{ \Illuminate\Support\Str::limit($plainMsg, 140) }}
                    </div>
                    @if($plainMsg !== '')
                    <div class="comms-hub-wish-detail-block">
                        <div class="small text-muted mb-1 fw-semibold">Full message</div>
                        <div class="notice-feed-card-body">{{ $plainMsg }}</div>
                    </div>
                    @endif
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
