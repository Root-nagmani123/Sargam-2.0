@extends('admin.layouts.master')

@section('title', 'Faculty MDO/Escort Exception View')

@section('setup_content')
<style>
    /* Clean, flat styling built on the design-system tokens (--ds-*). */
    .fmx-page .fmx-table thead th {
        background: var(--ds-surface-2);
        color: var(--ds-primary);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
        border-bottom: 1px solid var(--ds-line);
        padding: 0.75rem 1rem;
    }
    .fmx-page .fmx-table tbody td {
        font-size: 0.875rem;
        color: var(--ds-ink);
        vertical-align: middle;
        padding: 0.75rem 1rem;
    }
    .fmx-page .fmx-table tbody tr:last-child td { border-bottom: 0; }

    .fmx-page .fmx-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        padding: 0.4rem 0.75rem;
        border-radius: 10px;
        color: #b54708;
        background: #fffaeb;
    }
    .fmx-page .fmx-badge .material-icons { font-size: 0.95rem; }

    /* Faculty section header (admin view) */
    .fmx-page .fmx-faculty-head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: var(--ds-space-3);
        background: var(--ds-surface-2);
        border-bottom: 1px solid var(--ds-line);
        border-left: 3px solid var(--ds-primary);
    }
    .fmx-page .fmx-faculty-head .material-icons { color: var(--ds-primary); font-size: 1.25rem; }
    .fmx-page .fmx-faculty-name { margin: 0; font-weight: 600; font-size: 1rem; color: var(--ds-ink); }

    .fmx-page .fmx-course { padding: var(--ds-space-3); border-top: 1px solid var(--ds-line); }
    .fmx-page .fmx-course:first-of-type { border-top: 0; }
    .fmx-page .fmx-course-title {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 600;
        color: var(--ds-ink);
    }
    .fmx-page .fmx-course-title .material-icons { color: var(--ds-primary); font-size: 1.05rem; }

    .fmx-page .ds-stat-icon .material-icons { font-size: 1.15rem; }

    @media print {
        .fmx-print-hide { display: none !important; }
    }
</style>

<div class="container-fluid fmx-page">
    <x-breadcrum title="Faculty MDO/Escort Exception View" :showBack="true"></x-breadcrum>

    {{-- Title + Print --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-2 mb-4">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-1 fmx-print-hide border-0 text-primary bg-white" onclick="printContent()" >
            <i class="material-icons material-symbols-rounded" style="font-size: 20px;">print</i>
            <span>Print</span>
        </button>
    </div>

    <div id="fmxPrintable">
        @php
            // Check if this is a faculty login view
            $isFacultyView = isset($isFacultyView) && $isFacultyView === true;
        @endphp

        @if($isFacultyView && isset($courseMaster))
            {{-- Course Filter --}}
            <div class="ds-card ds-section fmx-print-hide">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="course_filter" class="form-label fw-semibold small text-secondary d-flex align-items-center gap-1">
                                <i class="material-icons material-symbols-rounded" style="font-size: 18px;">filter_alt</i>
                                Select Course
                            </label>
                            <select id="course_filter" class="form-select">
                                <option value="">-- All Courses --</option>
                                @foreach ($courseMaster as $id => $name)
                                    <option value="{{ $id }}" {{ isset($courseFilter) && $courseFilter == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($isFacultyView)
            {{-- Faculty Login View --}}
            @if(isset($hasData) && $hasData && count($studentData) > 0)
                {{-- Summary stat cards --}}
                <div class="row g-3 ds-section">
                    <div class="col-md-6">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Total Number of Exceptions</p>
                                <div class="ds-stat-value">{{ $totalExceptions ?? 0 }}</div>
                            </div>
                            <span class="ds-stat-icon"><i class="material-icons material-symbols-rounded">assignment</i></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Total Students with Exceptions</p>
                                <div class="ds-stat-value">{{ count($studentData) }}</div>
                            </div>
                            <span class="ds-stat-icon"><i class="material-icons material-symbols-rounded">group</i></span>
                        </div>
                    </div>
                </div>

                {{-- Student Data Table --}}
                <div class="ds-card ds-section">
                    <div class="card-header">
                        <i class="material-icons material-symbols-rounded" style="color: var(--ds-primary);">group</i>
                        <span>Student Exceptions</span>
                    </div>
                    <div class="card-body">
                        @php $displayedRows = 0; @endphp
                        <div class="ds-table-wrap">
                            <table class="table align-middle mb-0 fmx-table">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>OT Code</th>
                                        <th>Email</th>
                                        <th>Faculty</th>
                                        <th>Course</th>
                                        <th>Date</th>
                                        <th>Duty Type</th>
                                        <th>Time</th>
                                        <th>Description</th>
                                        <th class="text-center">Total Exceptions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($studentData as $student)
                                        @if(count($student['exemptions']) > 0)
                                            @foreach($student['exemptions'] as $exemption)
                                                <tr>
                                                    <td><strong>{{ $student['student_name'] }}</strong></td>
                                                    <td>{{ $student['ot_code'] }}</td>
                                                    <td>{{ $student['email'] ?? 'N/A' }}</td>
                                                    <td>{{ $exemption['faculty'] ?? 'N/A' }}</td>
                                                    <td>{{ $exemption['course_name'] ?? 'N/A' }}</td>
                                                    <td>{{ $exemption['date'] ? \Carbon\Carbon::parse($exemption['date'])->format('d/m/Y') : 'N/A' }}</td>
                                                    <td>{{ $exemption['duty_type'] ?? 'N/A' }}</td>
                                                    <td>{{ $exemption['time'] ?? 'N/A' }}</td>
                                                    <td style="max-width: 250px; word-wrap: break-word;">
                                                        {{ $exemption['description'] && $exemption['description'] !== 'N/A' ? $exemption['description'] : '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if($loop->first)
                                                            <span class="fmx-badge">
                                                                <i class="material-icons material-symbols-rounded">assignment</i>
                                                                {{ $student['total_exception_count'] }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @php $displayedRows++; @endphp
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($displayedRows === 0)
                            <div class="ds-empty-state mt-3">
                                <i class="material-icons material-symbols-rounded d-block mb-2 opacity-50" style="font-size: 40px;">info</i>
                                <p class="mb-0">No records found</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- No records found --}}
                <div class="ds-empty-state">
                    <i class="material-icons material-symbols-rounded d-block mb-2 opacity-50" style="font-size: 48px;">info</i>
                    <p class="mb-0 fs-6">No records found</p>
                </div>
            @endif
        @else
            {{-- Admin View: Faculty → Course → Students --}}
            @if(isset($facultyData) && count($facultyData) > 0)
                @foreach($facultyData as $faculty)
                    <div class="ds-card ds-section">
                        <div class="fmx-faculty-head">
                            <i class="material-icons material-symbols-rounded">person</i>
                            <h6 class="fmx-faculty-name">{{ $faculty['faculty_name'] }}</h6>
                        </div>

                        @foreach($faculty['courses'] as $course)
                            <div class="fmx-course">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <span class="fmx-course-title">
                                        <i class="material-icons material-symbols-rounded">book</i>
                                        {{ $course['course_name'] }}
                                    </span>
                                    <span class="fmx-badge">
                                        <i class="material-icons material-symbols-rounded">assignment</i>
                                        {{ $course['duty_count'] }} Exception(s)
                                    </span>
                                </div>

                                @if($course['student_duties'] && count($course['student_duties']) > 0)
                                    <div class="ds-table-wrap">
                                        <table class="table align-middle mb-0 fmx-table">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>OT Code</th>
                                                    <th>Date</th>
                                                    <th>Duty Type</th>
                                                    <th>Time</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($course['student_duties'] as $duty)
                                                    <tr>
                                                        <td><strong>{{ $duty['student_name'] }}</strong></td>
                                                        <td>{{ $duty['ot_code'] }}</td>
                                                        <td>{{ $duty['date'] ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}</td>
                                                        <td>{{ $duty['duty_type'] }}</td>
                                                        <td>{{ $duty['time'] }}</td>
                                                        <td style="max-width: 300px; word-wrap: break-word;">{{ $duty['description'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="ds-empty-state">
                                        <p class="mb-0">No exceptions found for this course.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @else
                <div class="ds-empty-state">
                    <i class="material-icons material-symbols-rounded d-block mb-2 opacity-50" style="font-size: 48px;">info</i>
                    <p class="mb-0">No faculty data found matching the selected filters.</p>
                </div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
    <script>
        // Print only the report content in a clean standalone window.
        function printContent() {
            var printContent = document.getElementById('fmxPrintable').innerHTML;
            var printWindow = window.open('', '', 'width=900,height=600');

            var printDocument = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Faculty MDO/Escort Exception Report</title>
                    <link href="{{ asset('admin_assets/css/material-icons-local.css') }}" rel="stylesheet">
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1f2937; background: #fff; padding: 20px; line-height: 1.5; }
                        h5, h6 { color: #004a93; }
                        /* Hide interactive bits */
                        .fmx-print-hide, .form-label, .form-select, select, button, .btn { display: none !important; }

                        .row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 16px; }
                        .col-md-6 { flex: 1 1 240px; }

                        .ds-stat-card { border: 1px solid #d1d5db; border-left: 3px solid #004a93; border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center; page-break-inside: avoid; }
                        .ds-stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
                        .ds-stat-value { font-size: 26px; font-weight: 700; color: #004a93; }
                        .ds-stat-icon { display: none; }

                        .ds-card { border: 1px solid #d1d5db; border-radius: 8px; margin-bottom: 16px; overflow: hidden; page-break-inside: avoid; }
                        .ds-card > .card-header, .card-header { background: #f0f3f7; border-bottom: 1px solid #d1d5db; padding: 12px 16px; font-weight: 600; color: #004a93; text-transform: uppercase; font-size: 13px; letter-spacing: .5px; display: flex; align-items: center; gap: 8px; }
                        .card-body { padding: 16px; }

                        .fmx-faculty-head { background: #f0f3f7; border-bottom: 1px solid #d1d5db; border-left: 3px solid #004a93; padding: 12px 16px; display: flex; align-items: center; gap: 8px; }
                        .fmx-faculty-name { color: #004a93; font-size: 16px; margin: 0; }
                        .fmx-course { padding: 16px; border-top: 1px solid #e5e7eb; page-break-inside: avoid; }
                        .fmx-course-title { font-weight: 600; color: #1f2937; font-size: 15px; display: inline-flex; align-items: center; gap: 6px; }
                        .fmx-badge { background: #fffaeb; color: #b54708; border: 1px solid #fde3c3; padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }

                        .ds-table-wrap { border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; margin-top: 8px; }
                        .fmx-table { width: 100%; border-collapse: collapse; font-size: 13px; }
                        .fmx-table thead th { background: #f0f3f7; color: #004a93; font-weight: 600; font-size: 12px; padding: 10px 12px; text-align: left; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #d1d5db; white-space: nowrap; }
                        .fmx-table tbody td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #1f2937; }
                        .fmx-table tbody tr:last-child td { border-bottom: none; }

                        .ds-empty-state { text-align: center; padding: 24px; color: #6b7280; border: 1px dashed #d1d5db; border-radius: 8px; }
                        .material-icons, .material-symbols-rounded { font-size: 16px; vertical-align: middle; }
                        strong { font-weight: 600; color: #1f2937; }
                        .d-flex { display: flex; } .flex-wrap { flex-wrap: wrap; }
                        .justify-content-between { justify-content: space-between; } .align-items-center { align-items: center; }
                        .gap-2 { gap: 8px; } .mb-3 { margin-bottom: 12px; } .text-center { text-align: center; }

                        @media print {
                            body { padding: 0; }
                            .ds-card, .fmx-course, table, tr { page-break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>${printContent}</body>
                </html>
            `;

            printWindow.document.write(printDocument);
            printWindow.document.close();

            setTimeout(function() {
                printWindow.focus();
                printWindow.print();
            }, 250);
        }

        // Course filter handler for faculty view
        $(document).ready(function() {
            if ($('#course_filter').length > 0) {
                $('#course_filter').on('change', function() {
                    var courseFilter = $(this).val();
                    var url = new URL(window.location.href);

                    if (courseFilter) {
                        url.searchParams.set('course_filter', courseFilter);
                    } else {
                        url.searchParams.delete('course_filter');
                    }

                    window.location.href = url.toString();
                });
            }
        });
    </script>
@endpush

@endsection
