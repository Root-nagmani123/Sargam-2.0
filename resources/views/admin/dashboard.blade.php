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

<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-9">
            <div class="container my-4">
                <div class="row g-4 mb-4">
                    <!-- Total Active Courses -->
                    <div class="col-md-3">
                        <div class="stat-card-modern">
                            <div class="stat-card-icon-modern icon-bg-blue">
                                <img src="{{ asset('images/groups.svg') }}" alt="Total Active Courses Icon">
                            </div>
                            <div class="stat-card-label-modern">Total Active Courses</div>
                            <h3 class="stat-card-value-modern">{{ $totalActiveCourses }}</h3>
                        </div>
                    </div>

                    <!-- Upcoming Courses -->
                    <div class="col-md-3">
                        <div class="stat-card-modern">
                            <div class="stat-card-icon-modern icon-bg-green">
                                <img src="{{ asset('images/teachers.svg') }}" alt="Upcoming Courses Icon">
                            </div>
                            <div class="stat-card-label-modern">Upcoming Courses</div>
                            <h3 class="stat-card-value-modern">{{ $upcomingCourses }}</h3>
                        </div>
                    </div>

                    <!-- Total Guest Faculty -->
                    <div class="col-md-3">
                        <div class="stat-card-modern">
                            <div class="stat-card-icon-modern icon-bg-yellow">
                                <img src="{{ asset('images/classes.svg') }}" alt="Total Guest Faculty Icon">
                            </div>
                            <div class="stat-card-label-modern">Total Guest Faculty</div>
                            <h3 class="stat-card-value-modern">{{ $total_guest_faculty }}</h3>
                        </div>
                    </div>

                    <!-- Total Inhouse Faculty -->
                    <div class="col-md-3">
                        <div class="stat-card-modern">
                            <div class="stat-card-icon-modern icon-bg-purple">
                                <img src="{{ asset('images/attendance.svg') }}" alt="Total Inhouse Faculty Icon">
                            </div>
                            <div class="stat-card-label-modern">Total Inhouse Faculty</div>
                            <h3 class="stat-card-value-modern">{{ $total_internal_faculty }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-3 text-end">
            <x-dropdown label="Select Teacher" :items="['Amit', 'Rohan', 'Shreya']" />
            <x-dropdown label="Select Teacher" :items="['Amit', 'Rohan', 'Shreya']" />
            <x-dropdown label="Select Teacher" :items="['Amit', 'Rohan', 'Shreya']" />

        </div>

    </div>
    <div class="row g-4 mb-4">
        <!-- LEFT CONTENT -->
        <div class="col-lg-7">
            <div class="content-card-modern" style="max-height:650px;">
                <div class="content-card-header-modern">
                    <h2>Admin & Campus Summary</h2>
                </div>
                <div class="content-card-body-modern">
                    <!-- Admin Summary / Notifications -->
                    <section aria-labelledby="{{ hasRole('Admin') ? 'admin-summary-title' : 'notifications-title' }}" class="mb-5">
                        <h3 id="{{ hasRole('Admin') ? 'admin-summary-title' : 'notifications-title' }}" class="section-header-modern"
                            style="font-size:1.25rem;line-height:1.4;">
                            {{ hasRole('Admin') ? 'Admin Summary' : 'Notifications' }}
                        </h3>

                        <div class="divider-modern"></div>

                        <div class="content-text">




                            @php
                                $user = Auth::user();
                                $notifications = $user ? notification()->getNotifications($user->user_id, 10) : collect();
                            @endphp
                            
                            @if($notifications->isEmpty())
                                <p class="text-muted">No notifications available.</p>
                            @else
                                @foreach($notifications as $notification)
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-1" style="font-size: 1rem; color: {{ $notification->is_read ? '#6c757d' : '#1a1a1a' }};">
                                                {{ $notification->title }}
                                                @if(!$notification->is_read)
                                                    <span class="badge bg-primary ms-2" style="font-size: 0.7rem;">New</span>
                                                @endif
                                            </h6>
                                            <small class="text-muted">{{ $notification->created_at->format('d M, Y') }}</small>
                                        </div>
                                        <p class="mb-1" style="font-size: 0.9rem; line-height: 1.5; color: #333;">
                                            {{ $notification->message }}
                                        </p>
                                        <small class="text-muted">
                                            <span class="badge bg-secondary">{{ $notification->module_name }}</span>
                                        </small>
                                    </div>
                                @endforeach
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
                </div>
            </div>
        </div>

        <!-- RIGHT NOTICE PANEL -->
        <div class="col-lg-5">

            <div class="card shadow-sm border-0 rounded-4 h-100">
 <!-- Notice Header -->
                    <div class="card-header bg-danger text-white rounded-top-4 py-3">
                        <h5 class="mb-0 fw-bold d-flex align-items-center text-white">
                            <i class="bi bi-megaphone-fill me-2"></i>
                            Notices
                        </h5>
                    </div>

                    <!-- Notice Body -->
                    <div class="card-body" style="max-height:600px; overflow-y:auto;">
                        @php $notices = get_notice_notification_by_role() @endphp
                        @foreach($notices as $notice)
                        <div class="mb-4 pb-2 border-bottom">
                            <h6 class="fw-bold">{{ $notice->notice_title }}</h6>
                            <p class="mb-1" style="font-size: 14px; line-height: 1.5; color: #333;">
                                {!! Str::limit($notice->description, 100) !!}
                            </p>
                            <small class="text-muted">Posted on:
                                {{ date('d M, Y', strtotime($notice->created_at)) }}</small>
                            @if($notice->document)
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $notice->document) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>View Attachment
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
            <div class="col-7">
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
            <div class="col-5">
                <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events"
                    theme="gov-red" />

            </div>
        </div>
    </div>


</div>



@push('scripts')
<script>
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