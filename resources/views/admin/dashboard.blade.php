@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
.admin-dashboard-surface {
    background: #f5f6f8;
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

.dashboard-welcome {
    background: var(--bs-primary);
    border-radius: 0.65rem;
    color: #fff;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
}
.dashboard-welcome h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem; }
.dashboard-welcome .text-white { font-size: 0.9rem; }

.dashboard-stat-card .stat-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    opacity: 0.9;
}
.dashboard-stat-card.card-blue .stat-icon { background: rgba(var(--bs-primary-rgb), 0.2); color: var(--bs-primary); }
.dashboard-stat-card.card-green .stat-icon { background: rgba(var(--bs-success-rgb), 0.2); color: var(--bs-success); }
.dashboard-stat-card.card-amber .stat-icon { background: rgba(var(--bs-warning-rgb), 0.2); color: var(--bs-warning); }
.dashboard-stat-card.card-rose .stat-icon { background: rgba(var(--bs-danger-rgb), 0.2); color: var(--bs-danger); }

.dashboard-stat-card .stat-link-hint {
    font-size: 0.7rem;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.dashboard-stat-card:hover .stat-link-hint { opacity: 1; }

.dashboard-empty-state {
    text-align: center;
    padding: 1.5rem 1rem;
    color: var(--bs-secondary);
}
.dashboard-empty-state .bi { font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5; }

.dashboard-tweet-item {
    border-left: 3px solid var(--bs-primary);
    padding-left: 0.75rem;
    margin-bottom: 0.75rem;
}
.dashboard-tweet-item:last-child { margin-bottom: 0; }

.dashboard-panel .card-header .badge { font-size: 0.75rem; }

.dashboard-stat-card:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
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

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.active_course') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-blue h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Total Active Courses</p>
                            <div class="dashboard-stat-value fw-semibold text-primary">{{ $totalActiveCourses }}</div>
                            <span class="stat-link-hint text-primary">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.incoming_course') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-green h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Upcoming Courses</p>
                            <div class="dashboard-stat-value fw-semibold text-success">{{ $upcomingCourses }}</div>
                            <span class="stat-link-hint text-success">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-calendar-event"></i></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.upcoming_events') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-amber h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Upcoming Events</p>
                            <div class="dashboard-stat-value fw-semibold text-warning-emphasis">2</div>
                            <span class="stat-link-hint text-warning">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-megaphone"></i></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6">
            @if(hasRole('Student-OT'))
            <a href="{{ route('medical.exception.ot.view') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-rose h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Medical Exception</p>
                            <div class="dashboard-stat-value fw-semibold text-danger">{{ $exemptionCount }}</div>
                            <span class="stat-link-hint text-danger">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-heart-pulse"></i></span>
                    </div>
                </div>
            </a>
            @else
            <a href="{{ route('admin.dashboard.guest_faculty') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-rose h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Total Guest Faculty</p>
                            <div class="dashboard-stat-value fw-semibold text-danger">{{ $total_guest_faculty }}</div>
                            <span class="stat-link-hint text-danger">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-person-badge"></i></span>
                    </div>
                </div>
            </a>
            @endif
        </div>

        <div class="col-xl-3 col-md-6">
            @if(hasRole('Student-OT'))
            <a href="{{ route('ot.mdo.escrot.exemption.view') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-blue h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">OT MDO/Escort</p>
                            <div class="dashboard-stat-value fw-semibold text-primary">{{ $MDO_count }}</div>
                            <span class="stat-link-hint text-primary">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-person-gear"></i></span>
                    </div>
                </div>
            </a>
            @else
            <a href="{{ route('admin.dashboard.inhouse_faculty') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-blue h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Total Inhouse Faculty</p>
                            <div class="dashboard-stat-value fw-semibold text-primary">{{ $total_internal_faculty }}</div>
                            <span class="stat-link-hint text-primary">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-people"></i></span>
                    </div>
                </div>
            </a>
            @endif
        </div>

        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty'))
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.sessions') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-green h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Session Details</p>
                            <div class="dashboard-stat-value fw-semibold text-success">{{ $totalSessions }}</div>
                            <span class="stat-link-hint text-success">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-clock-history"></i></span>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(isset($isCCorACC) && $isCCorACC)
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.students') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-amber h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-dark mb-1">Total Students</p>
                            <div class="dashboard-stat-value fw-semibold text-warning-emphasis">{{ $totalStudents }}</div>
                            <span class="stat-link-hint text-warning">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-person-vcard"></i></span>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(hasRole('Admin') || hasRole('Training-Induction'))
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard-statistics.charts') }}" class="text-decoration-none d-block h-100">
                <div class="card dashboard-stat-card card-rose h-100">
                    <div class="card-body d-flex align-items-start justify-content-between gap-2">
                        <div>
                                <p class="small text-dark mb-1">Batch Profile</p>
                            <div class="dashboard-stat-value fw-semibold text-danger">{{ $batchProfileCoursesCount ?? 0 }}</div>
                            <span class="stat-link-hint text-danger">View →</span>
                        </div>
                        <span class="stat-icon"><i class="bi bi-bar-chart-line"></i></span>
                    </div>
                </div>
            </a>
        </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
        @if(hasRole('Admin') || hasRole('Training-Induction'))
            <div class="card dashboard-panel mb-4">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <i class="bi bi-bell-fill text-primary"></i>
                        {{ hasRole('Admin') ? 'Admin Summary' : 'Notifications' }}
                    </h5>
                    <span class="badge text-bg-primary rounded-pill">{{ $notifications->count() }}</span>
                </div>
                <div class="card-body p-3 p-md-4 dashboard-list-scroll">
                    @if($notifications->isEmpty())
                    <div class="dashboard-empty-state">
                        <i class="bi bi-bell"></i>
                        <p class="mb-0 small">No notifications available.</p>
                    </div>
                    @else
                    <div class="list-group list-group-flush">
                        @foreach($notifications as $notification)
                        <button type="button"
                            class="list-group-item list-group-item-action border-0 rounded-2 mb-1 bg-body"
                            onclick="window.markAsReadDashboard({{ $notification->pk }}, this)">
                            <div class="d-flex gap-2">
                                <i class="bi bi-circle-fill text-primary mt-1" style="font-size: .45rem;"></i>
                                <span class="small text-start">{{ $notification->message }}</span>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <div class="card dashboard-panel mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-megaphone text-primary"></i>
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
            <div class="card dashboard-panel mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-journal-check text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Today's Classes</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    @if($todayTimetable && $todayTimetable->isNotEmpty())
                    <div class="dashboard-list-scroll pe-2">
                        @foreach($todayTimetable as $entry)
                        <div class="pb-3 mb-3 border-bottom border-secondary-subtle">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6 text-primary fw-medium">{{ $entry['session_date'] }}</div>
                                <div class="col-md-6 text-primary fw-medium">{{ $entry['topic'] }}</div>
                            </div>
                            <div class="row g-2 text-body-secondary">
                                <div class="col-md-6">Faculty - {{ $entry['faculty_name'] }}</div>
                                <div class="col-md-6">Group Name - {{ $entry['group_name'] ?? 'N/A' }}</div>
                                <div class="col-md-6">Session - {{ $entry['session_time'] }}</div>
                                <div class="col-md-6">Venue - {{ $entry['session_venue'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="dashboard-empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <p class="mb-0 small">No classes scheduled for today.</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="card dashboard-panel">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-pin-angle text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Notices</h5>
                </div>
                <div class="card-body p-3 p-md-4 dashboard-list-scroll">
                    @if(count($notices) === 0)
                    <div class="dashboard-empty-state">
                        <i class="bi bi-file-earmark-text"></i>
                        <p class="mb-0 small">No notices available.</p>
                    </div>
                    @else
                    <ul class="list-unstyled mb-0">
                        @foreach($notices as $notice)
                        <li class="mb-3 d-flex gap-2 align-items-start">
                            <i class="bi bi-file-earmark-text text-primary mt-1 flex-shrink-0" style="font-size: 0.9rem;"></i>
                            <div>
                                <span class="text-body fw-medium">{{ $notice->notice_title }}</span>
                                <small class="d-block text-body-secondary">{{ date('d M, Y', strtotime($notice->created_at)) }}</small>
                                @if($notice->document)
                                <a href="{{ asset('storage/' . $notice->document) }}" target="_blank" class="small text-danger text-decoration-none"><i class="bi bi-paperclip me-1"></i>View attachment</a>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card dashboard-panel mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-balloon-heart text-primary"></i>
                    <h4 class="mb-0 fw-semibold text-primary">Today's Birthday</h4>
                </div>
                <div class="card-body p-3 dashboard-list-scroll">
                    @if($emp_dob_data->isEmpty())
                    <div class="dashboard-empty-state">
                        <i class="bi bi-gift"></i>
                        <p class="mb-0 small">No birthdays today.</p>
                    </div>
                    @else
                    <div class="d-grid gap-2">
                        @foreach($emp_dob_data as $employee)
                        @php
                        $avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary'];
                        $avClass = $avClasses[$loop->index % count($avClasses)];
                        $photo = !empty($employee->profile_picture) ? asset('storage/' . $employee->profile_picture) : null;
                        @endphp
                        <div class="card dashboard-birthday-item rounded-3">
                            <div class="card-body p-3 d-flex align-items-start gap-2">
                                @if($photo)
                                <img src="{{ $photo }}" alt="" class="rounded-circle object-fit-cover flex-shrink-0 dashboard-avatar">
                                @else
                                <div class="rounded-circle {{ $avClass }} fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0 dashboard-avatar">
                                    {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                                </div>
                                @endif
                                <div class="small lh-sm">
                                    <div class="fw-semibold text-body mb-1">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                    <div class="text-body-secondary">{{ $employee->designation_name }}</div>
                                    <div class="text-body-secondary text-break">{{ $employee->email }}</div>
                                    <div class="text-body-secondary">{{ $employee->mobile }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <div class="card dashboard-panel">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3 text-primary"></i>
                    <h5 class="mb-0 fw-semibold">Calendar</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events" theme="gov-red" />
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
