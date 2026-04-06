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
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

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

@push('scripts')
<script>
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
</script>
@endpush
@endsection
