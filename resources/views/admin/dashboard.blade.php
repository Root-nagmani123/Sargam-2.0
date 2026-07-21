@extends('admin.layouts.master')

@section('title', 'Dashboard')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-calendar.css') }}?v=4">
<link rel="stylesheet" href="{{ asset('css/dashboard-stat-cards.css') }}?v=2">
<link rel="stylesheet" href="{{ asset('css/dashboard-main.css') }}?v=4">

@php
$user = Auth::user();
$isAdminSummary = hasRole('Super Admin');
$daysOld = $isAdminSummary ? 10 : null;
$notifications = ($user && $user->user_id) ? notification()->getNotifications($user->user_id, 10, false, $daysOld) :
collect();
$notificationBadgeCount = ($user && $user->user_id)
? ($isAdminSummary ? notification()->getUnreadCount($user->user_id, $daysOld) : $notifications->count())
: 0;
$notices = get_notice_notification_by_role();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening' ); $userName=$user ?
    (trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'User')) : 'User';

    $todayBirthdayWishNotifications = collect();
    $myBirthdayWishesSummary = '';
    if (($isMyBirthday ?? false) && $user && $user->user_id) {
    $todayBirthdayWishNotifications = \App\Models\Notification::with('sender')
    ->where('receiver_user_id', $user->user_id)
    ->where('type', 'birthday')
    ->whereDate('created_at', today())
    ->orderByDesc('created_at')
    ->get();

    $myBirthdayWishSenderNames = $todayBirthdayWishNotifications->map(function ($notification) {
    if ($notification->sender) {
    return trim((string) ($notification->sender->first_name ?? $notification->sender->name ?? ''));
    }
    if (!empty($notification->message) && preg_match('/^(.+?)\s+wished you/i', $notification->message, $matches)) {
    return trim($matches[1]);
    }
    return null;
    })->filter()->unique()->values();

    $wishNameCount = $myBirthdayWishSenderNames->count();
    $wishTotal = (int) ($myBirthdayWishCount ?? 0);

    if ($wishNameCount === 1) {
    $myBirthdayWishesSummary = $myBirthdayWishSenderNames->first() . ' has sent their wish.';
    } elseif ($wishNameCount === 2) {
    $myBirthdayWishesSummary = $myBirthdayWishSenderNames->implode(' and ') . ' have sent their wishes.';
    } elseif ($wishNameCount > 2) {
    $others = $wishNameCount - 2;
    $myBirthdayWishesSummary = $myBirthdayWishSenderNames->take(2)->implode(', ')
    . ' and ' . $others . ' ' . ($others === 1 ? 'other' : 'others') . ' have sent their wishes.';
    } elseif ($wishTotal > 0) {
    $myBirthdayWishesSummary = $wishTotal === 1
    ? '1 person has sent their wish.'
    : $wishTotal . ' people have sent their wishes.';
    }
    }
    @endphp

    <div class="container-fluid">
        @if($isMyBirthday ?? false)
        {{-- Birthday Banner with Confetti (reference design) --}}
        <div class="dashboard-birthday-banner rounded-4 shadow-sm mb-3 position-relative overflow-hidden"
            id="birthday-banner">
            <div class="birthday-banner-accent" aria-hidden="true"></div>
            <canvas id="confetti-canvas" aria-hidden="true"></canvas>
            <button type="button" class="birthday-banner-close" id="birthday-banner-dismiss"
                aria-label="Dismiss birthday message">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="birthday-banner-inner">
                <div class="birthday-banner-text">
                    <h2 class="birthday-banner-title mb-2">Happy Birthday {{ $userName }}</h2>
                    <p class="birthday-banner-subtitle mb-0">
                        Wishing you a fantastic year ahead 🎉
                        @if(!empty($myBirthdayWishesSummary))
                        {{ ' ' . $myBirthdayWishesSummary }}
                        @endif
                        @if(($myBirthdayWishCount ?? 0) > 0)
                        <a href="{{ route('admin.dashboard.feed', ['tab' => 'wishes']) }}" class="birthday-banner-link"
                            id="btn-view-birthday-wishes">View all wishes →</a>
                        @endif
                    </p>
                </div>
                <div class="birthday-banner-illustration d-none d-sm-block" aria-hidden="true">
                    <svg viewBox="0 0 152 88" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="">
                        <ellipse cx="118" cy="72" rx="28" ry="6" fill="#E8EDF3" opacity="0.9" />
                        <rect x="98" y="52" width="22" height="18" rx="3" fill="#F4C430" />
                        <rect x="98" y="52" width="22" height="5" rx="2" fill="#E5A820" />
                        <path d="M99 52h20v3H99z" fill="#D4921A" />
                        <rect x="124" y="48" width="18" height="16" rx="3" fill="#E74C3C" />
                        <rect x="124" y="48" width="18" height="4" rx="2" fill="#C0392B" />
                        <path d="M108 38c0-8 6-14 14-14s14 6 14 14c0 6-4 11-10 13l-4 10-4-10c-6-2-10-7-10-13z"
                            fill="#3498DB" />
                        <path d="M108 38c0-8 6-14 14-14" stroke="#2980B9" stroke-width="1.5" stroke-linecap="round" />
                        <line x1="122" y1="24" x2="122" y2="14" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <circle cx="88" cy="30" r="11" fill="#2ECC71" />
                        <line x1="88" y1="19" x2="88" y2="11" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <circle cx="72" cy="36" r="10" fill="#F1C40F" />
                        <line x1="72" y1="26" x2="72" y2="18" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <circle cx="56" cy="28" r="9" fill="#E74C3C" />
                        <line x1="56" y1="19" x2="56" y2="12" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <rect x="78" y="58" width="16" height="14" rx="2.5" fill="#9B59B6" />
                        <rect x="78" y="58" width="16" height="4" rx="1.5" fill="#8E44AD" />
                    </svg>
                </div>
            </div>
        </div>
        @endif

        <div class="card dashboard-hero-card shadow-sm rounded-3 border-0 mb-3">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="min-w-0">
                        <p class="text-body-secondary mb-1 small">
                            {{ $greeting }},
                            <span class="text-primary fw-semibold">{{ $userName }}</span>
                        </p>
                        <h1 class="h3 fw-bold text-dark mb-0">Dashboard</h1>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2 gap-sm-3">
                        <i class="bi bi-clock text-primary lh-1" style="font-size: 1.85rem;" aria-hidden="true"></i>
                        <div class="lh-sm">
                            <div class="text-primary fw-bold tabular-nums lh-1"
                                style="font-size: 1.7rem; letter-spacing: -0.01em;">
                                <span id="dashboard-live-time">{{ now()->format('H:i') }}</span>
                            </div>
                            <p class="text-body-secondary mb-0 mt-1" style="font-size: 0.9rem;">
                                {{ now()->format('l, d F Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(($isMyBirthday ?? false) && ($myBirthdayWishCount ?? 0) > 0)
        <div class="card dashboard-panel shadow-sm rounded-4 mb-4" id="dashboard-birthday-wishes-panel">
            <div class="card-header bg-body py-3 px-4 d-flex align-items-center gap-2 border-bottom">
                <span
                    class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary p-2">
                    <i class="bi bi-balloon-heart-fill" aria-hidden="true"></i>
                </span>
                <h5 class="mb-0 fw-semibold">Birthday Wishes Received</h5>
                <span class="badge rounded-1 text-bg-primary">{{ $myBirthdayWishCount }}</span>
                <button type="button"
                    class="btn btn-sm btn-outline-primary rounded-pill ms-auto d-none d-md-inline-flex align-items-center gap-1"
                    data-bs-toggle="collapse" data-bs-target="#dashboard-birthday-wishes-collapse" aria-expanded="false"
                    aria-controls="dashboard-birthday-wishes-collapse" id="btn-toggle-birthday-wishes">
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    <span class="small">Show / Hide</span>
                </button>
            </div>
            <div class="collapse" id="dashboard-birthday-wishes-collapse">
                <div class="card-body p-3 p-md-4 dashboard-list-scroll">
                    @if($todayBirthdayWishNotifications->isEmpty())
                    <div class="dashboard-empty-state py-4">
                        <i class="bi bi-balloon-heart text-primary opacity-50 fs-1 d-block mb-2" aria-hidden="true"></i>
                        <p class="mb-0 small">Wishes received today will appear here.</p>
                    </div>
                    @else
                    <ul class="list-unstyled mb-0 ps-0">
                        @foreach($todayBirthdayWishNotifications as $wish)
                        <li class="mb-2">
                            @include('admin.dashboard.partials.wish-received-item', ['wish' => $wish, 'layout' =>
                            'dashboard'])
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </div>
        @endif

        
        <div class="dashboard-stats-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-3 mt-3">
            @foreach($cardsToRender as $card)
            <div class="col">
                @if($card['link'])
                <a href="{{ $card['link'] }}" class="text-decoration-none d-block h-100">
                @endif
                    <div class="card stat-card h-100 p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon-wrapper {{ $card['color_class'] }}">
                                <i class="material-symbols-rounded">{{ $card['icon'] }}</i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <p class="stat-title">{{ $card['label'] }}</p>
                                @php $v = (int) $card['count']; @endphp
                                <p class="stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</p>
                            </div>
                        </div>
                    </div>
                @if($card['link'])
                </a>
                @endif
            </div>
            @endforeach
        </div>

        <div class="row g-3 g-lg-4">
            <div class="col">
                @if(in_array('widget_notices', $enabledWidgetKeys))
                @php
                $noticeTabKeys = ['office-orders', 'work-allocation', 'notice-circular'];
                $noticeTabLabels = [
                'office-orders' => 'Office Orders',
                'work-allocation' => 'Work Allocation',
                'notice-circular' => 'Notice/ Circular/ Order',
                ];
                $noticeTabCounts = ['office-orders' => 0, 'work-allocation' => 0, 'notice-circular' => 0];
                $resolveDashboardNoticeTab = function ($type) {
                $t = strtolower((string) ($type ?? ''));
                if (str_contains($t, 'office order')) {
                return 'office-orders';
                }
                if (str_contains($t, 'course notice')) {
                return 'work-allocation';
                }
                return 'notice-circular';
                };
                foreach ($notices as $noticeForTab) {
                $tabKey = $resolveDashboardNoticeTab($noticeForTab->notice_type ?? '');
                $noticeTabCounts[$tabKey]++;
                }
                $defaultNoticeTab = 'office-orders';
                foreach ($noticeTabKeys as $tabKeyCandidate) {
                if ($noticeTabCounts[$tabKeyCandidate] > 0) {
                $defaultNoticeTab = $tabKeyCandidate;
                break;
                }
                }
                @endphp
                <div class="card dashboard-panel dashboard-feed-panel mb-3" id="dashboard-notices-panel">
                    <div class="card-header py-3 px-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 gap-md-3">
                            <h5 class="dashboard-feed-panel__title mb-0">Notices</h5>
                            @if(hasRole('Admin') || hasRole('Super Admin'))
                            <a href="{{ route('admin.notice.create') }}"
                                class="btn btn-sm dashboard-feed-btn-primary d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                <span>Add New Notice</span>
                            </a>
                            @endif
                        </div>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3 dashboard-list-scroll">
                        @if(count($notices) === 0)
                        <div class="dashboard-feed-empty">
                            <span class="dashboard-feed-empty__icon" aria-hidden="true">
                                <i class="bi bi-file-earmark-x"></i>
                            </span>
                            <p class="mb-3 text-body-secondary">No notices available.</p>
                            @if(hasRole('Admin') || hasRole('Super Admin'))
                            <a href="{{ route('admin.notice.create') }}"
                                class="btn dashboard-feed-btn-primary d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                <span>Add New Notice</span>
                            </a>
                            @endif
                        </div>
                        @else
                        <div class="dashboard-notice-tabs" role="tablist" aria-label="Notice categories">
                            @foreach($noticeTabKeys as $tabKey)
                            <button type="button"
                                class="dashboard-notice-tab {{ $tabKey === $defaultNoticeTab ? 'active' : '' }}{{ $noticeTabCounts[$tabKey] === 0 ? ' dashboard-notice-tab-empty' : '' }}"
                                role="tab" aria-selected="{{ $tabKey === $defaultNoticeTab ? 'true' : 'false' }}"
                                data-notice-tab="{{ $tabKey }}" id="dashboard-notice-tab-{{ $tabKey }}">
                                {{ $noticeTabLabels[$tabKey] }}@if($noticeTabCounts[$tabKey] > 0):
                                {{ $noticeTabCounts[$tabKey] }}@endif
                            </button>
                            @endforeach
                        </div>
                        <p class="dashboard-notice-list-empty d-none mb-0" id="dashboard-notice-tab-empty"
                            role="status">
                            No notices in this category.
                        </p>
                        <ul class="list-unstyled mb-0 ps-0" id="dashboard-notice-list">
                            @foreach($notices as $notice)
                            @php
                            $noticeTab = $resolveDashboardNoticeTab($notice->notice_type ?? '');
                            $noticeDate = $notice->created_at ?? $notice->display_date ?? null;
                            $isNewNotice = $noticeDate && \Carbon\Carbon::parse($noticeDate)->diffInDays(now()) < 7;
                                $displayFrom=!empty($notice->display_date)
                                ? \Carbon\Carbon::parse($notice->display_date)->format('j F, Y')
                                : null;
                                $displayTo = !empty($notice->expiry_date)
                                ? \Carbon\Carbon::parse($notice->expiry_date)->format('j F, Y')
                                : null;
                                if ($displayFrom && $displayTo) {
                                $noticeDateLabel = $displayFrom . ' to ' . $displayTo;
                                } elseif ($displayFrom) {
                                $noticeDateLabel = $displayFrom;
                                } elseif ($noticeDate) {
                                $noticeDateLabel = date('j F, Y', strtotime($noticeDate));
                                } else {
                                $noticeDateLabel = '—';
                                }
                                @endphp
                                <li class="mb-2 {{ $noticeTab !== $defaultNoticeTab ? 'd-none' : '' }}"
                                    data-notice-tab-item="{{ $noticeTab }}">
                                    <div
                                        class="dashboard-notice-item {{ $isNewNotice ? 'dashboard-notice-item-new' : '' }}">
                                        <span class="notice-icon-wrap" aria-hidden="true"><span
                                                class="material-icons material-symbols-rounded">description</span></span>
                                        <div class="min-w-0">
                                            <div
                                                class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                                <span class="dashboard-notice-title">{{ $notice->notice_title }}</span>
                                                @if($isNewNotice)
                                                <span
                                                    class="badge bg-danger dashboard-notice-new-tag flex-shrink-0">New</span>
                                                @endif
                                            </div>
                                            <small class="dashboard-notice-date">{{ $noticeDateLabel }}</small>
                                            @if($notice->document)
                                            <a href="{{ asset('storage/' . $notice->document) }}" target="_blank"
                                                class="dashboard-notice-attachment text-danger text-decoration-none">
                                                <i class="bi bi-paperclip" aria-hidden="true"></i>View attachment
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                        </ul>
                        <div class="dashboard-feed-footer">
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'notices']) }}"
                                class="dashboard-feed-see-all">See all</a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                @if(in_array('widget_admin_summary', $enabledWidgetKeys))
                <div class="card dashboard-panel dashboard-feed-panel mb-4" id="dashboard-notifications-panel">
                    <div class="card-header py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h5 class="dashboard-feed-panel__title mb-0 d-flex align-items-center gap-2">
                                @if($notificationBadgeCount > 0)
                                <i class="bi bi-bell-fill text-primary dashboard-panel-bell--ring"
                                    aria-hidden="true"></i>
                                @else
                                <i class="bi bi-bell text-primary opacity-75" aria-hidden="true"></i>
                                @endif
                                <span>{{ hasRole('Admin') || hasRole('Super Admin') ? 'Admin Summary' : 'Notifications' }}</span>
                            </h5>
                            <span class="dashboard-feed-count-badge"
                                aria-label="{{ $notificationBadgeCount }} items">{{ $notificationBadgeCount }}</span>
                        </div>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3 dashboard-list-scroll">
                        @if($notifications->isEmpty())
                        <div class="dashboard-feed-empty">
                            <span class="dashboard-feed-empty__icon" aria-hidden="true">
                                <i class="bi bi-bell-slash"></i>
                            </span>
                            <p class="mb-0 text-body-secondary small">No notifications available.</p>
                        </div>
                        @else
                        <ul class="list-unstyled mb-0 ps-0">
                            @foreach($notifications as $notification)
                            <li class="mb-0">
                                <button type="button"
                                    class="dashboard-notification-item {{ empty($notification->is_read) ? 'dashboard-notification-item-unread' : '' }}"
                                    data-notification-id="{{ $notification->pk }}">
                                    <span
                                        class="notification-icon-wrap dashboard-notification-bell {{ empty($notification->is_read) ? 'dashboard-notification-bell--ring' : '' }}"
                                        aria-hidden="true">
                                        <i class="bi bi-bell-fill"></i>
                                    </span>
                                    <div class="dashboard-notification-body">
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                            <span
                                                class="dashboard-notification-title">{{ $notification->title ?? 'Notification' }}</span>
                                            @if(empty($notification->is_read))
                                            <span class="badge bg-danger dashboard-notification-new-tag">New</span>
                                            @endif
                                        </div>
                                        <span
                                            class="dashboard-notification-time">{{ isset($notification->created_at) ? \Carbon\Carbon::parse($notification->created_at)->diffForHumans() : '—' }}</span>
                                        {{-- Full message, not Str::limit(): the CSS clamps it to two
                                             lines and hover/focus reveals the rest, so the text has to
                                             be in the DOM. Longest message in the data is ~180 chars,
                                             i.e. a line or two more when expanded. --}}
                                        <p class="dashboard-notification-message mb-0">
                                            {{ \App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? '') }}
                                        </p>
                                    </div>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                        <div class="dashboard-feed-footer">
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'notifications']) }}"
                                class="dashboard-feed-see-all">See all</a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                @if(in_array('widget_campus_tweets', $enabledWidgetKeys))
                @php
                $campusTweetCount = 3;
                @endphp
                <div class="card dashboard-panel dashboard-feed-panel mb-4" id="dashboard-campus-tweets-panel">
                    <div class="card-header py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h5 class="dashboard-feed-panel__title mb-0">Campus Tweets</h5>
                            <span class="dashboard-feed-count-badge"
                                aria-label="{{ $campusTweetCount }} items">{{ $campusTweetCount }}</span>
                        </div>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3">
                        <div class="dashboard-tweet-item">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> unread notices and total
                                <strong class="text-body">{{ count($notices) }}</strong> notices.</span>
                        </div>
                        <div class="dashboard-tweet-item">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> purchase orders for
                                approval.</span>
                        </div>
                        <div class="dashboard-tweet-item">
                            <span class="small text-body-secondary"><a href="#"
                                    class="link-primary text-decoration-none fw-medium">Click Here</a> for menu of
                                departmental canteen for next 2 weeks.</span>
                        </div>
                        <div class="dashboard-feed-footer">
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'notifications']) }}"
                                class="dashboard-feed-see-all">See all</a>
                        </div>
                    </div>
                </div>
                @endif

                @if(in_array('widget_todays_classes', $enabledWidgetKeys))
                <div class="card dashboard-panel dashboard-feed-panel mb-4" id="dashboard-todays-classes-panel">
                    <div class="card-header py-3 px-4">
                        <h5 class="dashboard-feed-panel__title mb-0">Today's Classes</h5>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3">
                        @if($todayTimetable && $todayTimetable->isNotEmpty())
                        <div class="dashboard-list-scroll pe-1">
                            @foreach($todayTimetable as $entry)
                            <div class="dashboard-class-card">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="dashboard-class-icon" aria-hidden="true">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                    <span class="fw-semibold text-primary">{{ $entry['session_date'] }} ·
                                        {{ $entry['session_time'] }}</span>
                                </div>
                                <div class="dashboard-class-topic">{{ $entry['topic'] }}</div>
                                <div class="dashboard-class-meta">
                                    <span><i class="bi bi-person me-1 opacity-75" aria-hidden="true"></i>Faculty:
                                        {{ $entry['faculty_name'] }}</span>
                                    <span><i class="bi bi-people me-1 opacity-75" aria-hidden="true"></i>Group:
                                        {{ $entry['group_name'] ?? 'N/A' }}</span>
                                    <span><i class="bi bi-geo-alt me-1 opacity-75" aria-hidden="true"></i>Venue:
                                        {{ $entry['session_venue'] }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="dashboard-feed-empty">
                            <span class="dashboard-feed-empty__icon" aria-hidden="true">
                                <i class="bi bi-calendar-x"></i>
                            </span>
                            <p class="mb-0 text-body-secondary small">No classes scheduled for today.</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>

            @if(in_array('widget_todays_birthdays', $enabledWidgetKeys) || in_array('widget_calendar', $enabledWidgetKeys))
            <div class="col-auto" style="width: 480px; min-width: 480px;">
                @if(in_array('widget_todays_birthdays', $enabledWidgetKeys))
                <div class="card dashboard-panel dashboard-birthdays-panel border-0 mb-4">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <h5 class="dashboard-birthdays-panel__title mb-0">Today's Birthdays 🎉</h5>
                            <span
                                class="dashboard-birthdays-count {{ $emp_dob_data->count() > 9 ? 'dashboard-birthdays-count--wide' : '' }}"
                                aria-label="{{ $emp_dob_data->count() }} birthdays today">{{ $emp_dob_data->count() }}</span>
                        </div>
                        <hr class="dashboard-birthdays-divider">
                    </div>
                    <div class="card-body dashboard-list-scroll">
                        @if($emp_dob_data->isEmpty())
                        <div class="dashboard-empty-state py-4">
                            <i class="bi bi-gift text-primary opacity-50 fs-1 d-block mb-2" aria-hidden="true"></i>
                            <p class="mb-0 small text-body-secondary">No birthdays today.</p>
                        </div>
                        @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($emp_dob_data as $employee)
                            @php
                            $avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning',
                            'text-bg-danger', 'text-bg-secondary'];
                            $avClass = $avClasses[$loop->index % count($avClasses)];
                            $photo = !empty($employee->profile_picture) ? asset('storage/' . $employee->profile_picture)
                            : null;
                            $email = trim((string)($employee->email ?? ''));
                            $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                            $wishCount = $birthdayWishCounts[$employee->pk] ?? 0;
                            $hasContact = $email !== '' || !empty($employee->mobile) ||
                            !empty($employee->office_extension_no);
                            @endphp
                            <article class="dashboard-birthday-item" @if($hasContact) tabindex="0" @endif>
                                <div class="dashboard-birthday-row">
                                    <x-dashboard-birthday-avatar :photo="$photo" :name="$fullName"
                                        :color-class="$avClass" />
                                    <div class="dashboard-birthday-info">
                                        <p class="dashboard-birthday-name text-truncate mb-0">{{ $fullName }}</p>
                                        <p class="dashboard-birthday-designation text-truncate mb-0">
                                            {{ $employee->designation_name }}</p>
                                        @if($wishCount > 0)
                                        <span
                                            class="badge rounded-1 bg-success-subtle text-success border border-success-subtle dashboard-birthday-badge mt-1"
                                            title="{{ $wishCount }} wishes sent">🎁 {{ $wishCount }}</span>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-sm dashboard-birthday-wish-btn btn-custom-wish"
                                        data-name="{{ $fullName }}" data-email="{{ $email }}"
                                        data-mobile="{{ $employee->mobile ?? '' }}" data-pk="{{ $employee->pk }}"
                                        title="Send birthday wish to {{ $fullName }}">Wish them</button>
                                </div>
                                @if($hasContact)
                                <div class="dashboard-birthday-detail">
                                    <div class="d-flex flex-wrap gap-2">
                                        @if(!empty($employee->mobile))
                                        <div class="dashboard-birthday-contact-pill">
                                            <i class="bi bi-telephone" aria-hidden="true"></i>
                                            <span>{{ $employee->mobile }}</span>
                                        </div>
                                        @endif
                                        @if($email !== '')
                                        <div class="dashboard-birthday-contact-pill">
                                            <i class="bi bi-envelope" aria-hidden="true"></i>
                                            <span>{{ $email }}</span>
                                        </div>
                                        @endif
                                        @if(!empty($employee->office_extension_no))
                                        <div class="dashboard-birthday-contact-pill">
                                            <i class="bi bi-telephone-outbound" aria-hidden="true"></i>
                                            <span>Ext {{ $employee->office_extension_no }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </article>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @if($emp_dob_data->isNotEmpty())
                    <div class="card-footer bg-white border-0">
                        <div class="dashboard-birthdays-footer w-100">
                            <span class="visually-hidden">More actions</span>
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'birthdays']) }}"
                                class="dashboard-birthdays-see-all">See all</a>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                @if(in_array('widget_calendar', $enabledWidgetKeys))
                <div class="card dashboard-panel dashboard-birthdays-panel--calendar border-0">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <h5 class="dashboard-birthdays-panel__title mb-0">Calendar</h5>
                            <span class="dashboard-calendar-date-badge mb-0">
                                {{ now()->format('d-m-Y') }}
                            </span>
                        </div>
                        <hr class="dashboard-birthdays-divider mb-0">
                    </div>
                    <div class="card-body">
                        <div id="dashboard-calendar-container" aria-live="polite">
                            <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()"
                                :events="$events" theme="gov-red" />
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Custom Birthday Wish Modal -->
    <div class="modal fade" id="customWishModal" tabindex="-1" aria-labelledby="customWishModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered dashboard-wish-modal-dialog">
            <div class="modal-content dashboard-wish-modal">
                <div class="modal-header dashboard-wish-modal__header">
                    <h5 class="modal-title dashboard-wish-modal__title mb-0" id="customWishModalLabel">
                        Wish on their birthday
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr class="dashboard-wish-modal__divider">
                <div class="modal-body dashboard-wish-modal__body">
                    <input type="hidden" id="wish-recipient-email">
                    <input type="hidden" id="wish-recipient-mobile">
                    <input type="hidden" id="wish-modal-mode" value="birthday">

                    <p class="dashboard-wish-intro mb-0" id="wish-modal-intro-birthday">
                        Wish
                        <input type="text" class="dashboard-wish-name-inline" id="wish-recipient-name" readonly
                            aria-label="Recipient name" size="16">
                        on the occasion of their birthday.
                    </p>
                    <p class="dashboard-wish-intro mb-0 d-none" id="wish-modal-intro-reply">
                        Your reply to
                        <input type="text" class="dashboard-wish-name-inline" id="wish-reply-name-inline" readonly
                            aria-label="Recipient name" size="16">
                        for their birthday wish.
                    </p>

                    <div class="dashboard-wish-options row g-3 mt-3" id="wish-modal-extra">
                        <div class="col-sm-6">
                            <label class="form-label" for="wish-template-select">Message template</label>
                            <select class="form-select" id="wish-template-select">
                                <option value="formal">Formal Birthday Wish</option>
                                <option value="casual">Casual Birthday Wish</option>
                                <option value="professional">Professional Birthday Wish</option>
                                <option value="custom">Write Custom Message</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="wish-subject">
                                Email subject
                                <span class="fw-normal text-body-secondary">(for email)</span>
                            </label>
                            <input type="text" class="form-control" id="wish-subject" value="Happy Birthday!">
                        </div>
                    </div>

                    <div class="mt-4" id="wish-modal-message-wrap">
                        <label class="form-label dashboard-wish-message-label d-block" for="wish-message"
                            id="wish-message-label">Your message</label>
                        <textarea class="form-control dashboard-wish-textarea" id="wish-message" rows="7"
                            placeholder="Write your birthday wish here…"></textarea>
                    </div>

                    <div class="d-flex flex-wrap align-items-center dashboard-wish-channels mt-4 opacity-50 pe-none"
                        id="wish-modal-channels" aria-hidden="true" title="Temporarily unavailable">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="send-via-whatsapp" disabled>
                            <label class="form-check-label text-body-secondary" for="send-via-whatsapp">
                                Via WhatsApp
                            </label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="send-via-email" disabled>
                            <label class="form-check-label text-body-secondary" for="send-via-email">
                                Via Email
                            </label>
                        </div>
                    </div>
                    <p class="small text-body-secondary mb-0 mt-2" id="wish-modal-hint">
                        <i class="bi bi-bell me-1" aria-hidden="true"></i>Send delivers an in-app notification with your
                        message.
                    </p>
                </div>
                <div class="modal-footer dashboard-wish-modal__footer d-flex justify-content-end border-0">
                    <button type="button" class="btn dashboard-wish-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn dashboard-wish-btn-send" id="btn-send-wish">Send</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Birthday wish modal logic
    (function() {
        const templates = {
            formal: function(name) {
                return "Dear " + name +
                    ",\n\nOn the occasion of your birthday, I extend my heartfelt wishes for a wonderful year ahead. May this special day bring you joy, success, and good health.\n\nWarm regards,";
            },
            casual: function(name) {
                return "Hey " + name +
                    "! 🎂🎉\n\nWishing you a fantastic birthday! Hope your day is filled with joy, laughter, and all things wonderful. Have an amazing year ahead!\n\nCheers!";
            },
            professional: function(name) {
                return "Dear " + name +
                    ",\n\nWishing you a very Happy Birthday! May this new year of your life bring you continued success and fulfilment in all your endeavours.\n\nBest wishes,";
            },
            custom: function(name) {
                return "Dear " + name + ",\n\n";
            }
        };

        var replyTemplate = function(name) {
            return "Dear " + name +
                ",\n\nThank you so much for your lovely birthday wishes! I truly appreciate your thoughtfulness.\n\nWarm regards,";
        };

        var currentRecipient = {
            mode: 'birthday'
        };

        function setNameFieldSize(input, name) {
            if (!input) return;
            input.value = name || '';
            input.size = Math.max(4, Math.min(28, (name || '').length + 1));
        }

        function setWishModalMode(mode, name) {
            var isReply = mode === 'reply';
            currentRecipient.mode = mode;
            var modeInput = document.getElementById('wish-modal-mode');
            if (modeInput) modeInput.value = mode;
            document.getElementById('customWishModalLabel').textContent = isReply ? 'Reply to birthday wish' :
                'Wish on their birthday';
            var introBirthday = document.getElementById('wish-modal-intro-birthday');
            var introReply = document.getElementById('wish-modal-intro-reply');
            if (introBirthday) introBirthday.classList.toggle('d-none', isReply);
            if (introReply) introReply.classList.toggle('d-none', !isReply);
            var extra = document.getElementById('wish-modal-extra');
            var channels = document.getElementById('wish-modal-channels');
            if (extra) extra.classList.toggle('d-none', isReply);
            if (channels) channels.classList.toggle('d-none', isReply);
            var messageLabel = document.getElementById('wish-message-label');
            var messageField = document.getElementById('wish-message');
            if (messageLabel) messageLabel.textContent = isReply ? 'Your reply' : 'Your message';
            if (messageField) {
                messageField.placeholder = isReply ? 'Write your thank-you reply…' :
                    'Write your birthday wish here…';
            }
            setNameFieldSize(document.getElementById('wish-recipient-name'), name);
            setNameFieldSize(document.getElementById('wish-reply-name-inline'), name);
        }

        function openWishModal(recipient, mode) {
            currentRecipient = Object.assign({}, recipient, {
                mode: mode
            });
            setWishModalMode(mode, currentRecipient.name);
            document.getElementById('wish-recipient-email').value = currentRecipient.email || '';
            document.getElementById('wish-recipient-mobile').value = currentRecipient.mobile || '';
            if (mode === 'reply') {
                document.getElementById('wish-message').value = replyTemplate(currentRecipient.name || '');
                document.getElementById('wish-subject').value = 'Thank you for the birthday wishes!';
            } else {
                document.getElementById('wish-template-select').value = 'formal';
                document.getElementById('wish-subject').value = 'Happy Birthday ' + (currentRecipient.name || '') +
                    '!';
                document.getElementById('wish-message').value = templates.formal(currentRecipient.name || '');
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('customWishModal')).show();
        }

        document.addEventListener('click', function(e) {
            var replyBtn = e.target.closest('.btn-wish-reply');
            if (replyBtn) {
                e.preventDefault();
                e.stopPropagation();
                openWishModal({
                    name: replyBtn.dataset.name || '',
                    email: replyBtn.dataset.email || '',
                    mobile: replyBtn.dataset.mobile || '',
                    employee_pk: replyBtn.dataset.pk || ''
                }, 'reply');
                return;
            }
            var btn = e.target.closest('.btn-custom-wish');
            if (!btn) return;
            openWishModal({
                name: btn.dataset.name || '',
                email: btn.dataset.email || '',
                mobile: btn.dataset.mobile || '',
                employee_pk: btn.dataset.pk || ''
            }, 'birthday');
        });

        var templateSelect = document.getElementById('wish-template-select');
        if (templateSelect) {
            templateSelect.addEventListener('change', function() {
                if (currentRecipient.mode === 'reply') return;
                var name = currentRecipient.name || '';
                var tpl = templates[this.value] || templates.custom;
                document.getElementById('wish-message').value = tpl(name);
            });
        }

        var sendBtn = document.getElementById('btn-send-wish');
        if (sendBtn) {
            sendBtn.addEventListener('click', function() {
                var message = document.getElementById('wish-message').value.trim();
                var subject = document.getElementById('wish-subject').value.trim();
                var isReply = currentRecipient.mode === 'reply';

                if (!message) {
                    alert('Please enter a message.');
                    return;
                }
                if (!currentRecipient.employee_pk) {
                    alert('Could not identify the recipient. Please try again.');
                    return;
                }

                var defaultTitle = isReply ?
                    'Thank you for the birthday wishes!' :
                    ('Happy Birthday ' + (currentRecipient.name || '') + '!');

                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                    'content') ||
                    '{{ csrf_token() }}';
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

                fetch('{{ route("admin.birthday-wish.send-notification") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            employee_pks: [parseInt(currentRecipient.employee_pk, 10)],
                            message: message,
                            title: subject || defaultTitle
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('customWishModal'))
                                .hide();
                            showToast(data.message || (isReply ? 'Reply sent!' :
                                'Birthday wish notification sent!'), 'success');
                        } else {
                            alert('Failed to send notification: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(function(err) {
                        alert('Error sending notification: ' + (err.message || 'Unknown error'));
                    })
                    .finally(function() {
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = 'Send';
                    });
            });
        }

        function openWhatsApp(mobile, message) {
            var phone = mobile.replace(/[^0-9]/g, '');
            if (phone.length === 10) phone = '91' + phone;
            var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
            window.open(url, '_blank');
        }

        function showToast(msg, type) {
            var toastHtml = '<div class="toast align-items-center text-bg-' + (type || 'primary') +
                ' border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
                '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
            var div = document.createElement('div');
            div.innerHTML = toastHtml;
            document.body.appendChild(div);
            setTimeout(function() {
                div.remove();
            }, 4000);
        }
    })();

    window.markAsReadDashboard = function(notificationId, clickedElement) {
        if (clickedElement && clickedElement.dataset.processing === 'true') {
            return;
        }
        if (clickedElement) {
            clickedElement.dataset.processing = 'true';
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            '{{ csrf_token() }}';

        fetch('/admin/notifications/mark-read-redirect/' + notificationId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json().then(data => ({
                ok: response.ok,
                data
            })))
            .then(({
                ok,
                data
            }) => {
                if (!ok) {
                    throw new Error(data.error || 'Failed to mark notification as read');
                }
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
                if (data.success) {
                    location.reload();
                    return;
                }
                throw new Error(data.error || 'Unknown error occurred');
            })
            .catch(error => {
                if (clickedElement) {
                    clickedElement.dataset.processing = 'false';
                }
                alert('An error occurred: ' + (error.message || 'Unknown error'));
            });
    };

    window.markAsRead = window.markAsReadDashboard;

    // Use event delegation to avoid inline onclick (also helps JS linters in Blade).
    document.addEventListener('click', function(e) {
        const btn = e.target && e.target.closest ? e.target.closest(
            '.dashboard-notification-item[data-notification-id]') : null;
        if (!btn) return;
        const id = btn.dataset.notificationId;
        if (!id) return;
        window.markAsReadDashboard(id, btn);
    });

    /* ===== Notification hover peek =====
       Hovering a row floats the full notification beside the list. The card is
       appended to <body> and positioned with fixed coordinates because the panel is
       an overflow:auto scroller, which clips anything absolutely positioned inside
       it. Text is written with textContent — notification bodies are user data. */
    (function() {
        var peek = null;
        var current = null;
        var GAP = 12;

        function build() {
            if (peek) return peek;
            peek = document.createElement('div');
            peek.className = 'dashboard-notification-peek';
            peek.setAttribute('role', 'tooltip');
            peek.setAttribute('aria-hidden', 'true');
            peek.innerHTML =
                '<span class="dashboard-notification-peek__title"></span>' +
                '<span class="dashboard-notification-peek__meta">' +
                '<span class="dashboard-notification-peek__time"></span>' +
                '</span>' +
                '<p class="dashboard-notification-peek__message"></p>';
            document.body.appendChild(peek);
            return peek;
        }

        function place(item) {
            var r = item.getBoundingClientRect();
            var w = peek.offsetWidth;
            var h = peek.offsetHeight;

            // Prefer the right of the row; flip left when it would run off-screen.
            var toLeft = r.right + GAP + w > window.innerWidth - 8;
            var left = toLeft ? r.left - GAP - w : r.right + GAP;
            left = Math.max(8, Math.min(left, window.innerWidth - w - 8));

            var top = Math.max(8, Math.min(r.top, window.innerHeight - h - 8));

            peek.classList.toggle('dashboard-notification-peek--left', toLeft);
            peek.classList.toggle('dashboard-notification-peek--right', !toLeft);
            peek.style.left = left + 'px';
            peek.style.top = top + 'px';

            // Keep the caret pointing at the row even when the card is clamped.
            var caret = Math.max(10, Math.min(r.top + r.height / 2 - top, h - 18));
            peek.style.setProperty('--peek-caret-top', caret + 'px');
        }

        function show(item) {
            var message = item.querySelector('.dashboard-notification-message');
            var text = message ? message.textContent.trim() : '';
            if (!text) return; // nothing more to reveal than the row already shows

            build();
            current = item;

            var title = item.querySelector('.dashboard-notification-title');
            var time = item.querySelector('.dashboard-notification-time');
            peek.querySelector('.dashboard-notification-peek__title').textContent =
                title ? title.textContent.trim() : 'Notification';
            peek.querySelector('.dashboard-notification-peek__time').textContent =
                time ? time.textContent.trim() : '';
            peek.querySelector('.dashboard-notification-peek__message').textContent = text;

            var meta = peek.querySelector('.dashboard-notification-peek__meta');
            var existingTag = meta.querySelector('.dashboard-notification-peek__new');
            if (existingTag) existingTag.remove();
            if (item.classList.contains('dashboard-notification-item-unread')) {
                var tag = document.createElement('span');
                tag.className = 'dashboard-notification-peek__new';
                tag.textContent = 'New';
                meta.appendChild(tag);
            }

            place(item);
            peek.classList.add('is-visible');
            peek.setAttribute('aria-hidden', 'false');
        }

        function hide() {
            current = null;
            if (!peek) return;
            peek.classList.remove('is-visible');
            peek.setAttribute('aria-hidden', 'true');
        }

        function itemFrom(e) {
            return e.target && e.target.closest ?
                e.target.closest('.dashboard-notification-item') : null;
        }

        document.addEventListener('mouseover', function(e) {
            var item = itemFrom(e);
            if (item && item !== current) show(item);
        });

        document.addEventListener('mouseout', function(e) {
            var item = itemFrom(e);
            if (!item) return;
            // Ignore moves between children of the same row.
            if (e.relatedTarget && item.contains(e.relatedTarget)) return;
            hide();
        });

        // Keyboard parity: the row is a real button, so Tab must peek too.
        document.addEventListener('focusin', function(e) {
            var item = itemFrom(e);
            if (item) show(item);
        });
        document.addEventListener('focusout', function(e) {
            if (itemFrom(e)) hide();
        });

        // The list scrolls under the card, so re-anchor (or drop) it as things move.
        window.addEventListener('scroll', function() {
            if (current) place(current);
        }, true);
        window.addEventListener('resize', hide);
    })();

    document.addEventListener('click', function(e) {
        const tabBtn = e.target && e.target.closest ? e.target.closest(
            '.dashboard-notice-tab[data-notice-tab]') :
            null;
        if (!tabBtn) return;

        const activeTab = tabBtn.dataset.noticeTab;
        if (!activeTab) return;

        document.querySelectorAll('.dashboard-notice-tab[data-notice-tab]').forEach(function(button) {
            const isActive = button.dataset.noticeTab === activeTab;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        let visibleCount = 0;
        document.querySelectorAll('[data-notice-tab-item]').forEach(function(item) {
            const show = item.dataset.noticeTabItem === activeTab;
            item.classList.toggle('d-none', !show);
            if (show) {
                visibleCount++;
            }
        });

        const emptyState = document.getElementById('dashboard-notice-tab-empty');
        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount > 0);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const liveTimeEl = document.getElementById('dashboard-live-time');
        if (liveTimeEl) {
            const formatLiveTime = function(date) {
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return hours + ':' + minutes;
            };

            const updateLiveTime = function() {
                liveTimeEl.textContent = formatLiveTime(new Date());
            };

            updateLiveTime();
            setInterval(updateLiveTime, 1000);
        }

        const calendarContainer = document.getElementById('dashboard-calendar-container');

        function loadDashboardCalendar(year, month) {
            if (!calendarContainer) return;

            const url = new URL("{{ route('admin.dashboard') }}", window.location.origin);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);
            url.searchParams.set('calendar_only', '1');

            calendarContainer.style.opacity = '0.6';
            calendarContainer.style.pointerEvents = 'none';

            fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(function(response) {
                    return response.json().then(function(data) {
                        return {
                            ok: response.ok,
                            data: data
                        };
                    });
                })
                .then(function(result) {
                    if (!result.ok || !result.data || !result.data.html) {
                        throw new Error('Failed to load calendar');
                    }

                    calendarContainer.innerHTML = result.data.html;
                    const refreshedComponent = calendarContainer.querySelector('.calendar-component');
                    if (refreshedComponent) {
                        bindCalendarComponent(refreshedComponent);
                    }
                })
                .catch(function(error) {
                    console.error(error);
                })
                .finally(function() {
                    calendarContainer.style.opacity = '1';
                    calendarContainer.style.pointerEvents = 'auto';
                });
        }

        function bindCalendarComponent(comp) {
            if (!comp || comp.dataset.bound === 'true') return;
            comp.dataset.bound = 'true';

            const yearSel = comp.querySelector('.calendar-year');
            const monthSel = comp.querySelector('.calendar-month');
            const monthLabel = comp.querySelector('.calendar-month-year-label');
            const cells = comp.querySelectorAll('.calendar-cell:not(.calendar-day-other)');

            function updateMonthLabel() {
                if (!monthLabel || !yearSel || !monthSel) return;
                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov',
                    'Dec'
                ];
                const monthIndex = parseInt(monthSel.value, 10) - 1;
                monthLabel.textContent = (monthNames[monthIndex] || '') + ' ' + yearSel.value;
            }

            function shiftMonth(delta) {
                if (!yearSel || !monthSel) return;
                let month = parseInt(monthSel.value, 10) + delta;
                let year = parseInt(yearSel.value, 10);
                while (month < 1) {
                    month += 12;
                    year -= 1;
                }
                while (month > 12) {
                    month -= 12;
                    year += 1;
                }
                monthSel.value = String(month);
                yearSel.value = String(year);
                loadDashboardCalendar(year, month);
            }

            function shiftYear(delta) {
                if (!yearSel || !monthSel) return;
                const year = parseInt(yearSel.value, 10) + delta;
                yearSel.value = String(year);
                loadDashboardCalendar(year, monthSel.value);
            }

            comp.addEventListener('click', function(e) {
                if (e.target.closest('.calendar-nav-year-prev')) {
                    e.preventDefault();
                    shiftYear(-1);
                    return;
                }
                if (e.target.closest('.calendar-nav-year-next')) {
                    e.preventDefault();
                    shiftYear(1);
                    return;
                }
                if (e.target.closest('.calendar-nav-month-prev')) {
                    e.preventDefault();
                    shiftMonth(-1);
                    return;
                }
                if (e.target.closest('.calendar-nav-month-next')) {
                    e.preventDefault();
                    shiftMonth(1);
                    return;
                }

                const td = e.target.closest('.calendar-cell:not(.calendar-day-other)');
                if (!td || !td.dataset.date) return;
                const prev = comp.querySelector('.calendar-cell.is-selected');
                if (prev) {
                    prev.classList.remove('is-selected');
                    prev.setAttribute('aria-pressed', 'false');
                }
                td.classList.add('is-selected');
                td.setAttribute('aria-pressed', 'true');
                comp.dispatchEvent(new CustomEvent('dateSelected', {
                    detail: {
                        date: td.dataset.date
                    }
                }));
            });

            cells.forEach(function(cell) {
                cell.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });

                cell.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        cell.click();
                    }
                    const selectable = comp.querySelectorAll(
                        '.calendar-cell:not(.calendar-day-other)');
                    const idx = Array.prototype.indexOf.call(selectable, cell);
                    let targetIdx = null;
                    if (ev.key === 'ArrowLeft') targetIdx = idx - 1;
                    if (ev.key === 'ArrowRight') targetIdx = idx + 1;
                    if (ev.key === 'ArrowUp') targetIdx = idx - 7;
                    if (ev.key === 'ArrowDown') targetIdx = idx + 7;
                    if (targetIdx !== null && selectable[targetIdx]) {
                        selectable[targetIdx].focus();
                        ev.preventDefault();
                    }
                });
            });

            if (yearSel && monthSel) {
                yearSel.addEventListener('change', function() {
                    loadDashboardCalendar(this.value, monthSel.value);
                });

                monthSel.addEventListener('change', function() {
                    loadDashboardCalendar(yearSel.value, this.value);
                });
            }

            updateMonthLabel();

            const holidaysToggle = comp.querySelector('.calendar-holidays-toggle');
            const holidaysPanel = comp.querySelector('.calendar-holidays-panel');
            if (holidaysToggle && holidaysPanel) {
                holidaysToggle.addEventListener('click', function() {
                    const isOpen = !holidaysPanel.hidden;
                    holidaysPanel.hidden = isOpen;
                    holidaysToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                    holidaysToggle.textContent = isOpen ? 'Show holidays this month' :
                        'Hide holidays this month';
                });
            }

            const filterButtons = comp.querySelectorAll('.calendar-holiday-filter');
            const holidayItems = comp.querySelectorAll('.calendar-holiday-list__item');

            function applyHolidayFilter(type) {
                filterButtons.forEach(function(btn) {
                    const active = btn.dataset.filter === type;
                    btn.classList.toggle('active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                holidayItems.forEach(function(item) {
                    item.classList.toggle('is-hidden', item.dataset.holidayType !== type);
                });
            }

            filterButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    applyHolidayFilter(btn.dataset.filter || 'gazetted');
                });
            });

            if (filterButtons.length && holidayItems.length) {
                applyHolidayFilter('gazetted');
            }
        }

        document.querySelectorAll('.calendar-component').forEach(function(comp) {
            bindCalendarComponent(comp);
        });
    });

    // ── View birthday wishes panel (scroll + expand) ──
    (function() {
        var viewLink = document.getElementById('btn-view-birthday-wishes');
        var panel = document.getElementById('dashboard-birthday-wishes-panel');
        var collapseEl = document.getElementById('dashboard-birthday-wishes-collapse');
        var toggleBtn = document.getElementById('btn-toggle-birthday-wishes');
        if (!panel || !collapseEl) return;

        function expandWishesPanel() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                var instance = bootstrap.Collapse.getOrCreateInstance(collapseEl, {
                    toggle: false
                });
                instance.show();
            } else {
                collapseEl.classList.add('show');
            }
            panel.classList.add('is-expanded');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', 'true');
            }
        }

        function scrollToWishesPanel() {
            window.setTimeout(function() {
                panel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 200);
        }

        if (viewLink && viewLink.getAttribute('href') === '#') {
            viewLink.addEventListener('click', function(e) {
                e.preventDefault();
                expandWishesPanel();
                scrollToWishesPanel();
                if (history.replaceState) {
                    history.replaceState(null, '', '#dashboard-birthday-wishes-panel');
                } else {
                    window.location.hash = 'dashboard-birthday-wishes-panel';
                }
            });
        }

        if (window.location.hash === '#dashboard-birthday-wishes-panel') {
            expandWishesPanel();
            scrollToWishesPanel();
        }

        collapseEl.addEventListener('shown.bs.collapse', function() {
            panel.classList.add('is-expanded');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        });
        collapseEl.addEventListener('hidden.bs.collapse', function() {
            panel.classList.remove('is-expanded');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        });
    })();

    // ── Birthday banner dismiss (session only) ──
    (function() {
        var dismissBtn = document.getElementById('birthday-banner-dismiss');
        var banner = document.getElementById('birthday-banner');
        if (!banner) return;
        if (sessionStorage.getItem('dashboardBirthdayBannerDismissed') === '1') {
            banner.classList.add('is-dismissed');
            return;
        }
        if (!dismissBtn) return;
        dismissBtn.addEventListener('click', function() {
            banner.classList.add('is-dismissed');
            sessionStorage.setItem('dashboardBirthdayBannerDismissed', '1');
        });
    })();

    // ── Confetti Effect for Birthday Banner ──
    (function() {
        var canvas = document.getElementById('confetti-canvas');
        var banner = document.getElementById('birthday-banner');
        if (!canvas || !banner || banner.classList.contains('is-dismissed')) return;
        var ctx = canvas.getContext('2d');
        var W, H, particles = [],
            colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#4caf50',
                '#ffeb3b', '#ff9800', '#ff5722', '#fff'
            ];

        function resize() {
            W = canvas.width = canvas.parentElement.offsetWidth;
            H = canvas.height = canvas.parentElement.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        for (var i = 0; i < 80; i++) {
            particles.push({
                x: Math.random() * W,
                y: Math.random() * H - H,
                r: Math.random() * 5 + 2,
                d: Math.random() * 80,
                color: colors[Math.floor(Math.random() * colors.length)],
                tilt: Math.random() * 10 - 5,
                tiltAngle: 0,
                tiltAngleInc: Math.random() * 0.07 + 0.05
            });
        }

        var animFrame;

        function draw() {
            ctx.clearRect(0, 0, W, H);
            particles.forEach(function(p) {
                ctx.beginPath();
                ctx.lineWidth = p.r;
                ctx.strokeStyle = p.color;
                ctx.moveTo(p.x + p.tilt + p.r / 2, p.y);
                ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r / 2);
                ctx.stroke();
            });
            update();
            animFrame = requestAnimationFrame(draw);
        }

        function update() {
            particles.forEach(function(p) {
                p.tiltAngle += p.tiltAngleInc;
                p.y += (Math.cos(p.d) + 1 + p.r / 2) * 0.6;
                p.x += Math.sin(p.d) * 0.5;
                p.tilt = Math.sin(p.tiltAngle) * 12;
                if (p.y > H) {
                    p.y = -10;
                    p.x = Math.random() * W;
                }
            });
        }

        draw();
        // Stop confetti after 8 seconds
        setTimeout(function() {
            cancelAnimationFrame(animFrame);
            if (ctx) ctx.clearRect(0, 0, W, H);
        }, 8000);
    })();

    // ── Quick Wish All Button ──
    (function() {
        var btn = document.getElementById('btn-quick-wish-all');
        if (!btn) return;

        btn.addEventListener('click', function() {
            if (!confirm('Send birthday wishes (email + notification) to all birthday people today?'))
                return;

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                '';
            var allCards = document.querySelectorAll('.btn-custom-wish');
            var recipients = [];
            allCards.forEach(function(card) {
                var name = card.dataset.name || '';
                var email = card.dataset.email || '';
                var pk = card.dataset.pk || '';
                if (email && pk) {
                    recipients.push({
                        email: email,
                        name: name,
                        employee_pk: parseInt(pk)
                    });
                }
            });

            if (recipients.length === 0) {
                alert('No recipients with email found.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

            fetch('{{ route("admin.birthday-wish.send-bulk-email") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        recipients: recipients,
                        subject: 'Happy Birthday!',
                        message_template: "Dear {name},\n\nWishing you a very Happy Birthday! May this special day bring you joy, success, and good health.\n\nWarm regards,\n{{ $userName ?? 'Team' }}"
                    })
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.success) {
                        var div = document.createElement('div');
                        div.innerHTML =
                            '<div class="toast align-items-center text-bg-success border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
                            '<div class="d-flex"><div class="toast-body">🎉 ' + data.message +
                            '</div>' +
                            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
                        document.body.appendChild(div);
                        setTimeout(function() {
                            div.remove();
                        }, 5000);
                    } else {
                        alert('Error: ' + (data.error || 'Unknown'));
                    }
                })
                .catch(function(err) {
                    alert('Error: ' + err.message);
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML =
                        '<i class="bi bi-stars" aria-hidden="true"></i><span class="small">Wish All</span>';
                });
        });
    })();
    </script>
    @endpush
    @endsection