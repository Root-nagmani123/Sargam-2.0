@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
.admin-dashboard-surface {
    background: linear-gradient(160deg, #f0f4f9 0%, #e8eef6 50%, #f5f7fb 100%);
    min-height: 100%;
}
.dashboard-panel {
    border: 0;
    border-radius: 1rem;
    background: var(--bs-body-bg);
    box-shadow: 0 2px 12px rgba(16, 24, 40, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.dashboard-panel .card-header {
    border-bottom: 1px solid var(--bs-border-color-translucent);
    background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.98) 100%);
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}
.dashboard-panel .card-header .material-icons.material-symbols-rounded {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.6rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    font-size: 1.2rem !important;
}

.dashboard-stat-card {
    border: 0;
    border-left: 3px solid var(--bs-border-color);
    border-radius: 0.9rem;
    box-shadow: 0 2px 8px rgba(16, 24, 40, 0.08);
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    min-height: 84px;
}

.dashboard-stat-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(16, 24, 40, 0.1);
}

.dashboard-stat-card .card-body {
    padding: 0.55rem 0.7rem;
}

.dashboard-stat-value {
    font-size: clamp(1.35rem, 1.55vw, 1.85rem);
    line-height: 1.05;
    letter-spacing: -0.02em;
}

.dashboard-stat-card.card-blue {
    border-left-color: var(--bs-primary);
    background: var(--bs-primary-bg-subtle);
}

.dashboard-stat-card.card-green {
    border-left-color: var(--bs-success);
    background: var(--bs-success-bg-subtle);
}

.dashboard-stat-card.card-amber {
    border-left-color: var(--bs-warning);
    background: var(--bs-warning-bg-subtle);
}

.dashboard-stat-card.card-rose {
    border-left-color: var(--bs-danger);
    background: var(--bs-danger-bg-subtle);
}

.dashboard-panel {
    border: 0;
    border-radius: 0.9rem;
    background: var(--bs-body-bg);
    box-shadow: 0 2px 8px rgba(16, 24, 40, 0.07);
}

.dashboard-panel .card-header {
    border-bottom: 1px solid var(--bs-border-color-translucent);
    background: transparent;
    padding-top: 0.9rem !important;
    padding-bottom: 0.9rem !important;
}

.dashboard-birthday-item {
    border: 1px solid #b7cdf9;
    background: #f7f9ff;
    border-radius: 0.5rem;
}

.dashboard-birthday-item .card-body {
    padding: 0.8rem !important;
}

.dashboard-avatar {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

.dashboard-list-scroll {
    max-height: 23rem;
    overflow-y: auto;
}

@media (max-width: 991.98px) {
    .dashboard-list-scroll {
        max-height: none;
    }
}

.dashboard-welcome {
    background: linear-gradient(135deg, #004a93 0%, #003a75 50%, #002d5c 100%) !important;
    border-radius: 1rem;
    color: #fff;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 74, 147, 0.25);
    position: relative;
    overflow: hidden;
}
.dashboard-welcome::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 40%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.dashboard-welcome h2 { font-size: 1.3rem; font-weight: 600; margin-bottom: 0.25rem; letter-spacing: -0.01em; }
.dashboard-welcome .text-white { font-size: 0.9rem; opacity: 0.95; }
.dashboard-welcome .material-icons { opacity: 0.9; }

.dashboard-stat-card .stat-icon {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    opacity: 0.9;
}
.dashboard-stat-card.card-blue .stat-icon { background: rgba(var(--bs-primary-rgb), 0.2); color: var(--bs-primary); }
.dashboard-stat-card.card-green .stat-icon { background: rgba(var(--bs-success-rgb), 0.2); color: var(--bs-success); }
.dashboard-stat-card.card-amber .stat-icon { background: rgba(var(--bs-warning-rgb), 0.2); color: var(--bs-warning); }
.dashboard-stat-card.card-rose .stat-icon { background: rgba(var(--bs-danger-rgb), 0.2); color: var(--bs-danger); }

.dashboard-stat-card .stat-link-hint {
    font-size: 0.7rem;
    display: inline-flex;
    align-items: center;
    gap: 0.1rem;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.dashboard-stat-card:hover .stat-link-hint { opacity: 1; }

.dashboard-empty-state {
    text-align: center;
    padding: 2rem 1.25rem;
    color: var(--bs-secondary);
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.03) 0%, transparent 100%);
    border-radius: 0.75rem;
    border: 1px dashed var(--bs-border-color-translucent);
}
.dashboard-empty-state .material-icons {
    font-size: 2.75rem;
    margin-bottom: 0.75rem;
    opacity: 0.4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.06);
}
.dashboard-empty-state p { font-size: 0.875rem; }

.dashboard-tweet-item {
    padding: 12px 14px 12px 16px;
    margin-bottom: 10px;
    border-radius: 10px;
    border-left: 4px solid var(--bs-primary);
    background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.05) 0%, transparent 100%);
    transition: background 0.2s ease, transform 0.15s ease;
}
.dashboard-tweet-item:hover {
    background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.08) 0%, transparent 100%);
    transform: translateX(2px);
}
.dashboard-tweet-item:last-child { margin-bottom: 0; }

/* Today's Classes cards */
.dashboard-class-card {
    padding: 14px 16px;
    margin-bottom: 12px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-left: 4px solid var(--bs-primary);
    background: linear-gradient(180deg, #fff 0%, rgba(248,250,252,0.7) 100%);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}
.dashboard-class-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
}
.dashboard-class-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem !important;
}
.dashboard-class-topic {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-bottom: 8px;
    padding-left: 2.5rem;
}
.dashboard-class-meta {
    font-size: 0.8125rem;
    color: var(--bs-secondary);
    padding-left: 2.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 12px 16px;
}
.dashboard-class-meta span { white-space: nowrap; }

.dashboard-list-scroll::-webkit-scrollbar { width: 6px; }
.dashboard-list-scroll::-webkit-scrollbar-track { background: rgba(0,0,0,0.04); border-radius: 3px; }
.dashboard-list-scroll::-webkit-scrollbar-thumb { background: rgba(var(--bs-primary-rgb), 0.25); border-radius: 3px; }
.dashboard-list-scroll::-webkit-scrollbar-thumb:hover { background: rgba(var(--bs-primary-rgb), 0.4); }

.dashboard-panel .card-header .badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35em 0.65em;
    min-width: 1.75rem;
    text-align: center;
}

/* Notifications panel - item design and blinking "New" tag */
.dashboard-notification-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    width: 100%;
    padding: 16px 18px;
    margin-bottom: 10px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-left: 4px solid transparent;
    background: linear-gradient(180deg, #fff 0%, rgba(248,250,252,0.8) 100%);
    text-align: left;
    transition: all 0.2s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    cursor: pointer;
}
.dashboard-notification-item:hover {
    background: linear-gradient(180deg, #fff 0%, rgba(248,250,252,1) 100%);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    transform: translateY(-1px);
}
.dashboard-notification-item-unread {
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    border-left-color: var(--bs-primary);
    border-color: rgba(var(--bs-primary-rgb), 0.15);
}
.dashboard-notification-item-unread:hover {
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.12) 0%, rgba(var(--bs-primary-rgb), 0.04) 100%);
}
.dashboard-notification-item .notification-icon-wrap {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem !important;
}
.dashboard-notification-body {
    flex-grow: 1;
    min-width: 0;
}
.dashboard-notification-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--bs-body-color);
    line-height: 1.35;
}
.dashboard-notification-message {
    font-size: 0.8125rem;
    color: var(--bs-secondary);
    margin: 4px 0 0 0;
    line-height: 1.45;
}
.dashboard-notification-time {
    font-size: 0.6875rem;
    color: var(--bs-secondary);
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.dashboard-notification-time::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--bs-secondary);
    opacity: 0.6;
}
/* Blinking "New" tag for unread notifications */
.dashboard-notification-new-tag {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.3em 0.6em;
    flex-shrink: 0;
    animation: dashboard-notification-blink 1s ease-in-out infinite;
}
@keyframes dashboard-notification-blink {
    0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(var(--bs-danger-rgb), 0.5); }
    50% { opacity: 0.9; transform: scale(1.03); box-shadow: 0 0 0 6px rgba(var(--bs-danger-rgb), 0); }
}

/* Notices panel - item design and blinking "New" tag */
.dashboard-notice-item {
    display: block;
    padding: 16px 18px;
    margin-bottom: 10px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-left: 4px solid transparent;
    background: linear-gradient(180deg, #fff 0%, rgba(248,250,252,0.8) 100%);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}
.dashboard-notice-item:hover {
    background: linear-gradient(180deg, #fff 0%, rgba(248,250,252,1) 100%);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    transform: translateY(-1px);
}
.dashboard-notice-item-new {
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    border-left-color: var(--bs-primary);
    border-color: rgba(var(--bs-primary-rgb), 0.15);
}
.dashboard-notice-item-new:hover {
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.12) 0%, rgba(var(--bs-primary-rgb), 0.04) 100%);
}
.dashboard-notice-item .notice-icon-wrap {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem !important;
}
.dashboard-notice-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--bs-body-color);
    line-height: 1.35;
}
.dashboard-notice-date {
    font-size: 0.8125rem;
    color: var(--bs-secondary);
    margin-top: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.dashboard-notice-date::before {
    content: '';
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--bs-secondary);
    opacity: 0.6;
}
.dashboard-notice-attachment {
    font-size: 0.8125rem;
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(var(--bs-danger-rgb), 0.08);
    transition: background 0.2s ease;
}
.dashboard-notice-attachment:hover {
    background: rgba(var(--bs-danger-rgb), 0.14);
}
.dashboard-notice-new-tag {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.3em 0.6em;
    animation: dashboard-notice-blink 1.2s ease-in-out infinite;
}
@keyframes dashboard-notice-blink {
    0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(var(--bs-danger-rgb), 0.45); }
    50% { opacity: 0.9; transform: scale(1.03); box-shadow: 0 0 0 6px rgba(var(--bs-danger-rgb), 0); }
}

.dashboard-stat-card:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}
table>thead{
    background-color: transparent !important;
}
</style>

@php
$user = Auth::user();
$isAdminSummary = hasRole('Admin');
$daysOld = $isAdminSummary ? 10 : null;
$notifications = $user ? notification()->getNotifications($user->user_id, 10, false, $daysOld) : collect();
$notificationBadgeCount = $user
    ? ($isAdminSummary ? notification()->getUnreadCount($user->user_id, $daysOld) : $notifications->count())
    : 0;
$notices = get_notice_notification_by_role();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$userName = $user ? ($user->first_name ?? $user->name ?? 'User') : 'User';
@endphp

<div class="container-fluid px-3 px-lg-4">
    @if($isMyBirthday ?? false)
    {{-- Birthday Banner with Confetti --}}
    <div class="birthday-banner-wrapper mb-3" id="birthday-banner">
        <canvas id="confetti-canvas"></canvas>
        <div class="birthday-banner-content">
            <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                <span style="font-size:2.5rem;">🎂</span>
                <div class="text-center">
                    <h3 class="mb-0 fw-bold text-white">Happy Birthday, {{ $userName }}! 🎉</h3>
                    <p class="mb-0 text-white-50 small">Wishing you a wonderful year ahead!</p>
                    @if(($myBirthdayWishCount ?? 0) > 0)
                    <div class="mt-1">
                        <button type="button" class="badge bg-white text-primary rounded-pill px-3 py-2 fw-semibold border-0 shadow-sm" style="font-size:0.85rem; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#birthdayWishesReceivedModal" title="See who wished you and send a reply">
                            🎁 {{ $myBirthdayWishCount }} {{ $myBirthdayWishCount === 1 ? 'wish' : 'wishes' }} received today — tap to view &amp; reply
                        </button>
                    </div>
                    @endif
                </div>
                <span style="font-size:2.5rem;">🎈</span>
            </div>
        </div>
    </div>
    <style>
    .birthday-banner-wrapper {
        position: relative;
        border-radius: 1rem;
        overflow: hidden;
        background: linear-gradient(135deg, #e91e63 0%, #9c27b0 40%, #673ab7 70%, #3f51b5 100%);
        box-shadow: 0 6px 30px rgba(233, 30, 99, 0.35);
        min-height: 100px;
    }
    #confetti-canvas {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    .birthday-banner-content {
        position: relative;
        z-index: 2;
        padding: 1.5rem 2rem;
    }
    .birthday-banner-wrapper::before {
        content: '';
        position: absolute;
        top: -50%; right: -20%;
        width: 60%; height: 200%;
        background: radial-gradient(ellipse, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    </style>
    @if(($myBirthdayWishCount ?? 0) > 0)
        @include('admin.birthday-wish.partials.received_wishes_modal')
    @endif
    @endif

    <div class="dashboard-welcome shadow-sm bg-gradient d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="mb-0 text-white">{{ $greeting }}, {{ $userName }}</h2>
            <div class="text-white">{{ now()->format('l, d F Y') }}</div>
        </div>
        <div class="d-none d-sm-block">
            <span class="material-icons material-symbols-rounded align-middle me-1">calendar_month</span>
            <span class="small" id="dashboard-live-time">{{ now()->format('h:i A') }}</span>
        </div>
    </div>

@if(hasRole('Security Card') || hasRole('Admin Security'))
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    @php
                        $idCardApprovalRoute = hasRole('Admin Security')
                            ? route('admin.security.employee_idcard_approval.approval3')
                            : route('admin.security.employee_idcard_approval.approval2');
                    @endphp
                    <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none">
                        <div class="card dashboard-stat-card shadow-sm rounded-4 card-blue">
                            <div class="card-body d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <p class="small text-dark mb-1">Today's Pending Permanent ID Requests</p>
                                    <div class="dashboard-stat-value fw-semibold text-primary">{{ $todayPendingPermanentIdCardRequests ?? 0 }}</div>
                                    <span class="stat-link-hint text-primary">Go to approvals <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span></span>
                                </div>
                                <span class="stat-icon"><span class="material-icons material-symbols-rounded">badge</span></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none">
                        <div class="card dashboard-stat-card shadow-sm rounded-4 card-blue">
                            <div class="card-body d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <p class="small text-dark mb-1">Today's Pending Contractual ID Requests</p>
                                    <div class="dashboard-stat-value fw-semibold text-primary">{{ $todayPendingContractualIdCardRequests ?? 0 }}</div>
                                    <span class="stat-link-hint text-primary">Go to approvals <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span></span>
                                </div>
                                <span class="stat-icon"><span class="material-icons material-symbols-rounded">badge</span></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none">
                        <div class="card dashboard-stat-card shadow-sm rounded-4 card-amber">
                            <div class="card-body d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <p class="small text-dark mb-1">Today's Duplicate Permanent ID Requests</p>
                                    <div class="dashboard-stat-value fw-semibold text-warning-emphasis">{{ $todayDuplicatePermIdCardRequests ?? 0 }}</div>
                                    <span class="stat-link-hint text-warning">Go to approvals <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span></span>
                                </div>
                                <span class="stat-icon"><span class="material-icons material-symbols-rounded">copy_all</span></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none">
                        <div class="card dashboard-stat-card shadow-sm rounded-4 card-amber">
                            <div class="card-body d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <p class="small text-dark mb-1">Today's Duplicate Contractual ID Requests</p>
                                    <div class="dashboard-stat-value fw-semibold text-warning-emphasis">{{ $todayDuplicateContractualIdCardRequests ?? 0 }}</div>
                                    <span class="stat-link-hint text-warning">Go to approvals <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span></span>
                                </div>
                                <span class="stat-icon"><span class="material-icons material-symbols-rounded">content_copy</span></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-4 col-md-6">
                    <a href="{{ route('admin.security.family_idcard_approval.index') }}" class="text-decoration-none">
                        <div class="card dashboard-stat-card shadow-sm rounded-4 card-blue">
                            <div class="card-body d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <p class="small text-dark mb-1">Today's Pending Family ID Requests</p>
                                    <div class="dashboard-stat-value fw-semibold text-primary">{{ $todayFamilyApprovals ?? 0 }}</div>
                                    <span class="stat-link-hint text-primary">Go to approvals <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span></span>
                                </div>
                                <span class="stat-icon"><span class="material-icons material-symbols-rounded">diversity_3</span></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-4 col-md-6">
                    <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="text-decoration-none">
                        <div class="card dashboard-stat-card shadow-sm rounded-4 card-green">
                            <div class="card-body d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <p class="small text-dark mb-1">Today's Pending Vehicle Pass Requests</p>
                                    <div class="dashboard-stat-value fw-semibold text-success">{{ $todayVehicleApprovals ?? 0 }}</div>
                                    <span class="stat-link-hint text-success">Go to approvals <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span></span>
                                </div>
                                <span class="stat-icon"><span class="material-icons material-symbols-rounded">directions_car</span></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            @endif
    @if(!hasRole('Security Card') && !hasRole('Admin Security'))
    <div class="row g-2 g-md-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5">
        <div class="col">
            <a href="{{ route('admin.dashboard.active_course') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-blue h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Total Active Courses</p>
                            <div class="dashboard-stat-value fw-semibold text-primary">{{ $totalActiveCourses }}</div>
                            <span class="stat-link-hint text-primary small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">menu_book</span></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('admin.dashboard.incoming_course') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-green h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Upcoming Courses</p>
                            <div class="dashboard-stat-value fw-semibold text-success">{{ $upcomingCourses }}</div>
                            <span class="stat-link-hint text-success small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">event</span></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('admin.dashboard.upcoming_events') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-amber h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Upcoming Events</p>
                            <div class="dashboard-stat-value fw-semibold text-warning-emphasis">2</div>
                            <span class="stat-link-hint text-warning small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">campaign</span></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            @if(hasRole('Student-OT'))
            <a href="{{ route('medical.exception.ot.view') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-rose h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Medical Exception</p>
                            <div class="dashboard-stat-value fw-semibold text-danger">{{ $exemptionCount }}</div>
                            <span class="stat-link-hint text-danger small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">monitor_heart</span></span>
                    </div>
                </div>
            </a>
            @else
            <a href="{{ route('admin.dashboard.guest_faculty') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-rose h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Total Guest Faculty</p>
                            <div class="dashboard-stat-value fw-semibold text-danger">{{ $total_guest_faculty }}</div>
                            <span class="stat-link-hint text-danger small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">badge</span></span>
                    </div>
                </div>
            </a>
            @endif
        </div>

        @if(($todayApproval1IdCardRequests ?? 0) > 0)
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-blue h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Today's Pending ID Card Requests (Approval I)</p>
                            <div class="dashboard-stat-value fw-semibold text-primary">{{ $todayApproval1IdCardRequests }}</div>
                            <span class="stat-link-hint text-primary">Go to approvals
                                <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span>
                            </span>
                        </div>
                        <span class="stat-icon"><span class="material-icons material-symbols-rounded">badge</span></span>
                    </div>
                </div>
            </a>
        </div>
        @endif
        @if(($todayApproval1DuplicateIdCardRequests ?? 0) > 0)
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-amber h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Today's Pending Duplicate ID Card Requests (Approval I)</p>
                            <div class="dashboard-stat-value fw-semibold text-warning-emphasis">{{ $todayApproval1DuplicateIdCardRequests }}</div>
                            <span class="stat-link-hint text-warning">Go to approvals
                                <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span>
                            </span>
                        </div>
                        <span class="stat-icon"><span class="material-icons material-symbols-rounded">content_copy</span></span>
                    </div>
                </div>
            </a>
        </div>
        @endif

        <div class="col-xl-3 col-md-6">
            @if(hasRole('Student-OT'))
            <a href="{{ route('ot.mdo.escrot.exemption.view') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-blue h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">OT MDO/Escort</p>
                            <div class="dashboard-stat-value fw-semibold text-primary">{{ $MDO_count }}</div>
                            <span class="stat-link-hint text-primary small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">manage_accounts</span></span>
                    </div>
                </div>
            </a>
            @else
            <a href="{{ route('admin.dashboard.inhouse_faculty') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-blue h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Total Inhouse Faculty</p>
                            <div class="dashboard-stat-value fw-semibold text-primary">{{ $total_internal_faculty }}</div>
                            <span class="stat-link-hint text-primary small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">groups</span></span>
                    </div>
                </div>
            </a>
            @endif
        </div>

        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty'))
        <div class="col">
            <a href="{{ route('admin.dashboard.sessions') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-green h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Session Details</p>
                            <div class="dashboard-stat-value fw-semibold text-success">{{ $totalSessions }}</div>
                            <span class="stat-link-hint text-success small">View <span class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon flex-shrink-0" aria-hidden="true"><span class="material-icons material-symbols-rounded">history</span></span>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(isset($isCCorACC) && $isCCorACC)
        <div class="col">
            <a href="{{ route('admin.dashboard.students') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card shadow-sm rounded-4 card-amber h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <p class="small text-body-secondary mb-1 text-truncate">Total Students</p>
                            <div class="dashboard-stat-value fw-semibold text-warning-emphasis">{{ $totalStudents }}</div>
                            <span class="stat-link-hint text-warning">View <span class="material-icons material-symbols-rounded align-middle" style="font-size: 1rem;">arrow_forward</span></span>
                        </div>
                        <span class="stat-icon"><span class="material-icons material-symbols-rounded">contacts</span></span>
                    </div>
                </div>
            </a>
        </div>
        @endif
    </div>
    @endif

    <div class="row g-3 g-lg-4">
        <div class="col-lg-7">
        @if(hasRole('Admin') || hasRole('Training-Induction'))
            <div class="card dashboard-panel shadow-sm rounded-4 mb-4">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-icons material-symbols-rounded text-primary">notifications</span>
                        {{ hasRole('Admin') ? 'Admin Summary' : 'Notifications' }}
                    </h5>
                    <span class="badge text-bg-primary rounded-pill">{{ $notificationBadgeCount }}</span>
                </div>
                <div class="card-body p-3 p-md-4 dashboard-list-scroll">
                    @if($notifications->isEmpty())
                    <div class="dashboard-empty-state">
                        <span class="material-icons material-symbols-rounded">notifications_off</span>
                        <p class="mb-0 small">No notifications available.</p>
                    </div>
                    @else
                    <ul class="list-unstyled mb-0 ps-0">
                        @foreach($notifications as $notification)
                        <li class="mb-2">
                            <button type="button"
                                class="dashboard-notification-item {{ empty($notification->is_read) ? 'dashboard-notification-item-unread' : '' }}"
                                data-notification-id="{{ $notification->pk }}">
                                <div class="d-flex gap-3 flex-grow-1 min-w-0">
                                    <span class="notification-icon-wrap"><span class="material-icons material-symbols-rounded">notifications</span></span>
                                    <div class="dashboard-notification-body">
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                            <span class="dashboard-notification-title">{{ $notification->title ?? 'Notification' }}</span>
                                            @if(empty($notification->is_read))
                                            <span class="badge bg-danger dashboard-notification-new-tag">New</span>
                                            @endif
                                        </div>
                                        <p class="dashboard-notification-message mb-0">{{ Str::limit(\App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? ''), 80) }}</p>
                                        <span class="dashboard-notification-time">{{ isset($notification->created_at) ? \Carbon\Carbon::parse($notification->created_at)->diffForHumans() : '—' }}</span>
                                    </div>
                                </div>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>

            <div class="card dashboard-panel shadow-sm rounded-4 mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-primary">campaign</span>
                    <h5 class="mb-0 fw-semibold">Campus Tweets</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="dashboard-tweet-item">
                        <span class="small text-body-secondary">You have <strong class="text-body">{{ $notifications->count() }}</strong> unread notices and total <strong class="text-body">{{ count($notices) }}</strong> notices.</span>
                    </div>
                    <div class="dashboard-tweet-item">
                        <span class="small text-body-secondary">You have <strong class="text-body">{{ $notifications->count() }}</strong> purchase orders for approval.</span>
                    </div>
                    <div class="dashboard-tweet-item">
                        <span class="small text-body-secondary"><a href="#" class="link-primary text-decoration-none fw-medium">Click Here</a> for menu of departmental canteen for next 2 weeks.</span>
                    </div>
                </div>
            </div>
            @endif

            @if(hasRole('Student-OT') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
            <div class="card dashboard-panel shadow-sm rounded-4 mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-primary">fact_check</span>
                    <h5 class="mb-0 fw-semibold">Today's Classes</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    @if($todayTimetable && $todayTimetable->isNotEmpty())
                    <div class="dashboard-list-scroll pe-2">
                        @foreach($todayTimetable as $entry)
                        <div class="dashboard-class-card">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="dashboard-class-icon"><span class="material-icons material-symbols-rounded">schedule</span></span>
                                <span class="text-primary fw-semibold">{{ $entry['session_date'] }} · {{ $entry['session_time'] }}</span>
                            </div>
                            <div class="dashboard-class-topic">{{ $entry['topic'] }}</div>
                            <div class="dashboard-class-meta">
                                <span>Faculty: {{ $entry['faculty_name'] }}</span>
                                <span>Group: {{ $entry['group_name'] ?? 'N/A' }}</span>
                                <span>Venue: {{ $entry['session_venue'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="dashboard-empty-state">
                        <span class="material-icons material-symbols-rounded">event_busy</span>
                        <p class="mb-0 small">No classes scheduled for today.</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="card dashboard-panel shadow-sm rounded-4 mb-3">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-primary">push_pin</span>
                    <h5 class="mb-0 fw-semibold">Notices</h5>
                </div>
                <div class="card-body p-3 p-md-4 dashboard-list-scroll">
                    @if(count($notices) === 0)
                    <div class="dashboard-empty-state">
                        <span class="material-icons material-symbols-rounded">description</span>
                        <p class="mb-0 small">No notices available.</p>
                    </div>
                    @else
                    <ul class="list-unstyled mb-0 ps-0">
                        @foreach($notices as $notice)
                        @php
                            $noticeDate = $notice->created_at ?? $notice->display_date ?? null;
                            $isNewNotice = $noticeDate && \Carbon\Carbon::parse($noticeDate)->diffInDays(now()) < 7;
                        @endphp
                        <li class="mb-2">
                            <div class="dashboard-notice-item {{ $isNewNotice ? 'dashboard-notice-item-new' : '' }}">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="notice-icon-wrap"><span class="material-icons material-symbols-rounded">description</span></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                            <span class="dashboard-notice-title">{{ $notice->notice_title }}</span>
                                            @if($isNewNotice)
                                            <span class="badge bg-danger dashboard-notice-new-tag flex-shrink-0">New</span>
                                            @endif
                                        </div>
                                        <small class="d-block dashboard-notice-date">{{ $noticeDate ? date('d M, Y', strtotime($noticeDate)) : '—' }}</small>
                                        @if($notice->document)
                                        <a href="{{ asset('storage/' . $notice->document) }}" target="_blank" class="dashboard-notice-attachment text-danger text-decoration-none">
                                            <span class="material-icons material-symbols-rounded" style="font-size: 1rem;">attach_file</span>View attachment
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>

            
        </div>

        <div class="col-lg-5">
            <div class="card dashboard-panel shadow-sm rounded-4 mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-primary">cake</span>
                    <h4 class="mb-0 fw-semibold text-primary">Today's Birthday</h4>
                    <span class="ms-auto badge rounded-pill text-bg-primary-subtle text-primary border border-primary-subtle">
                        {{ $emp_dob_data->count() }}
                    </span>
                    @if($emp_dob_data->isNotEmpty())
                    <a href="{{ route('admin.birthday-wish.index') }}" class="btn btn-sm btn-primary rounded-pill ms-2 d-inline-flex align-items-center gap-1" title="Send wishes to all">
                        <span class="material-icons material-symbols-rounded" style="font-size:14px;">send</span>
                        <span style="font-size:0.75rem;">Send Wishes</span>
                    </a>
                    <button type="button" class="btn btn-sm btn-success rounded-pill ms-1 d-inline-flex align-items-center gap-1" id="btn-quick-wish-all" title="Quick wish everyone at once">
                        <span class="material-icons material-symbols-rounded" style="font-size:14px;">celebration</span>
                        <span style="font-size:0.75rem;">Wish All</span>
                    </button>
                    @endif
                </div>
                <div class="card-body p-3 dashboard-list-scroll">
                    @if($emp_dob_data->isEmpty())
                    <div class="dashboard-empty-state">
                        <span class="material-icons material-symbols-rounded">card_giftcard</span>
                        <p class="mb-0 small">No birthdays today.</p>
                    </div>
                    @else
                    <div class="d-grid gap-2">
                        @foreach($emp_dob_data as $employee)
                        @php
                        $avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary'];
                        $avClass = $avClasses[$loop->index % count($avClasses)];
                        $photo = !empty($employee->profile_picture) ? asset('storage/' . $employee->profile_picture) : null;
                        $email = trim((string)($employee->email ?? ''));
                        $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                        $subject = rawurlencode('Happy Birthday ' . ($fullName ?: ''));
                        $body = rawurlencode("Dear " . ($fullName ?: '') . ",\n\nWishing you a very Happy Birthday!\n\nRegards,");
                        @endphp
                        <div class="card dashboard-birthday-item border-0 shadow-sm rounded-4">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start gap-3">
                                    @if($photo)
                                        <img src="{{ $photo }}" alt="" class="rounded-circle object-fit-cover flex-shrink-0 dashboard-avatar">
                                    @else
                                        <div class="rounded-circle {{ $avClass }} fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0 dashboard-avatar">
                                            {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <div class="min-w-0">
                                                <div class="dashboard-birthday-name text-truncate">{{ $fullName }}</div>
                                                <div class="dashboard-birthday-designation text-truncate">{{ $employee->designation_name }}</div>
                                            </div>

                                            <div class="dashboard-birthday-badge" title="Wish them">
                                                <span class="material-icons material-symbols-rounded" style="font-size: 16px;">cake</span>
                                                Birthday
                                            </div>
                                            {{-- Wishes count: only on your own row, and only you see it (not other viewers) --}}
                                            @if((int) ($user->user_id ?? 0) === (int) ($employee->pk ?? 0) && ($myBirthdayWishCount ?? 0) > 0)
                                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;" title="{{ $myBirthdayWishCount }} wishes received">
                                                    🎁 {{ $myBirthdayWishCount }} {{ $myBirthdayWishCount === 1 ? 'wish' : 'wishes' }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="dashboard-birthday-contact">
                                            @if($email !== '')
                                                <span class="text-truncate">
                                                    <span class="material-icons material-symbols-rounded align-middle">mail</span>
                                                    {{ $email }}
                                                </span>
                                            @endif
                                            @if(!empty($employee->mobile))
                                                <span class="text-truncate">
                                                    <span class="material-icons material-symbols-rounded align-middle">call</span>
                                                    {{ $employee->mobile }}
                                                </span>
                                            @endif
                                            @if(!empty($employee->office_extension_no))
                                                <span class="text-truncate">
                                                    <span class="material-icons material-symbols-rounded align-middle">local_phone</span>
                                                    Ext {{ $employee->office_extension_no }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="d-flex gap-2 mt-2 flex-wrap">
                                            @if(!empty($employee->mobile))
                                            @php
                                                $whatsappPhone = preg_replace('/[^0-9]/', '', $employee->mobile);
                                                if(!str_starts_with($whatsappPhone, '91') && strlen($whatsappPhone) == 10) { $whatsappPhone = '91' . $whatsappPhone; }
                                                $whatsappMsg = rawurlencode("Dear " . ($fullName ?: '') . ",\n\nWishing you a very Happy Birthday! 🎂🎉\n\nRegards,\n" . ($user->first_name ?? $user->name ?? ''));
                                            @endphp
                                            <a href="https://wa.me/{{ $whatsappPhone }}?text={{ $whatsappMsg }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-success rounded-pill d-inline-flex align-items-center gap-1"
                                               title="Send Birthday WhatsApp">
                                                <span class="material-icons material-symbols-rounded" style="font-size:14px;">chat</span>
                                                <span style="font-size:0.75rem;">WhatsApp</span>
                                            </a>
                                            @endif
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-1 btn-custom-wish"
                                                data-name="{{ $fullName }}"
                                                data-email="{{ $email }}"
                                                data-mobile="{{ $employee->mobile ?? '' }}"
                                                data-pk="{{ $employee->pk }}"
                                                title="Send Custom Message">
                                                <span class="material-icons material-symbols-rounded" style="font-size:14px;">edit</span>
                                                <span style="font-size:0.75rem;">Custom</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            @if(($upcomingBirthdays ?? collect())->isNotEmpty())
            <div class="card dashboard-panel shadow-sm rounded-4 mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-warning">upcoming</span>
                    <h6 class="mb-0 fw-semibold">Upcoming Birthdays</h6>
                    <span class="ms-auto badge rounded-pill text-bg-warning-subtle text-warning border border-warning-subtle">
                        Next 7 days
                    </span>
                </div>
                <div class="card-body p-3" style="max-height: 16rem; overflow-y: auto;">
                    <div class="d-grid gap-2">
                        @foreach($upcomingBirthdays as $upcoming)
                        @php
                            $upName = trim(($upcoming->first_name ?? '') . ' ' . ($upcoming->last_name ?? ''));
                            $upPhoto = !empty($upcoming->profile_picture) ? asset('storage/' . $upcoming->profile_picture) : null;
                            $upAvClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning', 'text-bg-danger'];
                            $upAvClass = $upAvClasses[$loop->index % count($upAvClasses)];
                        @endphp
                        <div class="d-flex align-items-center gap-2 px-2 py-2 rounded-3" style="background: rgba(var(--bs-warning-rgb), 0.05);">
                            @if($upPhoto)
                                <img src="{{ $upPhoto }}" alt="" class="rounded-circle object-fit-cover flex-shrink-0" style="width:2rem; height:2rem;">
                            @else
                                <div class="rounded-circle {{ $upAvClass }} fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:2rem; height:2rem; font-size:0.75rem;">
                                    {{ strtoupper(substr($upcoming->first_name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold small text-truncate">{{ $upName }}</div>
                                <div class="text-body-secondary" style="font-size:0.7rem;">{{ $upcoming->designation_name ?? '' }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.65rem;">
                                    🎂 {{ $upcoming->birthday_date }}
                                </span>
                                <div class="text-body-secondary" style="font-size:0.6rem;">in {{ $upcoming->days_away }} {{ $upcoming->days_away == 1 ? 'day' : 'days' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <div class="card dashboard-panel shadow-sm rounded-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-primary">calendar_month</span>
                    <h5 class="mb-0 fw-semibold">Calendar</h5>
                    <span class="ms-auto text-body-secondary fw-semibold">
                        {{ now()->format('d M Y') }}
                    </span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div id="dashboard-calendar-container">
                        <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events" theme="gov-red" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Birthday Wish Modal -->
<div class="modal fade" id="customWishModal" tabindex="-1" aria-labelledby="customWishModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary bg-opacity-10 border-0 rounded-top-4 px-4 py-3">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="customWishModalLabel">
                    <span class="material-icons material-symbols-rounded text-primary">celebration</span>
                    Send Birthday Wish
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">To</label>
                    <input type="text" class="form-control" id="wish-recipient-name" readonly>
                    <input type="hidden" id="wish-recipient-email">
                    <input type="hidden" id="wish-recipient-mobile">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Choose Template</label>
                    <select class="form-select" id="wish-template-select">
                        <option value="formal">Formal Birthday Wish</option>
                        <option value="casual">Casual Birthday Wish</option>
                        <option value="professional">Professional Birthday Wish</option>
                        <option value="custom">Write Custom Message</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Subject <small class="text-muted">(for Email)</small></label>
                    <input type="text" class="form-control" id="wish-subject" value="Happy Birthday!">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Message</label>
                    <textarea class="form-control" id="wish-message" rows="6"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold small">Send via</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send-via-email" checked>
                            <label class="form-check-label small" for="send-via-email">
                                <span class="material-icons material-symbols-rounded align-middle text-primary" style="font-size:16px;">mail</span> Email
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send-via-whatsapp">
                            <label class="form-check-label small" for="send-via-whatsapp">
                                <span class="material-icons material-symbols-rounded align-middle text-success" style="font-size:16px;">chat</span> WhatsApp
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2" id="btn-send-wish">
                    <span class="material-icons material-symbols-rounded" style="font-size:18px;">send</span> Send Wish
                </button>
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
            return "Dear " + name + ",\n\nOn the occasion of your birthday, I extend my heartfelt wishes for a wonderful year ahead. May this special day bring you joy, success, and good health.\n\nWarm regards,";
        },
        casual: function(name) {
            return "Hey " + name + "! 🎂🎉\n\nWishing you a fantastic birthday! Hope your day is filled with joy, laughter, and all things wonderful. Have an amazing year ahead!\n\nCheers!";
        },
        professional: function(name) {
            return "Dear " + name + ",\n\nWishing you a very Happy Birthday! May this new year of your life bring you continued success and fulfilment in all your endeavours.\n\nBest wishes,";
        },
        custom: function(name) {
            return "Dear " + name + ",\n\n";
        }
    };

    var currentRecipient = {};

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-custom-wish');
        if (!btn) return;
        currentRecipient = {
            name: btn.dataset.name || '',
            email: btn.dataset.email || '',
            mobile: btn.dataset.mobile || '',
            employee_pk: btn.dataset.pk || ''
        };
        document.getElementById('wish-recipient-name').value = currentRecipient.name;
        document.getElementById('wish-recipient-email').value = currentRecipient.email;
        document.getElementById('wish-recipient-mobile').value = currentRecipient.mobile;

        var emailCheckbox = document.getElementById('send-via-email');
        var whatsappCheckbox = document.getElementById('send-via-whatsapp');
        emailCheckbox.checked = currentRecipient.email !== '';
        emailCheckbox.disabled = currentRecipient.email === '';
        whatsappCheckbox.checked = false;
        whatsappCheckbox.disabled = currentRecipient.mobile === '';

        document.getElementById('wish-template-select').value = 'formal';
        document.getElementById('wish-subject').value = 'Happy Birthday ' + currentRecipient.name + '!';
        document.getElementById('wish-message').value = templates.formal(currentRecipient.name);

        var modal = new bootstrap.Modal(document.getElementById('customWishModal'));
        modal.show();
    });

    var templateSelect = document.getElementById('wish-template-select');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
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
            var sendEmail = document.getElementById('send-via-email').checked;
            var sendWhatsapp = document.getElementById('send-via-whatsapp').checked;

            if (!message) { alert('Please enter a message.'); return; }
            if (!sendEmail && !sendWhatsapp) { alert('Please select at least one channel (Email or WhatsApp).'); return; }

            var sent = false;

            if (sendEmail && currentRecipient.email) {
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

                fetch('{{ route("admin.birthday-wish.send-email") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        email: currentRecipient.email,
                        subject: subject,
                        message: message,
                        employee_pk: currentRecipient.employee_pk ? parseInt(currentRecipient.employee_pk) : null
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (sendWhatsapp && currentRecipient.mobile) {
                            openWhatsApp(currentRecipient.mobile, message);
                        }
                        bootstrap.Modal.getInstance(document.getElementById('customWishModal')).hide();
                        showToast('Birthday wish sent via email!', 'success');
                    } else {
                        alert('Failed to send email: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(function(err) {
                    alert('Error sending email: ' + err.message);
                })
                .finally(function() {
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<span class="material-icons material-symbols-rounded" style="font-size:18px;">send</span> Send Wish';
                });
                sent = true;
            }

            if (sendWhatsapp && currentRecipient.mobile && !sendEmail) {
                openWhatsApp(currentRecipient.mobile, message);
                // Send in-app notification for WhatsApp-only
                if (currentRecipient.employee_pk) {
                    var csrfToken2 = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    fetch('{{ route("admin.birthday-wish.send-notification") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken2 },
                        body: JSON.stringify({ employee_pks: [parseInt(currentRecipient.employee_pk)] })
                    }).catch(function() {});
                }
                bootstrap.Modal.getInstance(document.getElementById('customWishModal')).hide();
                showToast('Birthday wish sent via WhatsApp!', 'success');
                sent = true;
            }

            if (!sent) {
                alert('No valid email or mobile for the selected channels.');
            }
        });
    }

    function openWhatsApp(mobile, message) {
        var phone = mobile.replace(/[^0-9]/g, '');
        if (phone.length === 10) phone = '91' + phone;
        var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
        window.open(url, '_blank');
    }

    function showToast(msg, type) {
        var toastHtml = '<div class="toast align-items-center text-bg-' + (type || 'primary') + ' border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
            '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
        var div = document.createElement('div');
        div.innerHTML = toastHtml;
        document.body.appendChild(div);
        setTimeout(function() { div.remove(); }, 4000);
    }
})();

window.markAsReadDashboard = function(notificationId, clickedElement) {
    if (clickedElement && clickedElement.dataset.processing === 'true') {
        return;
    }
    if (clickedElement) {
        clickedElement.dataset.processing = 'true';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    fetch('/admin/notifications/mark-read-redirect/' + notificationId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
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
document.addEventListener('click', function (e) {
    const btn = e.target && e.target.closest ? e.target.closest('.dashboard-notification-item[data-notification-id]') : null;
    if (!btn) return;
    const id = btn.dataset.notificationId;
    if (!id) return;
    window.markAsReadDashboard(id, btn);
});

document.addEventListener('DOMContentLoaded', function() {
    const liveTimeEl = document.getElementById('dashboard-live-time');
    if (liveTimeEl) {
        const formatLiveTime = function(date) {
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return String(hours).padStart(2, '0') + ':' + minutes + ' ' + ampm;
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
                    return { ok: response.ok, data: data };
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
        const cells = comp.querySelectorAll('.calendar-cell');

        comp.addEventListener('click', function(e) {
            const td = e.target.closest('.calendar-cell');
            if (!td) return;
            const prev = comp.querySelector('.calendar-cell.is-selected');
            if (prev) prev.classList.remove('is-selected');
            td.classList.add('is-selected');
            comp.dispatchEvent(new CustomEvent('dateSelected', {
                detail: { date: td.dataset.date }
            }));
        });

        cells.forEach(function(cell) {
            cell.addEventListener('keydown', function(ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    cell.click();
                }
                const idx = Array.prototype.indexOf.call(cells, cell);
                let targetIdx = null;
                if (ev.key === 'ArrowLeft') targetIdx = idx - 1;
                if (ev.key === 'ArrowRight') targetIdx = idx + 1;
                if (ev.key === 'ArrowUp') targetIdx = idx - 7;
                if (ev.key === 'ArrowDown') targetIdx = idx + 7;
                if (targetIdx !== null && cells[targetIdx]) {
                    cells[targetIdx].focus();
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
    }

    document.querySelectorAll('.calendar-component').forEach(function(comp) {
        bindCalendarComponent(comp);
    });
});

// ── Confetti Effect for Birthday Banner ──
(function() {
    var canvas = document.getElementById('confetti-canvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var W, H, particles = [], colors = ['#f44336','#e91e63','#9c27b0','#673ab7','#3f51b5','#2196f3','#00bcd4','#4caf50','#ffeb3b','#ff9800','#ff5722','#fff'];

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
            if (p.y > H) { p.y = -10; p.x = Math.random() * W; }
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
        if (!confirm('Send birthday wishes notification to all birthday people today?')) return;

        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var allCards = document.querySelectorAll('.btn-custom-wish');
        var recipients = [];
        allCards.forEach(function(card) {
            var name = card.dataset.name || '';
            var email = card.dataset.email || '';
            var pk = card.dataset.pk || '';
            if (email && pk) {
                recipients.push({ email: email, name: name, employee_pk: parseInt(pk) });
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
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var div = document.createElement('div');
                div.innerHTML = '<div class="toast align-items-center text-bg-success border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
                    '<div class="d-flex"><div class="toast-body">🎉 ' + data.message + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
                document.body.appendChild(div);
                setTimeout(function() { div.remove(); }, 5000);
            } else {
                alert('Error: ' + (data.error || 'Unknown'));
            }
        })
        .catch(function(err) { alert('Error: ' + err.message); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-icons material-symbols-rounded" style="font-size:14px;">celebration</span><span style="font-size:0.75rem;">Wish All</span>';
        });
    });
})();
</script>
@endpush
@endsection