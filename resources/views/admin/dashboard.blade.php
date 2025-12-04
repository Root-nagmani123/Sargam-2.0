@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
    .calendar-component thead th {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

.line {
    height: 2px;
    background: #b3b3b3b3;
}

.content-text p {
    font-size: 1rem;
    line-height: 1.6;
    color: #333;
}

/* Smooth scrolling for Notice panel */
.card-body {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 transparent;
}

.card-body::-webkit-scrollbar {
    width: 6px;
}

.card-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

/* High contrast (GIGW) */
h2,
h4 {
    color: #1a1a1a !important;
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
                <div class="row g-3 mb-3">
                    <!-- Total Students -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #ECEDF8;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-primary my-filled-icon"
                                        style="font-size: 30px;">groups_2</i> -->
                                    <img src="{{ asset('images/groups.svg') }}" alt="Total Students"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Total Active Courses</h6>
                                    <div class="d-flex align-items-center gap-2">

                                        <h3 class="m-0 fw-bold">{{ $totalActiveCourses }}</h3>
                                    </div>
                                    <!-- <p class="text-primary small mt-2 fw-semibold">+12% from last month</p> -->
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Active Teachers -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #EBF5E8;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-success my-filled-icon"
                                        style="font-size: 30px;">groups_2</i> -->
                                    <img src="{{ asset('images/teachers.svg') }}" alt="Total Students"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Upcoming Courses</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <h3 class="m-0 fw-bold">{{ $upcomingCourses }}</h3>
                                    </div>
                                    <!-- <p class="text-success small mt-2 fw-semibold">+3% from last month</p> -->
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Total Classes -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #FEF5E7;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-warning"
                                                    style="font-size: 30px;">school</i> -->
                                    <img src="{{ asset('images/classes.svg') }}" alt="Total Guest faculty"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Total Guest faculty</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <h3 class="m-0 fw-bold">{{ $total_guest_faculty }}</h3>
                                    </div>
                                    <!-- <p class="text-warning small mt-2 fw-semibold">+2 new classes</p> -->
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Attendance Rate -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #ECEDF8;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-purple"
                                        style="font-size: 30px;">groups_2</i> -->
                                    <img src="{{ asset('images/attendance.svg') }}" alt="Total Inhouse faculty"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Total Inhouse Faculty</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <h3 class="m-0 fw-bold">{{ $total_internal_faculty }}</h3>
                                    </div>
                                    <!-- <p class="text-primary small mt-2 fw-semibold">+2.1% improvement</p> -->
                                </div>
                            </div>
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
    <div class="card" style="border-radius:30px;">
        <div class="card-body">
            <div class="row g-4">
                <!-- LEFT CONTENT -->
                <div class="col-lg-8">

                    <!-- Admin Summary -->
                    <section aria-labelledby="admin-summary-title" class="mb-5">
                        <h2 id="admin-summary-title" style="color: #1a1a1a !important;font-size:24px;line-height:28px;">
                            Admin Summary
                        </h2>

                        <div class="line w-100 my-4"></div>

                        <div class="content-text">
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                        </div>
                    </section>

                    <!-- Campus Summary -->
                    <section aria-labelledby="campus-summary-title">
                        <h2 id="campus-summary-title"
                            style="color: #1a1a1a !important;font-size:24px;line-height:28px;">
                            Campus Summary
                        </h2>

                        <div class="line w-100 my-4"></div>

                        <div class="content-text">
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                        </div>
                    </section>

                </div>

                <!-- RIGHT NOTICE PANEL -->
                <div class="col-lg-4">

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

                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>

                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>
                            <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick
                                access
                                to various administrative functions.</p>

                        </div>

                    </div>

                </div>
            </div>

            <h3 class="fw-bold py-3">Today Birthday's</h3>
            <hr class="my-2 mb-4">
            <div class="row g-4">
                <!-- LEFT SIDE: Birthday Cards -->
                <div class="col-8">
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
                                    : asset('admin_assets/images/profile/user-1.jpg');
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

                <!-- RIGHT SIDE: Calendar -->
                <div class="col-4">
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