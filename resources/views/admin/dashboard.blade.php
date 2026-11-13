@extends('admin.layouts.master')

@section('title', 'Dashboard')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-calendar.css') }}?v=4">
<link rel="stylesheet" href="{{ asset('css/dashboard-stat-cards.css') }}?v={{ @filemtime(public_path('css/dashboard-stat-cards.css')) ?: time() }}">
{{-- filemtime, not a hand-tracked ?v= : this file's manual token has already regressed once
     (v9 -> v7 through a revert), which serves returning users a stale stylesheet. --}}
<link rel="stylesheet" href="{{ asset('css/dashboard-main.css') }}?v={{ @filemtime(public_path('css/dashboard-main.css')) ?: time() }}">

@php
$user = Auth::user();
$isAdminSummary = hasRole('Super Admin');
$daysOld = $isAdminSummary ? 10 : null;
$notifications = ($user && $user->user_id) ? notification()->getNotifications($user->user_id, 10, false, $daysOld) :
collect();
$notificationBadgeCount = ($user && $user->user_id)
? ($isAdminSummary ? notification()->getUnreadCount($user->user_id, $daysOld) : $notifications->count())
: 0;
// Summary widget: only the latest few notices are rendered — "See all" links to
// the paginated feed. Fetching every live notice here just to show a short list
// was the same unbounded read the feed page had.
$noticeWidgetLimit = 5;
$noticeFeedQuery = notice_feed_query_by_role();
$noticeTotalCount = $noticeFeedQuery ? (clone $noticeFeedQuery)->count() : 0;
$notices = $noticeFeedQuery ? $noticeFeedQuery->limit($noticeWidgetLimit)->get() : collect();
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
        {{-- Birthday banner — design-system card (.ds-card + .ds-card--accent, docs/design.md) --}}
        <div class="ds-card ds-card--accent dashboard-birthday-banner mb-3 position-relative"
            id="birthday-banner" role="region" aria-label="Birthday greeting">
            <canvas id="confetti-canvas" aria-hidden="true"></canvas>
            <button type="button" class="birthday-banner-close" id="birthday-banner-dismiss"
                aria-label="Dismiss birthday message">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
            <div class="birthday-banner-inner">
                <span class="birthday-banner-icon" aria-hidden="true"><i class="bi bi-gift-fill"></i></span>
                <div class="birthday-banner-text">
                    <p class="birthday-banner-eyebrow mb-0">
                        <i class="bi bi-stars" aria-hidden="true"></i> Today is your special day
                    </p>
                    <h2 class="birthday-banner-title">
                        Happy Birthday, <span class="birthday-banner-name">{{ $userName }}</span>!
                    </h2>
                    <p class="birthday-banner-subtitle mb-0">
                        Wishing you a wonderful year ahead, filled with good health and happiness.
                        @if(!empty($myBirthdayWishesSummary))
                        {{ ' ' . $myBirthdayWishesSummary }}
                        @endif
                    </p>
                    @if(($myBirthdayWishCount ?? 0) > 0)
                    <div class="birthday-banner-actions">
                        <span class="birthday-banner-count">
                            <i class="bi bi-envelope-heart-fill" aria-hidden="true"></i>
                            {{ $myBirthdayWishCount }} {{ \Illuminate\Support\Str::plural('wish', (int) $myBirthdayWishCount) }} received
                        </span>
                        <a href="{{ route('admin.dashboard.feed', ['tab' => 'wishes']) }}" class="birthday-banner-link"
                            id="btn-view-birthday-wishes">View all wishes <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    @endif
                </div>

                {{-- Animated cake: candle flames flicker, stars twinkle, sparkles drift up (CSS only). --}}
                <div class="birthday-banner-illustration d-none d-sm-block" aria-hidden="true">
                    <svg class="bday-cake" viewBox="0 0 160 124" xmlns="http://www.w3.org/2000/svg" focusable="false">
                        <circle class="bday-cake__halo" cx="80" cy="66" r="54" />

                        <path class="bday-cake__spark bday-cake__spark--1" d="M24 26 L26 32 L32 34 L26 36 L24 42 L22 36 L16 34 L22 32 Z" />
                        <path class="bday-cake__spark bday-cake__spark--2" d="M138 21 L139.75 26.25 L145 28 L139.75 29.75 L138 35 L136.25 29.75 L131 28 L136.25 26.25 Z" />
                        <path class="bday-cake__spark bday-cake__spark--3" d="M146 73 L147.25 76.75 L151 78 L147.25 79.25 L146 83 L144.75 79.25 L141 78 L144.75 76.75 Z" />
                        <path class="bday-cake__spark bday-cake__spark--4" d="M14 77 L15.25 80.75 L19 82 L15.25 83.25 L14 87 L12.75 83.25 L9 82 L12.75 80.75 Z" />
                        <path class="bday-cake__spark bday-cake__spark--5" d="M112 4 L113 7 L116 8 L113 9 L112 12 L111 9 L108 8 L111 7 Z" />

                        <circle class="bday-cake__dot bday-cake__dot--1" cx="40" cy="100" r="2.5" />
                        <circle class="bday-cake__dot bday-cake__dot--2" cx="122" cy="104" r="2" />
                        <circle class="bday-cake__dot bday-cake__dot--3" cx="30" cy="64" r="2" />
                        <circle class="bday-cake__dot bday-cake__dot--4" cx="132" cy="58" r="2.5" />

                        <ellipse cx="80" cy="114" rx="60" ry="6" fill="#e5e7eb" />

                        {{-- Bottom tier --}}
                        <rect x="28" y="78" width="104" height="34" rx="6" fill="#004a93" />
                        <rect x="28" y="98" width="104" height="4" fill="#f5b301" />
                        <g fill="#ffffff" opacity="0.85">
                            <circle cx="40" cy="90" r="2.2" /><circle cx="56" cy="90" r="2.2" /><circle cx="72" cy="90" r="2.2" />
                            <circle cx="88" cy="90" r="2.2" /><circle cx="104" cy="90" r="2.2" /><circle cx="120" cy="90" r="2.2" />
                        </g>
                        <g fill="#ffffff">
                            <rect x="28" y="76" width="104" height="8" rx="4" />
                            <rect x="36" y="80" width="6" height="10" rx="3" /><rect x="56" y="80" width="6" height="7" rx="3" />
                            <rect x="78" y="80" width="6" height="11" rx="3" /><rect x="100" y="80" width="6" height="8" rx="3" />
                            <rect x="120" y="80" width="6" height="9" rx="3" />
                        </g>

                        {{-- Top tier --}}
                        <rect x="44" y="50" width="72" height="30" rx="6" fill="#b12923" />
                        <g fill="#ffd666">
                            <circle cx="56" cy="68" r="2" /><circle cx="72" cy="68" r="2" /><circle cx="88" cy="68" r="2" /><circle cx="104" cy="68" r="2" />
                        </g>
                        <g fill="#ffffff">
                            <rect x="44" y="48" width="72" height="8" rx="4" />
                            <rect x="52" y="52" width="6" height="8" rx="3" /><rect x="72" y="52" width="6" height="10" rx="3" />
                            <rect x="94" y="52" width="6" height="7" rx="3" /><rect x="106" y="52" width="6" height="9" rx="3" />
                        </g>

                        {{-- Candles --}}
                        <g>
                            <rect x="62" y="26" width="6" height="22" rx="2" fill="#ffd666" />
                            <rect x="77" y="26" width="6" height="22" rx="2" fill="#ffd666" />
                            <rect x="92" y="26" width="6" height="22" rx="2" fill="#ffd666" />
                            <rect x="63" y="28" width="1.5" height="18" fill="#ffffff" opacity="0.6" />
                            <rect x="78" y="28" width="1.5" height="18" fill="#ffffff" opacity="0.6" />
                            <rect x="93" y="28" width="1.5" height="18" fill="#ffffff" opacity="0.6" />
                            <path d="M65 22v4M80 22v4M95 22v4" stroke="#4b5563" stroke-width="1.2" stroke-linecap="round" />
                        </g>

                        {{-- Flames --}}
                        <circle class="bday-cake__glow" cx="65" cy="16" r="7" />
                        <circle class="bday-cake__glow bday-cake__glow--2" cx="80" cy="16" r="7" />
                        <circle class="bday-cake__glow bday-cake__glow--3" cx="95" cy="16" r="7" />
                        <g class="bday-cake__flame">
                            <path d="M65 8 C69 13 70 17 70 19.5 A5 5 0 0 1 60 19.5 C60 17 61 13 65 8Z" fill="#f59e0b" />
                            <path d="M65 13 C67 16 67.5 18 67.5 19.6 A2.5 2.5 0 0 1 62.5 19.6 C62.5 18 63 16 65 13Z" fill="#fffbeb" />
                        </g>
                        <g class="bday-cake__flame bday-cake__flame--2">
                            <path d="M80 8 C84 13 85 17 85 19.5 A5 5 0 0 1 75 19.5 C75 17 76 13 80 8Z" fill="#f59e0b" />
                            <path d="M80 13 C82 16 82.5 18 82.5 19.6 A2.5 2.5 0 0 1 77.5 19.6 C77.5 18 78 16 80 13Z" fill="#fffbeb" />
                        </g>
                        <g class="bday-cake__flame bday-cake__flame--3">
                            <path d="M95 8 C99 13 100 17 100 19.5 A5 5 0 0 1 90 19.5 C90 17 91 13 95 8Z" fill="#f59e0b" />
                            <path d="M95 13 C97 16 97.5 18 97.5 19.6 A2.5 2.5 0 0 1 92.5 19.6 C92.5 18 93 16 95 13Z" fill="#fffbeb" />
                        </g>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Full-screen birthday celebration: first dashboard visit of the birthday only,
             auto-closes after 5s (JS: "Birthday celebration overlay"). --}}
        <div class="bday-celebration" id="bday-celebration" hidden
            data-celebrate-key="sargam.bdayCelebrated.{{ auth()->id() }}.{{ now()->toDateString() }}">
            <canvas class="bday-celebration__confetti" id="bday-celebration-canvas" aria-hidden="true"></canvas>
            <div class="bday-celebration__balloons" id="bday-celebration-balloons" aria-hidden="true"></div>
            <span class="bday-celebration__popper bday-celebration__popper--left" aria-hidden="true">🎉</span>
            <span class="bday-celebration__popper bday-celebration__popper--right" aria-hidden="true">🎉</span>

            <div class="bday-celebration__card" role="status" aria-live="polite">
                <span class="bday-celebration__cake" aria-hidden="true">🎂</span>
                <p class="bday-celebration__eyebrow">Today is your special day</p>
                <h2 class="bday-celebration__title">Happy Birthday, <span>{{ $userName }}</span>!</h2>
                <p class="bday-celebration__text">Wishing you good health, happiness and a wonderful year ahead.</p>
                <button type="button" class="bday-celebration__skip" id="bday-celebration-skip">Continue to Dashboard</button>
                <div class="bday-celebration__timer" aria-hidden="true"><span></span></div>
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
                                @if($card['show_count'] ?? true)
                                @php
                                    // Counts are whole numbers and keep their 0-padding, but a
                                    // marks total can carry a half mark, and (int) would have
                                    // shown 13.5 as 13.
                                    $v = is_numeric($card['count']) ? (float) $card['count'] : 0;
                                    $statValue = floor($v) == $v
                                        ? ($v < 10 ? sprintf('%02d', $v) : (string) (int) $v)
                                        : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
                                @endphp
                                <p class="stat-value">{{ $statValue }}</p>
                                @else
                                {{-- Cards that open a page rather than count rows (the
                                     timetables) carry no number — an "00" would read as
                                     "nothing there". --}}
                                <p class="stat-value stat-value-link">Open</p>
                                @endif
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
                $noticeCategoryLabels = [
                'office-orders' => 'Office Order',
                'work-allocation' => 'Work Allocations',
                'notice-circular' => 'Notice',
                ];
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
                @endphp
                <div class="card dashboard-panel dashboard-feed-panel mb-3" id="dashboard-notices-panel">
                    <div class="card-header py-3 px-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 gap-md-3">
                            <h5 class="dashboard-feed-panel__title mb-0">Notices</h5>
                            @if(canAuthorNotices())
                            <a href="{{ route('admin.notice.create') }}"
                                class="btn btn-sm dashboard-feed-btn-primary d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                <span>Add New Notice</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-0 px-4 pb-3 dashboard-list-scroll">
                        @if(count($notices) === 0)
                        <div class="dashboard-feed-empty">
                            <span class="dashboard-feed-empty__icon" aria-hidden="true">
                                <i class="bi bi-file-earmark-x"></i>
                            </span>
                            <p class="mb-3 text-body-secondary">No notices available.</p>
                            @if(canAuthorNotices())
                            <a href="{{ route('admin.notice.create') }}"
                                class="btn dashboard-feed-btn-primary d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                <span>Add New Notice</span>
                            </a>
                            @endif
                        </div>
                        @else
                        <ul class="list-unstyled mb-0 ps-0" id="dashboard-notice-list">
                            @foreach($notices as $notice)
                            @php
                            // The notice's own type, not the tab's label. Mapping it
                            // through $noticeCategoryLabels showed "Course notice" as
                            // "Work Allocations" and lumped Personal / Office notice /
                            // Service related together as plain "Notice" — the reader
                            // was told a type the author never chose. The tab grouping
                            // still uses $resolveDashboardNoticeTab.
                            $noticeCategory = $notice->notice_type ?: 'Notice';
                            $noticeDate = $notice->created_at ?? $notice->display_date ?? null;
                            $noticeDateLabel = $noticeDate
                            ? \Carbon\Carbon::parse($noticeDate)->format('d/m/Y h:i A')
                            : null;
                            $noticeAuthor = \Illuminate\Support\Str::title(trim((string) ($notice->author_name ?? '')));
                            $noticeAuthorDept = trim((string) ($notice->author_department ?? ''));
                            $noticeMeta = trim(
                            ($noticeAuthor !== '' ? '~by ' . $noticeAuthor . ($noticeAuthorDept !== '' ? ' (' .
                            $noticeAuthorDept . ')' : '') : '')
                            . ($noticeDateLabel ? ($noticeAuthor !== '' ? ' on ' : '') . $noticeDateLabel : '')
                            );
                            @endphp
                            <li class="dashboard-notice-row">
                                <div class="dashboard-notice-item"
                                    data-notice-pk="{{ $notice->pk }}"
                                    data-notice-title="{{ $notice->notice_title ?? '' }}"
                                    data-notice-desc='@json(notice_safe_html($notice->description ?? ""))'
                                    data-notice-badge="{{ $noticeCategory }}"
                                    data-notice-meta="{{ $noticeMeta }}"
                                    data-notice-doc="{{ $notice->document ? route('admin.notice.attachment', $notice->pk) : '' }}"
                                    role="button" tabindex="0"
                                    aria-label="View notice: {{ $notice->notice_title ?? 'Notice' }}">
                                    <span class="dashboard-notice-title">{{ $notice->notice_title }}</span>
                                    <div class="dashboard-notice-meta">
                                        <small class="dashboard-notice-byline">
                                            @if($noticeAuthor !== '')
                                            <span class="dashboard-notice-byline__tilde" aria-hidden="true">~</span>by
                                            <span class="dashboard-notice-author">{{ $noticeAuthor }}@if($noticeAuthorDept !== '') ({{ $noticeAuthorDept }})@endif</span>
                                            @endif
                                            @if($noticeDateLabel)
                                            <span class="dashboard-notice-date">{{ $noticeAuthor !== '' ? 'on ' : '' }}{{
                                                $noticeDateLabel }}</span>
                                            @endif
                                        </small>
                                        <span class="dashboard-notice-type-pill">{{ $noticeCategory }}</span>
                                    </div>
                                    @if($notice->document)
                                    <a href="{{ route('admin.notice.attachment', $notice->pk) }}" target="_blank"
                                        class="dashboard-notice-attachment text-danger text-decoration-none">
                                        <i class="bi bi-paperclip" aria-hidden="true"></i>View attachment
                                    </a>
                                    @endif
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
                                <strong class="text-body">{{ $noticeTotalCount }}</strong> notices.</span>
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

            @if(in_array('widget_todays_birthdays', $enabledWidgetKeys) || in_array('widget_calendar', $enabledWidgetKeys) || in_array('widget_house_performance', $enabledWidgetKeys))
            <div class="col-auto" style="width: 480px; min-width: 480px;">
                @if(in_array('widget_house_performance', $enabledWidgetKeys))
                {{-- House wise Performance: every house on the courses running now,
                     least against it first. The figure is the marks its officer
                     trainees have lost on closed memos and discipline memos. --}}
                <div class="card dashboard-panel dashboard-house-panel border-0 mb-4" id="house-wise-performance">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex align-items-center justify-content-between w-100 gap-2 flex-wrap">
                            {{-- The title opens the full breakdown: the panel can only
                                 show a total per house, not who it came from. --}}
                            <a href="{{ route('admin.dashboard.house-wise-performance', array_filter(['course' => $houseCourseFilter ?? null])) }}"
                                class="dashboard-birthdays-panel__title mb-0 h5 text-decoration-none"
                                title="Open the full house wise performance breakdown">
                                House wise Performance
                            </a>
                            <div class="d-flex align-items-center gap-1 ms-auto">
                                @if(($houseCourses ?? collect())->isNotEmpty())
                                    {{-- Reloads the dashboard with ?house_course=, so the
                                         panel and the page it links to are computed by the
                                         same code rather than a second AJAX path. --}}
                                    <select class="form-select form-select-sm dashboard-house-course" id="housePerformanceCourse"
                                        aria-label="Filter house wise performance by course"
                                        onchange="window.location = '{{ route('admin.dashboard') }}' + (this.value ? ('?house_course=' + encodeURIComponent(this.value)) : '') + '#house-wise-performance';">
                                        <option value="">All Courses</option>
                                        @foreach($houseCourses as $pk => $name)
                                            <option value="{{ $pk }}" {{ (string) ($houseCourseFilter ?? '') === (string) $pk ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                                <a href="{{ route('admin.dashboard.house-wise-performance', array_filter(['course' => $houseCourseFilter ?? null])) }}"
                                    class="btn btn-sm btn-link text-decoration-none px-1" aria-label="Open house wise performance">
                                    <i class="material-icons material-symbols-rounded align-middle" style="font-size:20px;">chevron_right</i>
                                </a>
                            </div>
                        </div>
                        <hr class="dashboard-birthdays-divider mb-0">
                    </div>
                    <div class="card-body">
                        @if(($housePerformance ?? collect())->isEmpty())
                        <div class="dashboard-empty-state py-4">
                            <i class="bi bi-house text-primary opacity-50 fs-1 d-block mb-2" aria-hidden="true"></i>
                            <p class="mb-0 small text-body-secondary">No houses mapped yet.</p>
                        </div>
                        @else
                        <ul class="list-unstyled mb-0 dashboard-house-list">
                            @foreach($housePerformance as $house)
                            <li class="dashboard-house-item">
                                <span class="dashboard-house-rank">{{ $loop->iteration }}</span>
                                <span class="dashboard-house-name text-truncate" title="{{ $house['house'] }}">{{ $house['house'] }}</span>
                                <span class="dashboard-house-students">{{ $house['students'] }} OT{{ $house['students'] == 1 ? '' : 's' }}</span>
                                {{-- Marks deducted, not a record count (UAT 15-09-2026) --}}
                                <span class="dashboard-house-total" aria-label="{{ $house['total'] }} marks deducted">{{ $house['total'] + 0 }}</span>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
                @endif

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

    @include('admin.dashboard.partials.report_issue')

    {{-- Notice detail modal — driven by the data-notice-* payload on
         .dashboard-notice-item. Without this the cards carry role="button" and
         cursor:pointer but do nothing when clicked. --}}
    <div class="modal fade" id="dashboard-notice-modal" tabindex="-1"
        aria-labelledby="dashboard-notice-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-1">
                    <div class="flex-grow-1 min-w-0 pe-2">
                        <h5 class="modal-title fw-semibold lh-sm mb-1" id="dashboard-notice-modal-label"></h5>
                        <small class="text-muted" id="dashboard-notice-modal-meta"></small>
                    </div>
                    <span class="dashboard-notice-type-pill flex-shrink-0 me-2 mt-1"
                        id="dashboard-notice-modal-badge"></span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <hr class="mt-0 mb-3">
                    <div class="notice-description-content" id="dashboard-notice-modal-body"></div>
                    <div class="mt-3 d-none" id="dashboard-notice-modal-attachment">
                        <a id="dashboard-notice-modal-doc" href="#" target="_blank" rel="noopener noreferrer"
                            class="small text-danger text-decoration-none d-inline-flex align-items-center gap-1">
                            <i class="bi bi-paperclip" aria-hidden="true"></i> View attachment
                        </a>
                    </div>
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

    // ── Birthday banner: party poppers firing continuously ──
    // Both bottom corners burst confetti up and inward every BURST_EVERY ms for as
    // long as the banner is on screen. Bursts are timed inside the rAF loop, so a
    // background tab (where rAF pauses) doesn't pile up queued bursts.
    (function() {
        var canvas = document.getElementById('confetti-canvas');
        var banner = document.getElementById('birthday-banner');
        if (!canvas || !banner || banner.classList.contains('is-dismissed')) return;
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var ctx = canvas.getContext('2d');
        var BURST_EVERY = 2500;
        var GRAVITY = 0.15;
        var colors = ['#004a93', '#3498db', '#b12923', '#e74c3c', '#f5b301', '#ffd666', '#2ecc71', '#9b59b6', '#ff7aa2'];
        var W = 0, H = 0, pieces = [], lastBurst = -Infinity, animFrame;

        function resize() {
            W = canvas.width = banner.offsetWidth;
            H = canvas.height = banner.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        // One popper: a cone of confetti from a bottom corner, peaking near the banner's top.
        function pop(fromLeft) {
            var peak = Math.sqrt(2 * GRAVITY * H * 0.95);
            for (var i = 0; i < 100; i++) {
                pieces.push({
                    x: fromLeft ? 4 : W - 4,
                    y: H,
                    vx: (fromLeft ? 1 : -1) * (2 + Math.random() * 5),
                    vy: -peak * (0.65 + Math.random() * 0.35),
                    w: 5 + Math.random() * 5,
                    h: 3 + Math.random() * 3,
                    rot: Math.random() * 360,
                    vr: Math.random() * 14 - 7,
                    color: colors[Math.floor(Math.random() * colors.length)]
                });
            }
        }

        function frame(now) {
            if (banner.classList.contains('is-dismissed')) {   // closed → stop for good
                cancelAnimationFrame(animFrame);
                window.removeEventListener('resize', resize);
                return;
            }
            if (now - lastBurst >= BURST_EVERY) {
                lastBurst = now;
                pop(true);
                pop(false);
            }

            ctx.clearRect(0, 0, W, H);
            pieces = pieces.filter(function(p) { return p.y < H + 12; });
            pieces.forEach(function(p) {
                p.vy += GRAVITY;
                p.vx *= 0.99;
                p.x += p.vx;
                p.y += p.vy;
                p.rot += p.vr;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot * Math.PI / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            });
            animFrame = requestAnimationFrame(frame);
        }

        animFrame = requestAnimationFrame(frame);
    })();

    // ── Birthday celebration overlay (first visit of the birthday, 5s) ──
    // shortcut: "first visit" is remembered per browser (localStorage), so a second
    // device shows it once more; move to a server flag if that matters.
    (function() {
        var overlay = document.getElementById('bday-celebration');
        if (!overlay) return;

        var key = overlay.getAttribute('data-celebrate-key');
        try {
            if (localStorage.getItem(key) === '1') return;
            localStorage.setItem(key, '1');
        } catch (e) { /* storage blocked: celebrate anyway, it's harmless */ }

        var DURATION = 5000;
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var canvas = document.getElementById('bday-celebration-canvas');
        var ctx = canvas.getContext('2d');
        var colors = ['#f5b301', '#ffd666', '#b12923', '#e74c3c', '#004a93', '#3498db', '#2ecc71', '#9b59b6', '#ff7aa2', '#ffffff'];
        var pieces = [], raf = null, closed = false, W = 0, H = 0;

        function resize() {
            W = canvas.width = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }

        // Party popper: a cone of confetti fired up and inward from a bottom corner.
        function pop(fromLeft) {
            var x = fromLeft ? 0 : W, y = H;
            for (var i = 0; i < 110; i++) {
                var angle = (fromLeft ? -60 : -120) + (Math.random() * 40 - 20);
                var speed = 12 + Math.random() * 12;
                var rad = angle * Math.PI / 180;
                pieces.push({
                    x: x, y: y,
                    vx: Math.cos(rad) * speed, vy: Math.sin(rad) * speed,
                    w: 6 + Math.random() * 6, h: 3 + Math.random() * 4,
                    rot: Math.random() * 360, vr: Math.random() * 12 - 6,
                    color: colors[Math.floor(Math.random() * colors.length)]
                });
            }
        }

        function frame() {
            ctx.clearRect(0, 0, W, H);
            pieces = pieces.filter(function(p) { return p.y < H + 20; });
            pieces.forEach(function(p) {
                p.vy += 0.28;           // gravity
                p.vx *= 0.985;          // air drag
                p.x += p.vx;
                p.y += p.vy;
                p.rot += p.vr;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot * Math.PI / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            });
            raf = requestAnimationFrame(frame);
        }

        function spawnBalloons() {
            var box = document.getElementById('bday-celebration-balloons');
            for (var i = 0; i < 16; i++) {
                var b = document.createElement('span');
                b.className = 'bday-balloon';
                b.style.left = (Math.random() * 94) + '%';
                b.style.setProperty('--balloon-color', colors[i % 9]);
                b.style.animationDuration = (3.6 + Math.random() * 1.6) + 's';
                b.style.animationDelay = (Math.random() * 1.2) + 's';
                b.style.setProperty('--balloon-scale', (0.75 + Math.random() * 0.5).toFixed(2));
                box.appendChild(b);
            }
        }

        function close() {
            if (closed) return;
            closed = true;
            overlay.classList.add('is-leaving');
            setTimeout(function() {
                if (raf) cancelAnimationFrame(raf);
                overlay.hidden = true;
                overlay.remove();
            }, 500);
            document.removeEventListener('keydown', onKey);
        }

        function onKey(e) { if (e.key === 'Escape') close(); }

        document.body.appendChild(overlay); // escape any transformed ancestor so position:fixed covers the viewport
        overlay.hidden = false;
        overlay.style.setProperty('--bday-duration', DURATION + 'ms');
        document.getElementById('bday-celebration-skip').addEventListener('click', close);
        overlay.addEventListener('click', function(e) { if (e.target === overlay) close(); });
        document.addEventListener('keydown', onKey);

        if (!reduceMotion) {
            resize();
            window.addEventListener('resize', resize);
            spawnBalloons();
            pop(true);
            pop(false);
            setTimeout(function() { if (!closed) { pop(true); pop(false); } }, 1400);
            frame();
        }

        setTimeout(close, DURATION);
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

    // Notice card -> detail modal. The card carries the whole payload in
    // data-notice-* so no request is needed to open it.
    (function () {
        var modalEl = document.getElementById('dashboard-notice-modal');
        if (!modalEl) return;

        function openNoticeModal(card) {
            document.getElementById('dashboard-notice-modal-label').textContent = card.dataset.noticeTitle || '';
            document.getElementById('dashboard-notice-modal-meta').textContent = card.dataset.noticeMeta || '';

            var badgeEl = document.getElementById('dashboard-notice-modal-badge');
            badgeEl.textContent = card.dataset.noticeBadge || '';
            badgeEl.classList.toggle('d-none', !(card.dataset.noticeBadge || '').trim());

            // data-notice-desc holds the description JSON-encoded (escaped for the
            // attribute), so parse it back before injecting it as HTML.
            var desc = '';
            try { desc = JSON.parse(card.dataset.noticeDesc || '""'); } catch (e) { desc = ''; }
            document.getElementById('dashboard-notice-modal-body').innerHTML =
                desc || '<p class="text-muted fst-italic mb-0">No description provided.</p>';

            var attachWrap = document.getElementById('dashboard-notice-modal-attachment');
            var attachLink = document.getElementById('dashboard-notice-modal-doc');
            var docUrl = card.dataset.noticeDoc || '';
            if (docUrl) {
                attachLink.href = docUrl;
                attachWrap.classList.remove('d-none');
            } else {
                attachWrap.classList.add('d-none');
            }

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        document.addEventListener('click', function (e) {
            // The inline "View attachment" link opens the file directly.
            if (e.target.closest('.dashboard-notice-attachment')) return;
            var card = e.target.closest('.dashboard-notice-item[data-notice-pk]');
            if (card) openNoticeModal(card);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest ? e.target.closest('.dashboard-notice-item[data-notice-pk]') : null;
            if (!card) return;
            e.preventDefault();
            openNoticeModal(card);
        });
    })();
    </script>
    @endpush
    @endsection