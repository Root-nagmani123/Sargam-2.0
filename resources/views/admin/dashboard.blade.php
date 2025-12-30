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

/* GIGW: Minimum 4.5:1 contrast ratio for text */
.calendar-component thead th {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    color: var(--text-primary);
    font-weight: 600;
}

.line {
    height: 2px;
    background: linear-gradient(90deg, #e0e0e0 0%, #d0d0d0 100%);
    border-radius: 2px;
}

.content-text p {
    font-size: 1rem;
    line-height: 1.75;
    color: var(--text-primary);
    margin-bottom: 1rem;
    letter-spacing: 0.02em;
}

/* SMOOTH SCROLLING - Enhanced UX */
.card-body {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 transparent;
    scroll-behavior: smooth;
}

.card-body::-webkit-scrollbar {
    width: 8px;
}

.card-body::-webkit-scrollbar-track {
    background: transparent;
    border-radius: 4px;
}

.card-body::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #c1c1c1 0%, #a8a8a8 100%);
    border-radius: 4px;
    transition: var(--transition-base);
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #a8a8a8 0%, #909090 100%);
}

/* HIGH CONTRAST FOR ACCESSIBILITY (GIGW Standard) */
h1,
h2,
h3,
h4,
h5,
h6 {
    color: var(--text-primary) !important;
    font-weight: 700;
    letter-spacing: -0.02em;
}

h2 {
    font-size: 1.5rem;
    line-height: 1.3;
}

h3 {
    font-size: 1.25rem;
    line-height: 1.4;
}

/* FOCUS STATES - WCAG 2.1 Compliant */
a:focus,
button:focus,
input:focus,
select:focus,
textarea:focus,
[tabindex]:focus {
    outline: 3px solid #004a93;
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(0, 74, 147, 0.15);
}
</style>
<style>
.user-card {
    border-radius: 20px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
}

.user-card img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #1a1a1a;
}

.user-role {
    font-size: 15px;
    color: #555;
    margin-bottom: 8px;
}

.user-email,
.user-phone {
    font-size: 14px;
    color: #333;
    margin: 0;
}

/* Soft pastel card backgrounds */
.bg-soft-green {
    background: #E6F2E8;
}

.bg-soft-beige {
    background: #EFE7DC;
}

.bg-soft-lavender {
    background: #E3E1EA;
}

.bg-soft-rose {
    background: #F0E0E0;
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
================================ */

/* --- Global Card Styling --- */
.stat-card-modern,
.content-card-modern,
.card,
.birthday-card {
    border-radius: 18px !important;
    background: #ffffff;
    box-shadow: var(--shadow-sm) !important;
    border: 1px solid #e8e8e8;
    transition: var(--transition-base);
}

.stat-card-modern:hover,
.content-card-modern:hover,
.card:hover {
    box-shadow: var(--shadow-md) !important;
    transform: translateY(-3px);
}

/* --- Modern Section Headers --- */
.section-header-modern {
    font-size: 1.2rem;
    font-weight: 700;
    padding-bottom: 4px;
    color: var(--text-primary);
    border-left: 4px solid var(--primary-color);
    padding-left: 10px;
}

/* --- Divider --- */
.divider-modern {
    width: 100%;
    height: 1px;
    background: #e5e5e5;
    margin: 16px 0;
}

/* --- Notice Sidebar --- */
.card-header.bg-danger {
    border-radius: 18px 18px 0 0 !important;
    padding: 14px 20px;
}

.card-body {
    padding: 20px !important;
}

.card-body p {
    color: #202020;
    line-height: 1.6;
}

/* --- Notice Items --- */
.notice-item {
    padding: 12px 14px;
    border-radius: 12px;
    transition: var(--transition-base);
    background: #fafafa;
}

.notice-item:hover {
    background: #eff5ff;
    border-left: 4px solid var(--primary-color);
}

/* --- Calendar Card --- */
.calendar-component {
    border-radius: 20px;
    background: #fff;
    box-shadow: var(--shadow-sm);
    border: 1px solid #e6e6e6;
    padding: 18px;
}

.calendar-component table {
    border-collapse: separate !important;
    border-spacing: 4px !important;
}

/* Highlight Active Day */
.calendar-cell.is-selected {
    background: var(--primary-color) !important;
    color: #fff !important;
    font-weight: 600;
}

/* --- Dropdown Alignment --- */
.x-dropdown {
    margin-bottom: 10px;
    display: inline-block;
    width: 100%;
}

/* --- Teacher Dropdown Column --- */
.col-3 .x-dropdown {
    width: 100%;
}

/* --- Birthday Cards Grid --- */
.birthday-card {
    min-height: 160px;
    padding: 16px 20px !important;
    transition: var(--transition-base);
}

.birthday-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

/* Employee name */
.emp-name {
    font-size: 1.1rem !important;
    font-weight: 700;
}

/* --- Smooth Scrolling --- */
.content-card-body-modern {
    scrollbar-width: thin;
    scrollbar-color: var(--primary-color) #f1f1f1;
}

.content-card-body-modern::-webkit-scrollbar {
    width: 8px;
}

.content-card-body-modern::-webkit-scrollbar-thumb {
    background: #c9c9c9;
    border-radius: 10px;
}

.content-card-body-modern::-webkit-scrollbar-thumb:hover {
    background: #a3a3a3;
}

/* --- Buttons Modernized --- */
.btn-outline-primary {
    border-radius: 10px;
    padding: 6px 12px;
    border-width: 1.5px;
}

.btn-outline-primary:hover {
    background: var(--primary-color);
    color: #fff;
}

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
</style>


<div class="container-fluid">

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
        @if(hasRole('Student-OT'))
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('medical.exception.ot.view') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-yellow">
                        <img src="{{ asset('images/classes.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Medical Exception</div>
                        <div class="stat-value">{{ $exemptionCount }}</div>
                    </div>
                </div>
            </a>
        </div>
        @else
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.guest_faculty') }}">
                <div class="stat-card clean-style">
                    <div class="stat-icon icon-yellow">
                        <img src="{{ asset('images/classes.svg') }}" alt="">
                    </div>
                    <div>
                        <div class="stat-label">Total Guest Faculty</div>
                        <div class="stat-value">{{ $total_guest_faculty }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endif
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


    </div>

    <div class="row g-4 mb-4">
        <!-- LEFT CONTENT -->
        <div class="col-lg-7">
            <div class="content-card-modern" style="height:700px; overflow-y:auto;">
                <div class="content-card-header-modern">
                    @if(hasRole('Student-OT') || hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Staff'))
                    <h2>Notification & Time Table</h2>
                    @endif
                    @if(hasRole('Admin') || hasRole('Training'))
                    <h2>Admin & Campus Summary</h2>
                    @endif
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
                    @if(hasRole('Admin') || hasRole('Training'))
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

                    @endif

                    @if(hasRole('Student-OT') || hasRole('Training') || hasRole('Admin') || hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Staff'))
                    <section class="h-100">
                        <div class="row">
                            <div class="col-6"><h4 id="campus-summary-title"
                            style="color: #1a1a1a !important;font-size:24px;line-height:28px;">
                            Time Table
                        </h4></div>
                        <div class="col-6 text-end">
                            <a href="{{ route('calendar.index') }}" class="btn btn-outline-primary" id="calendar">View All</a>
                        </div>
                        </div>
                        
                        <div class="divider-modern"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap align-middle">
                                <tr>
                                    <th style="color: #af2910 !important;">S.No.</th>
                                    <th style="color: #af2910 !important;">Topics</th>
                                    <th style="color: #af2910 !important;">Date</th>
                                    <th style="color: #af2910 !important;">Time</th>
                                    <th style="color: #af2910 !important;">Venue</th>
                                    <th style="color: #af2910 !important;">Faculty</th>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>Contribution of Manufacturing for Viksit Bharat (C&I-2)</td>
                                    <td>20-06-2024</td>
                                    <td>10:00 AM - 11:00 AM</td>
                                    <td>Room 1</td>
                                    <td>Ms. Anjali</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>Contribution of Manufacturing for Viksit Bharat (C&I-2)</td>
                                    <td>20-06-2024</td>
                                    <td>10:00 AM - 11:00 AM</td>
                                    <td>Room 1</td>
                                    <td>Ms. Anjali</td>
                                </tr>
                            </table>
                        </div>
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
// Define markAsRead function for Admin Summary notifications - Always override to ensure it works
window.markAsRead = function(notificationId, clickedElement) {
    console.log('markAsRead called with notificationId:', notificationId);

    // Prevent multiple clicks
    if (clickedElement && clickedElement.dataset.processing === 'true') {
        console.log('Already processing, ignoring click');
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
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Failed to mark notification as read');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success && data.redirect_url) {
                // Notification remains visible until redirect happens
                window.location.href = data.redirect_url;
            } else if (data.success) {
                // If no redirect URL, just reload (notification will remain visible if not filtered)
                location.reload();
            } else {
                console.error('Failed to mark notification as read:', data.error || 'Unknown error');
                if (clickedElement) {
                    clickedElement.dataset.processing = 'false';
                }
                alert('Failed to mark notification as read. Please try again.');
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

// Lightweight calendar interactions (vanilla JS)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.calendar-component').forEach(function(comp) {
        const yearSel = comp.querySelector('.calendar-year');
        const monthSel = comp.querySelector('.calendar-month');
        const cells = comp.querySelectorAll('.calendar-cell');


        // Click a date -> emit custom event
        comp.addEventListener('click', function(e) {
            const td = e.target.closest('.calendar-cell');
            if (!td) return;
            const prev = comp.querySelector('.calendar-cell.is-selected');
            if (prev) prev.classList.remove('is-selected');
            td.classList.add('is-selected');


            const date = td.dataset.date;
            comp.dispatchEvent(new CustomEvent('dateSelected', {
                detail: {
                    date
                }
            }));
        });


        // keyboard support for cells
        cells.forEach(function(cell) {
            cell.addEventListener('keydown', function(ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    cell.click();
                }
                // Arrow navigation (left/right/up/down)
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


        // Change month/year -> navigate by query params (simple behavior)
        yearSel.addEventListener('change', function() {
            const y = this.value;
            const m = monthSel.value;
            const url = new URL(window.location.href);
            url.searchParams.set('year', y);
            url.searchParams.set('month', m);
            window.location.href = url.toString();
        });
        monthSel.addEventListener('change', function() {
            const y = yearSel.value;
            const m = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('year', y);
            url.searchParams.set('month', m);
            window.location.href = url.toString();
        });
    });
});
</script>

@endpush
@endsection