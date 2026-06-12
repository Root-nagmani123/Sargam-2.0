@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Session Moderator/Escort Duty')

@section(hasRole('Student-OT') ? 'content' : 'setup_content')
<style>
    /* ===== Session Moderator / Escort Duty — modern UI (scoped to .otmdo) ===== */
    .otmdo {
        --otmdo-primary: #004a93;
        --otmdo-primary-soft: #eaf1f9;
        --otmdo-border: #eef0f3;
    }

    .otmdo .otmdo-card {
        border: 0;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 4px 16px rgba(16, 24, 40, .06);
    }

    .otmdo .otmdo-accent {
        border-left: 4px solid var(--otmdo-primary);
    }

    .otmdo .otmdo-icon-badge {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: var(--otmdo-primary-soft);
        color: var(--otmdo-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .otmdo .otmdo-id-label {
        font-size: .75rem;
        color: #6b7280;
    }

    .otmdo .otmdo-id-value {
        font-weight: 700;
        color: #111827;
    }

    /* Summary stat cards */
    .otmdo .otmdo-stat {
        border: 1px solid var(--otmdo-border);
        border-left: 4px solid var(--otmdo-primary);
        border-radius: 12px;
        background: #fff;
        height: 100%;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .otmdo .otmdo-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(16, 24, 40, .08);
    }

    .otmdo .otmdo-stat-label {
        font-size: .8125rem;
        font-weight: 600;
        color: #6b7280;
    }

    .otmdo .otmdo-stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1;
        color: #111827;
    }

    .otmdo .otmdo-stat-ico {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    /* Filter toolbar */
    .otmdo .otmdo-toolbar {
        border: 1px solid var(--otmdo-border);
        border-radius: 12px;
        background: #fff;
    }

    .otmdo .otmdo-toolbar .form-label {
        font-size: .75rem;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: .25rem;
    }

    .otmdo .otmdo-toolbar .form-select,
    .otmdo .otmdo-toolbar .form-control {
        border-radius: 8px;
        font-size: .875rem;
    }

    /* Table */
    .otmdo .otmdo-table {
        margin-bottom: 0;
    }

    .otmdo .otmdo-table thead th {
        background: #f3f4f6;
        color: #6b7280;
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
        border: 0;
        padding: .85rem 1rem;
        vertical-align: middle;
    }

    .otmdo .otmdo-table tbody td {
        padding: .9rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f1f3;
        color: #374151;
        font-size: .9rem;
    }

    .otmdo .otmdo-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .otmdo .otmdo-table tbody tr {
        transition: background-color .15s ease;
    }

    .otmdo .otmdo-table tbody tr:hover {
        background: #f8fafc;
    }

    .otmdo .otmdo-date {
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
    }

    .otmdo .otmdo-time {
        font-size: .8rem;
        color: #6b7280;
        white-space: nowrap;
    }

    /* Duty-type pills */
    .otmdo .otmdo-badge {
        font-weight: 600;
        font-size: .75rem;
        padding: .35rem .7rem;
        border-radius: 999px;
    }

    .otmdo .otmdo-badge-escort {
        background: var(--otmdo-primary-soft);
        color: var(--otmdo-primary);
    }

    .otmdo .otmdo-badge-mdo {
        background: #e7f6ec;
        color: #1a7f4b;
    }

    .otmdo .otmdo-badge-neutral {
        background: #f1f3f5;
        color: #495057;
    }

    .otmdo .otmdo-empty {
        border: 1px dashed #d8dde3;
        border-radius: 12px;
        background: #f9fafb;
    }

    @media (max-width: 575.98px) {
        .otmdo .otmdo-header-actions {
            width: 100%;
        }
    }

    @media print {
        .otmdo .otmdo-no-print {
            display: none !important;
        }
    }
</style>

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
@endphp

<div class="container-fluid otmdo">

    {{-- ===================== HEADER ===================== --}}
    <div class="card otmdo-card otmdo-accent mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="otmdo-icon-badge">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">assignment_ind</i>
                    </span>
                    <div>
                        <h4 class="mb-0 fw-bold text-dark">Session Moderator / Escort Duty</h4>
                        <p class="mb-0 small text-muted">Your assigned moderator &amp; escort duties</p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 otmdo-header-actions">
                    @if($isStudentView)
                    <div class="text-md-end small lh-lg">
                        <div><span class="otmdo-id-label">OT Code:</span> <span class="otmdo-id-value">{{ $studentData['ot_code'] }}</span></div>
                        <div><span class="otmdo-id-label">OT Name:</span> <span class="otmdo-id-value">{{ $studentData['student_name'] }}</span></div>
                        <div><span class="otmdo-id-label">Email:</span> <span class="otmdo-id-value">{{ $studentData['email'] ?? 'N/A' }}</span></div>
                    </div>
                    @endif
                    <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 otmdo-no-print"
                        onclick="window.print()" aria-label="Print this page">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">print</i>
                        <span class="d-none d-sm-inline">Print</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== STUDENT (OT) VIEW ===================== --}}
    @if($isStudentView)
        @php
            $dutyMaps = $studentData['duty_maps'] ?? [];
            $totalDuties = $studentData['total_duty_count'] ?? count($dutyMaps);
            $escortCount = collect($dutyMaps)->filter(fn($d) => str_contains(strtolower($d['duty_type'] ?? ''), 'escort'))->count();
            $moderatorCount = collect($dutyMaps)->filter(function ($d) {
                $t = strtolower($d['duty_type'] ?? '');
                return str_contains($t, 'mdo') || str_contains($t, 'moder');
            })->count();
        @endphp

        {{-- Summary cards (real aggregates only) --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-4">
                <div class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Total Duties</div>
                        <div class="otmdo-stat-value">{{ str_pad($totalDuties, 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <span class="otmdo-stat-ico" style="background:#eaf1f9;color:#004a93;">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">assignment</i>
                    </span>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Escort Duties</div>
                        <div class="otmdo-stat-value">{{ str_pad($escortCount, 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <span class="otmdo-stat-ico" style="background:#eaf1f9;color:#004a93;">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">badge</i>
                    </span>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Moderator Duties</div>
                        <div class="otmdo-stat-value">{{ str_pad($moderatorCount, 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <span class="otmdo-stat-ico" style="background:#e7f6ec;color:#1a7f4b;">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">co_present</i>
                    </span>
                </div>
            </div>
        </div>

        {{-- Filters toolbar (IDs preserved for existing JS) --}}
        <div class="otmdo-toolbar p-3 mb-3 otmdo-no-print">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2 me-md-2 pb-1 pb-md-0">
                    <i class="material-icons material-symbols-rounded text-primary" aria-hidden="true">filter_list</i>
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
                        class="btn btn-outline-danger btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">restart_alt</i>
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
                                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:14px;" aria-hidden="true">sticky_note_2</i>{{ $remark }}
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="otmdo-badge {{ $otmdoBadge($duty['duty_type'] ?? '') }}">
                                        {{ $duty['duty_type'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $duty['faculty'] ?? 'N/A' }}</td>
                                <td class="text-center pe-3 pe-md-4">
                                    @if(strtolower($duty['duty_type'] ?? '') == 'escort' && !empty($duty['faculty_master_pk']))
                                    <a href="{{ route('faculty.edit', ['id' => encrypt($duty['faculty_master_pk'])]) }}"
                                        class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1"
                                        aria-label="Edit Faculty Details">
                                        <i class="material-icons material-symbols-rounded" style="font-size:16px;" aria-hidden="true">edit</i>
                                        <span class="d-none d-lg-inline">Edit Faculty</span>
                                    </a>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
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
                    <i class="material-icons material-symbols-rounded text-secondary" style="font-size:48px;" aria-hidden="true">event_busy</i>
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
                    <i class="material-icons material-symbols-rounded text-primary" aria-hidden="true">filter_list</i>
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
                        class="btn btn-outline-danger btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">restart_alt</i>
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
                        <div class="otmdo-stat-value">{{ $totalStudents }}</div>
                    </div>
                    <span class="otmdo-stat-ico" style="background:#eaf1f9;color:#004a93;">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">groups</i>
                    </span>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="otmdo-stat p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="otmdo-stat-label">Total Duties</div>
                        <div class="otmdo-stat-value">{{ $totalDutiesAll }}</div>
                    </div>
                    <span class="otmdo-stat-ico" style="background:#e7f6ec;color:#1a7f4b;">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">assignment</i>
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
                        <span class="otmdo-icon-badge">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">person</i>
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
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">assignment</i>
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
                                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:14px;" aria-hidden="true">sticky_note_2</i>{{ $remark }}
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="otmdo-badge {{ $otmdoBadge($duty['duty_type'] ?? '') }}">
                                        {{ $duty['duty_type'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $duty['faculty'] ?? 'N/A' }}</td>
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
                    <i class="material-icons material-symbols-rounded text-secondary" style="font-size:48px;" aria-hidden="true">search_off</i>
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
