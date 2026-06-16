@extends(hasRole('Officer Trainee') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Session Moderator/Escort Duty')

@section(hasRole('Officer Trainee') ? 'content' : 'setup_content')

@include('admin.ot_mdo_escrot_exemption._styles')

@php
    // Detect single-student (OT) view vs. multi-student admin view.
    $isStudentView = isset($studentData) && isset($studentData['student_name']) && isset($studentData['ot_code']);

    // Helper: resolve a duty-type pill class from the (existing) duty_type text.
    $otmdoBadge = function ($type) {
        $t = strtolower((string) $type);
        if (str_contains($t, 'escort')) return 'otmdo-badge-escort';
        if (str_contains($t, 'mdo') || str_contains($t, 'moder')) return 'otmdo-badge-mdo';
        return 'otmdo-badge-neutral';
    };

    // Helper: render the acknowledgement status pill.
    $otmdoStatus = function ($status) {
        $isCompleted = strtolower((string) $status) === 'completed';
        $cls = $isCompleted ? 'otmdo-status-completed' : 'otmdo-status-pending';
        $label = $isCompleted ? 'Completed' : 'Pending';
        return '<span class="otmdo-badge ' . $cls . '">' . $label . '</span>';
    };
@endphp

<div class="container-fluid otmdo px-2 py-2">

    {{-- ===================== HEADER ===================== --}}
    <div class="card otmdo-card mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                {{-- Title --}}
                <div>
                    <h4 class="otmdo-title mb-1">Session Moderator / Escort Duty</h4>
                    <p class="mb-0 small text-muted">Your assigned moderator &amp; escort duties</p>
                </div>

                {{-- Right: OT identity (student view) + print --}}
                <div class="d-flex align-items-start gap-3 otmdo-header-actions">
                    @if($isStudentView)
                    <div class="text-md-end">
                        <div class="mb-1">
                            <span class="otmdo-id-label">OT Code:</span>
                            <span class="otmdo-id-value ms-1">{{ $studentData['ot_code'] }}</span>
                        </div>
                        <div class="mb-1">
                            <span class="otmdo-id-label">OT Name:</span>
                            <span class="otmdo-id-value ms-1">{{ $studentData['student_name'] }}</span>
                        </div>
                        <div>
                            <span class="otmdo-id-label">Email:</span>
                            <span class="otmdo-id-value ms-1">{{ $studentData['email'] ?? 'N/A' }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== STUDENT (OT) VIEW ===================== --}}
    @if($isStudentView)
        @php
            $dutyMaps = $studentData['duty_maps'] ?? [];
            $stats = $studentData['stats'] ?? ['today' => 0, 'pending' => 0, 'completed' => 0];
        @endphp

        {{-- Summary cards — click to open the matching sub-page --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-4">
                <a href="{{ route('ot.mdo.escrot.today') }}" class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Today's Duty</div>
                        <div class="otmdo-stat-value mt-2">{{ str_pad($stats['today'], 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-sm-4">
                <a href="{{ route('ot.mdo.escrot.pending') }}" class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Pending Duty</div>
                        <div class="otmdo-stat-value mt-2">{{ str_pad($stats['pending'], 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-sm-4">
                <a href="{{ route('ot.mdo.escrot.completed') }}" class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Completed Duty</div>
                        <div class="otmdo-stat-value mt-2">{{ str_pad($stats['completed'], 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Filters toolbar (IDs preserved for existing JS) --}}
        <div class="otmdo-toolbar p-3 mb-3 otmdo-no-print">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2 me-md-2 pb-1 pb-md-0">
                    <i class="bi bi-funnel text-primary" aria-hidden="true"></i>
                    <span class="fw-semibold text-dark">Filters</span>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="duty_type_filter" class="form-label">Duty Type</label>
                    <select name="duty_type_filter" id="duty_type_filter" class="form-select form-select-sm">
                        <option value="">All Duty Types</option>
                        @if(isset($allDutyTypes) && is_array($allDutyTypes))
                            @foreach($allDutyTypes as $pk => $name)
                                <option value="{{ $pk }}" {{ isset($dutyTypeFilter) && $dutyTypeFilter == $pk ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="from_date_filter" class="form-label">From Date</label>
                    <input type="date" name="from_date_filter" id="from_date_filter" class="form-control form-control-sm"
                        value="{{ isset($fromDateFilter) ? $fromDateFilter : '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="to_date_filter" class="form-label">To Date</label>
                    <input type="date" name="to_date_filter" id="to_date_filter" class="form-control form-control-sm"
                        value="{{ isset($toDateFilter) ? $toDateFilter : '' }}">
                </div>
                <div class="col-12 col-md-auto">
                    <button type="button" id="clearFilterBtn"
                        class="btn btn-outline-danger btn-sm w-100 rounded-1 d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- Duties table --}}
        <div class="card otmdo-card">
            <div class="card-body p-0">
                @if(!empty($dutyMaps) && count($dutyMaps) > 0)
                <div class="table-responsive">
                    <table class="table align-middle otmdo-table">
                        <thead>
                            <tr>
                                <th class="ps-3 ps-md-4">S. No.</th>
                                <th>Date &amp; Time</th>
                                <th>Course Name</th>
                                <th>Duty Type</th>
                                <th>Faculty</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-3 pe-md-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dutyMaps as $i => $duty)
                            @php
                                $timeText = trim((string) ($duty['time'] ?? ''));
                                $hasTime = $timeText !== '' && $timeText !== 'N/A - N/A' && stripos($timeText, 'N/A') === false;
                                $remark = $duty['description'] ?? null;
                                $hasRemark = $remark && $remark !== 'N/A';
                            @endphp
                            <tr>
                                <td class="ps-3 ps-md-4 text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="otmdo-date">
                                        {{ !empty($duty['date']) ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}
                                    </div>
                                    @if($hasTime)
                                    <div class="otmdo-time">{{ $timeText }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $duty['course'] ?? 'N/A' }}</span>
                                    @if($hasRemark)
                                    <div class="small text-muted text-truncate" style="max-width: 22rem;" title="{{ $remark }}">
                                        <i class="bi bi-sticky align-middle me-1" aria-hidden="true"></i>{{ $remark }}
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="otmdo-badge {{ $otmdoBadge($duty['duty_type'] ?? '') }}">
                                        {{ $duty['duty_type'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $duty['faculty'] ?? 'N/A' }}</td>
                                <td class="text-center">{!! $otmdoStatus($duty['status'] ?? 'pending') !!}</td>
                                <td class="text-center pe-3 pe-md-4">
                                    @php $isCompleted = ($duty['status'] ?? 'pending') === 'completed'; @endphp
                                    <div class="d-inline-flex align-items-center gap-2">
                                        @if($isCompleted)
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 d-inline-flex align-items-center gap-1" disabled>
                                            <i class="bi bi-check2" aria-hidden="true"></i>
                                            <span>Acknowledged</span>
                                        </button>
                                        @else
                                        <form method="POST" action="{{ route('ot.mdo.escrot.acknowledge') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="duty_pk" value="{{ $duty['id'] }}">
                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-1 d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                                                <span>Acknowledge</span>
                                            </button>
                                        </form>
                                        @endif

                                        @if(strtolower($duty['duty_type'] ?? '') == 'escort' && !empty($duty['faculty_master_pk']))
                                        <a href="{{ route('faculty.edit', ['id' => encrypt($duty['faculty_master_pk'])]) }}"
                                            class="btn btn-outline-primary btn-sm rounded-1 d-inline-flex align-items-center"
                                            title="Edit Faculty Details" aria-label="Edit Faculty Details">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center px-3 px-md-4 py-3 border-top">
                    <span class="small text-muted">
                        Showing <span class="fw-semibold text-dark">{{ count($dutyMaps) }}</span>
                        {{ Str::plural('duty', count($dutyMaps)) }}
                    </span>
                </div>
                @else
                <div class="otmdo-empty text-center py-5 m-3 m-md-4">
                    <i class="bi bi-calendar-x text-secondary" style="font-size:48px;" aria-hidden="true"></i>
                    <p class="mt-2 mb-0 fw-semibold text-secondary">No records found</p>
                    <p class="small text-muted mb-0">There are no duties matching the selected filters.</p>
                </div>
                @endif
            </div>
        </div>

    {{-- ===================== ADMIN VIEW ===================== --}}
    @else
        @php
            $students = (isset($studentData) && is_array($studentData)) ? $studentData : [];
            $totalStudents = count($students);
            $totalDutiesAll = collect($students)->sum(fn($s) => $s['duty_count'] ?? 0);
        @endphp

        {{-- Filters toolbar (IDs preserved for existing JS) --}}
        <div class="otmdo-toolbar p-3 mb-3 otmdo-no-print">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2 me-md-2 pb-1 pb-md-0">
                    <i class="bi bi-funnel text-primary" aria-hidden="true"></i>
                    <span class="fw-semibold text-dark">Filters</span>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="duty_type_filter" class="form-label">Duty Type</label>
                    <select name="duty_type_filter" id="duty_type_filter" class="form-select form-select-sm">
                        <option value="">All Duty Types</option>
                        @if(isset($allDutyTypes) && is_array($allDutyTypes))
                            @foreach($allDutyTypes as $pk => $name)
                                <option value="{{ $pk }}" {{ isset($dutyTypeFilter) && $dutyTypeFilter == $pk ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="from_date_filter" class="form-label">From Date</label>
                    <input type="date" name="from_date_filter" id="from_date_filter" class="form-control form-control-sm"
                        value="{{ isset($fromDateFilter) ? $fromDateFilter : '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="to_date_filter" class="form-label">To Date</label>
                    <input type="date" name="to_date_filter" id="to_date_filter" class="form-control form-control-sm"
                        value="{{ isset($toDateFilter) ? $toDateFilter : '' }}">
                </div>
                <div class="col-12 col-md-auto">
                    <button type="button" id="clearFilterBtn"
                        class="btn btn-outline-danger btn-sm w-100 rounded-3 d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        @if($totalStudents > 0)
        {{-- Summary --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-sm-4">
                <div class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Students</div>
                        <div class="otmdo-stat-value mt-2">{{ $totalStudents }}</div>
                    </div>
                    <span class="otmdo-stat-ico" style="background:#eaf1f9;color:#004a93;">
                        <i class="bi bi-people" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Total Duties</div>
                        <div class="otmdo-stat-value mt-2">{{ $totalDutiesAll }}</div>
                    </div>
                    <span class="otmdo-stat-ico" style="background:#e7f6ec;color:#1a7f4b;">
                        <i class="bi bi-card-checklist" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
        </div>

        @foreach($students as $student)
        <div class="card otmdo-card mb-3">
            <div class="card-body p-3 p-md-4">
                {{-- Student header --}}
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 pb-3 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <span class="otmdo-stat-ico" style="background:#eaf1f9;color:#004a93;">
                            <i class="bi bi-person" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">
                                <span class="text-primary">{{ $student['ot_code'] }}</span> · {{ $student['student_name'] }}
                            </h6>
                            @if(!empty($student['email']))
                            <span class="small text-muted">{{ $student['email'] }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="badge rounded-pill text-bg-primary fs-6 fw-semibold px-3 py-2 d-inline-flex align-items-center gap-1">
                        <i class="bi bi-card-checklist" aria-hidden="true"></i>
                        {{ $student['duty_count'] }} {{ Str::plural('Duty', $student['duty_count']) }}
                    </span>
                </div>

                @if(!empty($student['duty_maps']) && count($student['duty_maps']) > 0)
                <div class="table-responsive">
                    <table class="table align-middle otmdo-table">
                        <thead>
                            <tr>
                                <th style="width:64px;">S. No.</th>
                                <th>Date &amp; Time</th>
                                <th>Course Name</th>
                                <th>Duty Type</th>
                                <th>Faculty</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($student['duty_maps'] as $i => $duty)
                            @php
                                $timeText = trim((string) ($duty['time'] ?? ''));
                                $hasTime = $timeText !== '' && $timeText !== 'N/A - N/A' && stripos($timeText, 'N/A') === false;
                                $remark = $duty['description'] ?? null;
                                $hasRemark = $remark && $remark !== 'N/A';
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="otmdo-date">
                                        {{ !empty($duty['date']) ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}
                                    </div>
                                    @if($hasTime)
                                    <div class="otmdo-time">{{ $timeText }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $duty['course'] ?? 'N/A' }}</span>
                                    @if($hasRemark)
                                    <div class="small text-muted text-truncate" style="max-width: 22rem;" title="{{ $remark }}">
                                        <i class="bi bi-sticky align-middle me-1" aria-hidden="true"></i>{{ $remark }}
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="otmdo-badge {{ $otmdoBadge($duty['duty_type'] ?? '') }}">
                                        {{ $duty['duty_type'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $duty['faculty'] ?? 'N/A' }}</td>
                                <td class="text-center">{!! $otmdoStatus($duty['status'] ?? 'pending') !!}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0">No MDO/Escort duties found for this student.</p>
                @endif
            </div>
        </div>
        @endforeach

        @else
        <div class="card otmdo-card">
            <div class="card-body p-3 p-md-4">
                <div class="otmdo-empty text-center py-5">
                    <i class="bi bi-search text-secondary" style="font-size:48px;" aria-hidden="true"></i>
                    <p class="mt-2 mb-0 fw-semibold text-secondary">No student data found</p>
                    <p class="small text-muted mb-0">No students match the selected filters.</p>
                </div>
            </div>
        </div>
        @endif
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dutyTypeFilter = document.getElementById('duty_type_filter');
        const fromDateFilter = document.getElementById('from_date_filter');
        const toDateFilter = document.getElementById('to_date_filter');
        const clearFilterBtn = document.getElementById('clearFilterBtn');

        // Function to update URL with filters
        function updateFilters() {
            const url = new URL(window.location.href);

            // Update duty type filter
            if (dutyTypeFilter && dutyTypeFilter.value) {
                url.searchParams.set('duty_type_filter', dutyTypeFilter.value);
            } else {
                url.searchParams.delete('duty_type_filter');
            }

            // Update from date filter
            if (fromDateFilter && fromDateFilter.value) {
                url.searchParams.set('from_date_filter', fromDateFilter.value);
            } else {
                url.searchParams.delete('from_date_filter');
            }

            // Update to date filter
            if (toDateFilter && toDateFilter.value) {
                url.searchParams.set('to_date_filter', toDateFilter.value);
            } else {
                url.searchParams.delete('to_date_filter');
            }

            window.location.href = url.toString();
        }

        // Duty type filter change
        if (dutyTypeFilter) {
            dutyTypeFilter.addEventListener('change', updateFilters);
        }

        // From date filter change
        if (fromDateFilter) {
            fromDateFilter.addEventListener('change', updateFilters);
        }

        // To date filter change
        if (toDateFilter) {
            toDateFilter.addEventListener('change', updateFilters);
        }

        // Clear all filters
        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', function() {
                const url = new URL(window.location.href);
                url.searchParams.delete('duty_type_filter');
                url.searchParams.delete('from_date_filter');
                url.searchParams.delete('to_date_filter');
                window.location.href = url.toString();
            });
        }
    });
</script>

@endsection
