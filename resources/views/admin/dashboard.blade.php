@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')

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
            <h2 id="admin-summary-title" class="fw-bold text-dark fs-3">
                Admin Summary
            </h2>

            <div class="line w-100 my-2"></div>

            <div class="content-text">
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
            </div>
        </section>

        <!-- Campus Summary -->
        <section aria-labelledby="campus-summary-title">
            <h2 id="campus-summary-title" class="fw-bold text-dark fs-3">
                Campus Summary
            </h2>

            <div class="line w-100 my-2"></div>

            <div class="content-text">
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
            </div>
        </section>

    </div>

    <!-- RIGHT NOTICE PANEL -->
    <div class="col-lg-4">

        <div class="card shadow-sm border-0 rounded-4 h-100" style="max-height:600px; overflow-y:auto;">
            
            <!-- Notice Header -->
            <div class="card-header bg-danger text-white rounded-top-4 py-3">
                <h4 class="mb-0 fw-bold d-flex align-items-center text-white">
                    <i class="bi bi-megaphone-fill me-2"></i>
                    Notices
                </h4>
            </div>

            <!-- Notice Body -->
            <div class="card-body">

                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>

                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>
                <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                    to various administrative functions.</p>

            </div>

        </div>

    </div>
</div>

            <h3 class="fw-bold py-3">Today Birthday's</h3>
            <hr class="my-2">
            <div class="container my-4">
                <div class="row g-4">
                    <div class="col-8">
                        <!-- Example Card (Repeat in Loop) -->
                        @if($emp_dob_data->isEmpty())
                        <p class="text-center">No Birthdays Today</p>
                        @else
                        @php
                        $colors = ['#E4F2E9', '#FFF7DF', '#E8E8E9', '#ECEAF5', '#F5DCE0', '#D3E1EF'];
                        @endphp
                        @foreach($emp_dob_data as $employee)
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="card shadow-sm border-0 h-100"
                                style="background:{{ $colors[$loop->index % count($colors)] }}; border-radius:20px;">
                                <div class="card-body d-flex align-items-start gap-3 p-4">

                                    <!-- Profile Image -->
                                    @php
                                    $photo = $employee->profile_picture ?? ''
                                    ? asset('storage/' . $employee->profile_picture)
                                    : asset('admin_assets/images/profile/user-1.jpg');
                                    @endphp

                                    <img src="{{ $photo }}" class="rounded-circle"
                                        style="width:60px; height:60px; object-fit:cover;"
                                        alt="Profile photo of {{ $employee->first_name ?? '' }} {{ $employee->last_name ?? '' }} ">

                                    <!-- Details -->
                                    <div>
                                        <h5 class="fw-bold mb-1">{{ $employee->first_name ?? '' }}
                                            {{ $employee->last_name ?? '' }}</h5>
                                        <p class="text-muted small mb-2">{{ $employee->designation_name ?? '' }}</p>
                                        <p class="mb-0 small">
                                            {{ $employee->email ?? '' }} <br>
                                            {{ $employee->mobile ?? '' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif
                    </div>
                    <div class="col-4">
                        <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events"
                            theme="gov-red" />
                    </div>
                </div>
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