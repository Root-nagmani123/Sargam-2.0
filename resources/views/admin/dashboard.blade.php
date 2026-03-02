@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
/* ========================================
   GIGW-COMPLIANT DASHBOARD STYLES
   Following Government of India Web Guidelines
   ======================================== */

/* ACCESSIBILITY & TYPOGRAPHY - GIGW Compliant */
:root {
    --primary-color: #004a93;
    --secondary-color: #dc3545;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --text-primary: #1a1a1a;
    --text-secondary: #4a4a4a;
    --text-muted: #6c757d;
    --border-color: #dee2e6;
    --bg-light: #f8f9fa;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.16);
    --transition-base: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --border-radius-base: 12px;
    --border-radius-lg: 16px;
}

.dashboard-stat-card {
    border: 0;
    border-left: 3px solid var(--bs-border-color);
    border-radius: 0.45rem;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.dashboard-stat-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(16, 24, 40, 0.1);
}

.dashboard-stat-card .card-body {
    padding: 0.7rem 0.8rem;
}

.dashboard-stat-value {
    font-size: clamp(1.55rem, 1.9vw, 2.1rem);
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
    border-radius: 0.65rem;
    background: #f2f2f6;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.07);
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

.bg-soft-blue {
    background: #DCE7EF;
}

.user-card {
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: .2s ease-in-out;
}

.user-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.profile-img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
}

.user-role {
    font-size: 14px;
    color: #555;
    font-weight: 500;
    margin-top: 2px;
}

.user-email,
.user-phone {
    font-size: 14px;
    color: #333;
    letter-spacing: 0.2px;
}

.birthday-card {
    border-radius: 18px;
    padding: 18px 22px;
    display: block;
    box-shadow: 0 0 12px rgba(0, 0, 0, 0.12);
    min-height: 150px;
}

.birthday-photo {
    width: 68px;
    height: 67px;
    border-radius: 50%;
    object-fit: cover;
}

.emp-name {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    line-height: 18px;
    color: #000;
}

.emp-desg {
    margin: 2px 0 0;
    font-size: 13px;
    font-weight: 400;
    color: #444;
    line-height: 16px;
    letter-spacing: 0.3px;
}

.emp-email,
.emp-phone {
    margin: 2px 0;
    font-size: 13px;
    font-weight: 400;
    color: #2d2d2d;
    letter-spacing: 0.4px;
}
</style>
<style>
/* ================================
   MODERN UI ENHANCEMENTS (GIGW)
    color: #fff;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
}
.dashboard-welcome h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem; }
.dashboard-welcome .text-white { font-size: 0.9rem; }

/* --- GIGW Compliant Focus Styles --- */
*:focus-visible {
    outline: 3px solid var(--primary-color);
    outline-offset: 2px;
}

/* --- Improved Headings Spacing --- */
h3.fw-bold {
    margin-top: 30px;
}

/* --- Metric Cards Icons --- */
.stat-card-icon-modern img {
    width: 38px !important;
    height: 38px !important;
}

.stat-card-icon-modern {
    border-radius: 12px;
    padding: 10px !important;
}

.stat-card-value-modern {
    font-size: 1.8rem !important;
    font-weight: 800 !important;
}

/* --- Modern Stat Card Enhancements --- */
.stat-card-modern {
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.stat-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.stat-card-modern:hover::before {
    left: 100%;
}

/* Icon Background Colors with Gradients */
.icon-bg-blue {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
}

.icon-bg-green {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.icon-bg-yellow {
    background: linear-gradient(135deg, #fff9c4 0%, #fff59d 100%);
}

.icon-bg-purple {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
}

/* Stat Card Label */
.stat-card-label-modern {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin-bottom: 8px;
}

/* Add subtle pulse animation to stat values */
@keyframes pulseValue {

    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.05);
    }
}

.stat-card-modern:hover .stat-card-value-modern {
    animation: pulseValue 0.6s ease;
    color: var(--primary-color);
}

/* Modern card style matching the reference image */
.stat-card.clean-style {
    display: flex;
    align-items: center;
    gap: 18px;
    background: #ffffff;
    padding: 20px 22px;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.stat-card.clean-style:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.10);
}

/* Icon container */
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon img {
    width: 28px;
    height: 28px;
}

/* Colors */
.icon-blue {
    background: #e5edff;
}

.icon-green {
    background: #e6f7ec;
}

.icon-yellow {
    background: #fff4e2;
}

.icon-purple {
    background: #f1ebff;
}

/* Texts */
.stat-label {
    font-size: 15px;
    font-weight: 600;
    color: #4a4a4a;
}

.stat-value {
    font-size: 26px;
    font-weight: 700;
    color: #000;
    margin-top: 3px;
}

.stat-change {
    font-size: 13px;
    margin-top: 3px;
}

table>thead {
    background-color: #ffffff !important;
}

/* Compact Timetable Card Design - Shows 2 items at a time */
.timetable-container {
    max-height: 240px; /* Shows exactly 2 cards (110px each + 8px margin) */
    overflow-y: auto;
    padding-right: 5px;
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 transparent;
}

.timetable-container::-webkit-scrollbar {
    width: 6px;
}

.timetable-container::-webkit-scrollbar-track {
    background: transparent;
    border-radius: 3px;
}

.timetable-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.timetable-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.timetable-card {
    background: #fff;
    border-left: 4px solid #dc3545;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
    height: 110px;
    display: flex;
    flex-direction: column;
}

.timetable-card:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    transform: translateX(2px);
    border-left-color: #c82333;
}

.timetable-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.timetable-time-badge {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}

.timetable-sno {
    background: #f8f9fa;
    color: #6c757d;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 600;
}

.timetable-topic {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 6px 0;
    line-height: 1.3;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.timetable-details {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 0.7rem;
    color: #555;
    margin-top: auto;
}

.timetable-detail-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.timetable-detail-item i {
    font-size: 14px;
    color: #6c757d;
}
</style>

@php
$user = Auth::user();
$notifications = $user ? notification()->getNotifications($user->user_id, 10) : collect();
$notices = get_notice_notification_by_role();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$userName = $user ? ($user->first_name ?? $user->name ?? 'User') : 'User';
@endphp

<div class="container-fluid px-3 px-md-4 py-3 admin-dashboard-surface">
    <div class="dashboard-welcome d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="mb-0 text-white">{{ $greeting }}, {{ $userName }}</h2>
            <div class="text-white">{{ now()->format('l, d F Y') }}</div>
        </div>
        <div class="d-none d-sm-block">
            <i class="bi bi-calendar3 me-1"></i>
            <span class="small">{{ now()->format('h:i A') }}</span>
        </div>
    </div>

    <div class="row g-4 mb-4">

        <!-- Total Active Courses -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.active_course') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-blue">
                        <img src="{{ asset('images/groups.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Total Active Courses</div>
                        <div class="stat-value">{{ $totalActiveCourses }}</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Upcoming Courses -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.incoming_course') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-green">
                        <img src="{{ asset('images/teachers.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Upcoming Courses</div>
                        <div class="stat-value">{{ $upcomingCourses }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.upcoming_events') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-green">
                        <img src="{{ asset('images/teachers.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Upcoming Events</div>
                        <div class="stat-value">2</div>
                    </div>
                </div>
            </a>
        </div>
        <!-- Total Guest Faculty -->

        @if(hasRole('Student-OT'))
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('ot.mdo.escrot.exemption.view') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-purple">
                        <img src="{{ asset('images/attendance.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">OT MDO/Escort</div>
                        <div class="stat-value">{{ $MDO_count }}</div>
                    </div>
                </div>
            </a>
        </div>
        @else
        <!-- Total Inhouse Faculty -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.inhouse_faculty') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-purple">
                        <img src="{{ asset('images/attendance.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Total Inhouse Faculty</div>
                        <div class="stat-value">{{ $total_internal_faculty }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty'))
        <!-- Total Sessions -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.sessions') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-blue">
                        <img src="{{ asset('images/attendance.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Session Details</div>
                        <div class="stat-value">{{ $totalSessions }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(isset($isCCorACC) && $isCCorACC)
        <!-- Total Students - Only for CC/ACC -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.students') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-green">
                        <img src="{{ asset('images/classes.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value">{{ $totalStudents }}</div>
                    </div>
                </div>
            </a>
        </div>

        @endif

        @if(hasRole('Admin') || hasRole('Training-Induction') || (isset($isCCorACC) && $isCCorACC))
        <!-- Participant History - Full academic records across all courses -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.students') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-blue d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-clock-history text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Participant History</div>
                            <div class="stat-value-modern fw-bold text-dark">View Full</div>
                            <small class="text-muted">Academic, notices, memos, attendance</small>
                        </div>
                        <span class="stat-icon"><i class="bi bi-person-vcard"></i></span>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(isset($isCCorACC) && $isCCorACC)
        <!-- Total Students - Only for CC/ACC -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.students') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-green">
                        <img src="{{ asset('images/classes.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value">{{ $totalStudents }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(isset($isCCorACC) && $isCCorACC)
        <!-- Total Students - Only for CC/ACC -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.students') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-green">
                        <img src="{{ asset('images/classes.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value">{{ $totalStudents }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endif


    </div>

    <div class="row g-4 mb-4">
        <!-- LEFT CONTENT -->
        <div class="col-lg-7">
            <div class="content-card-modern" style="height:700px; overflow-y:auto;">
                <div class="content-card-header-modern">
                    <h2>Admin & Campus Summary</h2>
                </div>
                <div class="content-card-body-modern">
                    <!-- Admin Summary / Notifications -->
                    <section aria-labelledby="{{ hasRole('Admin') ? 'admin-summary-title' : 'notifications-title' }}"
                        class="mb-5">
                        <h3 id="{{ hasRole('Admin') ? 'admin-summary-title' : 'notifications-title' }}"
                            class="section-header-modern" style="font-size:1.25rem;line-height:1.4;">
                            {{ hasRole('Admin') ? 'Admin Summary' : 'Notifications' }}
                        </h3>

                        <div class="divider-modern"></div>

                        <div class="content-text">
                            @php
                            $user = Auth::user();
                            $notifications = $user ? notification()->getNotifications($user->user_id, 10) : collect();
                            @endphp

                            <script>
                            // Define markAsRead function for Admin Summary notifications - Define early to ensure availability
                            if (typeof window.markAsRead === 'undefined' || window.markAsReadDashboard === undefined) {
                                window.markAsReadDashboard = function(notificationId, clickedElement) {
                                    console.log('markAsReadDashboard called with notificationId:', notificationId);

                                    // Prevent multiple clicks
                                    if (clickedElement && clickedElement.dataset.processing === 'true') {
                                        return;
                                    }
                                    if (clickedElement) {
                                        clickedElement.dataset.processing = 'true';
                                    }

                                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                                        ?.getAttribute('content') || '{{ csrf_token() }}';

                                    fetch('/admin/notifications/mark-read-redirect/' + notificationId, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': csrfToken
                                            }
                                        })
                                        .then(response => {
                                            console.log('Response status:', response.status);
                                            return response.json().then(data => {
                                                if (!response.ok) {
                                                    throw new Error(data.error ||
                                                        'Failed to mark notification as read');
                                                }
                                                return data;
                                            });
                                        })
                                        .then(data => {
                                            console.log('Response data:', data);
                                            if (data.success && data.redirect_url) {
                                                window.location.href = data.redirect_url;
                                            } else if (data.success) {
                                                location.reload();
                                            } else {
                                                console.error('Failed to mark notification as read. Response:',
                                                    data);
                                                if (clickedElement) {
                                                    clickedElement.dataset.processing = 'false';
                                                }
                                                const errorMsg = data.error || 'Unknown error occurred';
                                                alert('Failed to mark notification as read: ' + errorMsg);
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            if (clickedElement) {
                                                clickedElement.dataset.processing = 'false';
                                            }
                                            alert('An error occurred: ' + (error.message || 'Unknown error'));
                                        });
                                };
                                // Also set as markAsRead for compatibility
                                window.markAsRead = window.markAsReadDashboard;
                            }
                            </script>

                            @if($notifications->isEmpty())
                            <p>No notifications available.</p>
                            @else
                            <ul style="list-style-type: disc; padding-left: 20px;">
                                @foreach($notifications as $notification)
                                <li style="cursor: pointer;"
                                    onclick="window.markAsReadDashboard({{ $notification->pk }}, this)">
                                    {{ $notification->message }}</li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </section>

                    <!-- Campus Summary -->
                    <section aria-labelledby="campus-summary-title" style="overflow-y:auto; max-height:250px;">
                        <h2 id="campus-summary-title"
                            style="color: #1a1a1a !important;font-size:24px;line-height:28px;">
                            Campus Summary
                        </h2>

                        <div class="line w-100 my-4"></div>

                        <div class="content-text">
                            <ul style="list-style-type: disc; padding-left: 20px;">
                                <li>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                    access
                                    to various administrative functions.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Today's Timetable -->
                    @if(hasRole('Student-OT') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
                    <section aria-labelledby="timetable-title" style="margin-top: 20px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 id="timetable-title" style="font-size: 1.2rem; font-weight: 600; margin: 0; color: #1a1a1a;">
                                Today's Classes
                            </h2>
                            <a href="{{ route('calendar.index') }}" class="btn btn-outline-primary btn-sm" style="font-size: 0.75rem; padding: 4px 12px;">View All</a>
                        </div>

                        <div class="line w-100 my-2"></div>

                        @if($todayTimetable && $todayTimetable->isNotEmpty())
                        <div class="timetable-container">
                            @foreach($todayTimetable as $entry)
                            <div class="timetable-card">
                                <div class="timetable-header">
                                    <span class="timetable-time-badge">{{ $entry['session_time'] }}</span>
                                    <span class="timetable-sno">#{{ $entry['sno'] }}</span>
                                </div>
                                <h5 class="timetable-topic">{{ $entry['topic'] }}</h5>
                                <div class="timetable-details">
                                    <div class="timetable-detail-item">
                                        <i class="material-icons material-symbols-rounded">person</i>
                                        <span>{{ $entry['faculty_name'] }}</span>
                                    </div>
                                    <div class="timetable-detail-item">
                                        <i class="material-icons material-symbols-rounded">location_on</i>
                                        <span>{{ $entry['session_venue'] }}</span>
                                    </div>
                                    <div class="timetable-detail-item">
                                        <i class="material-icons material-symbols-rounded">event</i>
                                        <span>{{ $entry['session_date'] }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="alert alert-info" style="padding: 10px; margin: 0; border-radius: 6px; font-size: 0.85rem;">
                            <p class="mb-0" style="text-align: center; color: #6c757d;">
                                <i class="material-icons material-symbols-rounded" style="font-size: 18px; vertical-align: middle; margin-right: 5px;">event_busy</i>
                                No classes scheduled for today
                            </p>
                        </div>
                        @endif
                    </section>
                    @endif
                </div>
            </div>
        </div>

        <!-- RIGHT NOTICE PANEL -->
        <div class="col-lg-5">

            <div class="card shadow-sm border-0 rounded-4 h-100" style="max-height:700px; overflow-y:auto;">
                <!-- Notice Header -->
                <div class="card-header bg-danger text-white rounded-top-4 py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center text-white">
                        <i class="material-icons material-symbols-rounded me-2">campaign</i>
                        Notices
                    </h5>
                </div>

                <!-- Notice Body -->
                <div class="card-body" style="overflow-y:auto;">
                    @php $notices = get_notice_notification_by_role() @endphp
                    @foreach($notices as $notice)
                    @php //print_r($notice); @endphp
                    <div class="mb-4 pb-2 border-bottom">
                        <h6 class="fw-bold">{{ $notice->notice_title }}</h6>

                        <small class="text-muted">Posted on:
                            {{ date('d M, Y', strtotime($notice->created_at)) }}</small>
                        @if($notice->document)
                        <div class="mt-2">
                            <a href="{{ asset('storage/' . $notice->document) }}" target="_blank"
                                class="btn btn-sm btn-outline-primary  text-center">
                                <i class="material-icons material-symbols-rounded me-1">attachment</i>View Attachment
                            </a>
                        </div>
                        @endif

                    </div>
                    @endforeach


                </div>

            </div>
        </div>

        <h3 class="fw-bold">Today Birthday's</h3>
        <hr class="my-2">
        <div class="row g-4">
            <!-- LEFT SIDE: Birthday Cards -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        @if($emp_dob_data->isEmpty())
                        <p class="text-center">No Birthdays Today</p>
                        @else

                        <div class="row g-4">
                            @php
                            $colors = ['#ebf5e8', '#fef5e7', '#ccdbe9', '#ecedf8', '#f8e7e8', '#f2f2f2'];
                            @endphp

                            @foreach($emp_dob_data as $employee)
                            <div class="col-6 mb-4">
                                <div class="birthday-card"
                                    style="background: {{ $colors[$loop->index % count($colors)] }};">

                                    <div class="d-flex align-items-center gap-3">

                                        @php
                                        $photo = !empty($employee->profile_picture)
                                        ? asset('storage/' . $employee->profile_picture)
                                        :
                                        'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
                                        @endphp

                                        <!-- IMAGE -->
                                        <img src="{{ $photo }}" class="birthday-photo" alt="">

                                        <!-- NAME + DESIGNATION -->
                                        <div class="flex-grow-1">
                                            <h5 class="emp-name">
                                                {{ strtoupper($employee->first_name) }}
                                                {{ strtoupper($employee->last_name) }}
                                            </h5>

                                            <p class="emp-desg">
                                                {{ strtoupper($employee->designation_name) }}
                                            </p>
                                        </div>

                                    </div>

                                    <!-- BOTTOM DETAILS -->
                                    <div class="mt-2 ps-2">
                                        <p class="emp-email">{{ $employee->email }}</p>
                                        <p class="emp-phone">{{ $employee->mobile }}</p>
                                    </div>

                                </div>
                            </div>
                            @endforeach
                        </div>

                        @endif
                    </div>
                </div>
            </div>
            <!-- RIGHT SIDE: Calendar -->
            <div class="col-lg-5">
                <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events"
                    theme="gov-red" />

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

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.calendar-component').forEach(function(comp) {
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
                const url = new URL(window.location.href);
                url.searchParams.set('year', this.value);
                url.searchParams.set('month', monthSel.value);
                window.location.href = url.toString();
            });

            monthSel.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('year', yearSel.value);
                url.searchParams.set('month', this.value);
                window.location.href = url.toString();
            });
        }
    });
});
</script>
@endpush
@endsection
