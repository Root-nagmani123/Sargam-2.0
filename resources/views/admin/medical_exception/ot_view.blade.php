@extends('admin.layouts.master')

@section('title', 'Medical Exception OT View')

@section('setup_content')
<style>
    /* Clean, flat styling built on the design-system tokens (--ds-*). */
    .mex-page .mex-table thead th {
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
    .mex-page .mex-table tbody td {
        font-size: 0.875rem;
        color: var(--ds-ink);
        vertical-align: middle;
        padding: 0.75rem 1rem;
    }
    .mex-page .mex-table tbody tr:last-child td { border-bottom: 0; }

    /* Soft "count" badge (medical → red tint) */
    .mex-page .mex-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        color: #b42318;
        background: #fef3f2;
    }
    .mex-page .mex-badge .material-icons { font-size: 0.95rem; }

    /* Student info detail grid */
    .mex-page .mex-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: var(--ds-space-3);
    }
    .mex-page .mex-detail-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ds-ink-muted);
        margin-bottom: 0.15rem;
    }
    .mex-page .mex-detail-value { font-size: 0.9rem; font-weight: 500; color: var(--ds-ink); }

    /* Admin per-student header bar */
    .mex-page .mex-student-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: var(--ds-space-3);
        background: var(--ds-surface-2);
        border-bottom: 1px solid var(--ds-line);
        border-left: 3px solid var(--ds-primary);
    }
    .mex-page .mex-student-name { margin: 0; font-weight: 600; font-size: 1rem; color: var(--ds-ink); }
    .mex-page .mex-student-name .mex-ot { color: var(--ds-ink-muted); font-weight: 400; }

    .mex-page .ds-stat-icon .material-icons { font-size: 1.15rem; }
</style>

<div class="container-fluid mex-page">
    <div class="d-print-none">
        <x-breadcrum title="Medical Exception OT View"></x-breadcrum>
    </div>

    {{-- Title + Print --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-2 mb-4 d-print-none">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-1 bg-white text-primary border-0" onclick="window.print()">
            <i class="material-icons material-symbols-rounded" style="font-size: 20px;">print</i>
            <span>Print</span>
        </button>
    </div>

    @php
        $isStudentView = isset($studentData)
            && isset($studentData['student_name'])
            && isset($studentData['ot_code']);
    @endphp

    {{-- ============================
        STUDENT LOGIN VIEW
    ============================ --}}
    @if($isStudentView)

        {{-- Student info --}}
        <div class="ds-card ds-section">
            <div class="card-header">
                <i class="material-icons material-symbols-rounded" style="color: var(--ds-primary);">person</i>
                <span>Student Information</span>
            </div>
            <div class="card-body">
                <div class="mex-detail-grid">
                    <div>
                        <div class="mex-detail-label">Student Name</div>
                        <div class="mex-detail-value">{{ $studentData['student_name'] }}</div>
                    </div>
                    <div>
                        <div class="mex-detail-label">OT Code</div>
                        <div class="mex-detail-value">{{ $studentData['ot_code'] }}</div>
                    </div>
                    <div>
                        <div class="mex-detail-label">Email</div>
                        <div class="mex-detail-value">{{ $studentData['email'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="mex-detail-label">Total Exemptions</div>
                        <div class="mex-detail-value"><span class="mex-badge"><i class="material-icons material-symbols-rounded">medical_services</i>{{ $studentData['total_exemption_count'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Exemptions --}}
        @if(isset($studentData['has_exemptions']) && $studentData['has_exemptions'] && count($studentData['exemptions']) > 0)
            <div class="ds-card ds-section">
                <div class="card-header">
                    <i class="material-icons material-symbols-rounded" style="color: var(--ds-primary);">history</i>
                    <span>Medical Exemptions</span>
                </div>
                <div class="card-body">
                    <div class="ds-table-wrap">
                        <table class="table align-middle mb-0 mex-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>OPD Category</th>
                                    <th>Description</th>
                                    <th class="text-center">Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentData['exemptions'] as $exemption)
                                    <tr>
                                        <td><strong>{{ $exemption['course_name'] }}</strong></td>
                                        <td>{{ $exemption['from_date'] ? \Carbon\Carbon::parse($exemption['from_date'])->format('d/m/Y') : 'N/A' }}</td>
                                        <td>{{ $exemption['to_date'] ? \Carbon\Carbon::parse($exemption['to_date'])->format('d/m/Y') : 'Ongoing' }}</td>
                                        <td>{{ $exemption['opd_category'] ?? 'N/A' }}</td>
                                        <td style="max-width: 280px; word-wrap: break-word;">{{ $exemption['description'] ?: '-' }}</td>
                                        <td class="text-center">
                                            @if($exemption['doc_upload'])
                                                <a href="{{ asset('storage/' . $exemption['doc_upload']) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary rounded-1 d-inline-flex align-items-center gap-1">
                                                    <i class="material-icons material-symbols-rounded" style="font-size: 16px;">description</i>
                                                    View
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="ds-empty-state">
                <i class="material-icons material-symbols-rounded d-block mb-2 opacity-50" style="font-size: 48px;">info</i>
                <p class="mb-0">No medical exemptions found.</p>
            </div>
        @endif

    {{-- ============================
        ADMIN VIEW
    ============================ --}}
    @else

        @if(isset($studentData) && count($studentData) > 0)
            @foreach($studentData as $student)
                <div class="ds-card ds-section">
                    <div class="mex-student-head">
                        <h6 class="mex-student-name">
                            {{ $student['student_name'] }}
                            <span class="mex-ot">({{ $student['ot_code'] }})</span>
                        </h6>
                        <span class="mex-badge">
                            <i class="material-icons material-symbols-rounded">medical_services</i>
                            {{ $student['exemption_count'] }} Exemption(s)
                        </span>
                    </div>

                    <div class="card-body">
                        @if($student['exemptions']->count() > 0)
                            <div class="ds-table-wrap">
                                <table class="table align-middle mb-0 mex-table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Category</th>
                                            <th>Speciality</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($student['exemptions'] as $exemption)
                                            <tr>
                                                <td><strong>{{ $exemption->course->course_name ?? 'N/A' }}</strong></td>
                                                <td>{{ $exemption->category->exemption_category_name ?? 'N/A' }}</td>
                                                <td>{{ $exemption->speciality->exemption_medical_speciality_name ?? 'N/A' }}</td>
                                                <td>{{ $exemption->from_date ? \Carbon\Carbon::parse($exemption->from_date)->format('d/m/Y') : 'N/A' }}</td>
                                                <td>{{ $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date)->format('d/m/Y') : 'Ongoing' }}</td>
                                                <td style="max-width: 280px; word-wrap: break-word;">{{ $exemption->Description ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="ds-empty-state">
                                <p class="mb-0">No medical exemptions found.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="ds-empty-state">
                <i class="material-icons material-symbols-rounded d-block mb-2 opacity-50" style="font-size: 48px;">info</i>
                <p class="mb-0">No student data found.</p>
            </div>
        @endif

    @endif
</div>

@endsection
