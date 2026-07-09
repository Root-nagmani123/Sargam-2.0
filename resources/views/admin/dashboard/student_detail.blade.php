@extends('admin.layouts.master')

@section('title', 'Student Details')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush
<style>
    .student-detail-page .sd-info-label {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        color: #667085;
        text-transform: uppercase;
    }
    .student-detail-page .sd-info-value {
        font-size: 0.95rem;
        font-weight: 500;
        color: #101828;
    }
    .student-detail-page .sd-stat {
        border: 1px solid #eef2f6;
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
    }
    .student-detail-page .sd-stat:hover {
        box-shadow: 0 6px 16px rgba(16, 24, 40, 0.08);
        transform: translateY(-2px);
        border-color: #e4e7ec;
    }
    .student-detail-page .sd-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .student-detail-page .sd-stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; color: #101828; }
    .student-detail-page .sd-stat-label { font-size: 0.8rem; color: #667085; }
    .student-detail-page .sd-section .card-header {
        background: #fff;
        border-bottom: 1px solid #eef2f6;
    }
    .student-detail-page .sd-section .card-title {
        font-size: 1rem;
        font-weight: 600;
        color: #101828;
    }
    .student-detail-page .sd-count-pill {
        font-size: 0.75rem;
        font-weight: 600;
        color: #475467;
        background: #f2f4f7;
        border-radius: 999px;
        padding: 0.1rem 0.55rem;
    }
</style>
<div class="container-fluid student-detail-page">
    <x-breadcrum title="Student Details" :showBack="true" />
    <x-session_message />

    @php
        // When a count is clicked on the student list, only the relevant section is shown.
        $validSections = ['medicalExceptionsSection', 'ptExemptionsSection', 'stationedLeavesSection', 'dutiesSection', 'noticesSection', 'memosSection'];
        $focusSection = request('section');
        if (!in_array($focusSection, $validSections, true)) {
            $focusSection = null;
        }
    @endphp

    @php
        $focusFrom = request('from_date');
        $focusTo = request('to_date');
        $focusRangeLabel = ($focusFrom && $focusTo)
            ? \Carbon\Carbon::parse($focusFrom)->format('d/m/Y') . ' – ' . \Carbon\Carbon::parse($focusTo)->format('d/m/Y')
            : ($focusFrom ? 'from ' . \Carbon\Carbon::parse($focusFrom)->format('d/m/Y')
                : ($focusTo ? 'up to ' . \Carbon\Carbon::parse($focusTo)->format('d/m/Y') : null));
    @endphp
    @if($focusSection)
        <div class="alert alert-light border d-flex align-items-center justify-content-between rounded-3 mb-4">
            <span class="text-secondary">
                <i class="bi bi-funnel me-2"></i>Showing a single section only.
                @if($focusRangeLabel)
                    <span class="ms-1">&middot; Period: <strong>{{ $focusRangeLabel }}</strong></span>
                @endif
            </span>
            <a href="{{ route('admin.dashboard.students.detail', request()->route('id')) }}" class="btn btn-sm btn-outline-primary rounded-2">
                <i class="bi bi-grid me-1"></i>View full details
            </a>
        </div>
    @endif

    <!-- Student Basic Information -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="sd-info-label">Student Name</div>
                    <div class="sd-info-value">{{ $student->display_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="sd-info-label">OT Code</div>
                    <div class="sd-info-value">{{ $student->generated_OT_code ?? 'N/A' }}</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="sd-info-label">Email</div>
                    <div class="sd-info-value">{{ $student->email ?? 'N/A' }}</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="sd-info-label">Service</div>
                    <div class="sd-info-value">{{ $student->service->service_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

 {{--   @if($fcRegUsername !== '')
        <div class="card mb-4" id="fcJoiningDocumentsSection">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-file-upload me-2"></i>Foundation course – joining documents
                </h6>
                <small class="text-muted">Registration login: <code>{{ $fcRegUsername }}</code></small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Document</th>
                                <th class="text-center">Mandatory</th>
                                <th class="text-center">Uploaded</th>
                                <th class="text-center">Verified</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fcJoiningDocuments as $i => $doc)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $doc->documentMaster?->document_name ?? $doc->document_name }}</td>
                                    <td class="text-center">
                                        @if($doc->documentMaster?->is_mandatory)
                                            <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">Yes</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($doc->is_uploaded)
                                            @php $docUrl = view_file_link($doc->file_path ?? null); @endphp
                                            @if($docUrl)
                                                <a href="{{ $docUrl }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:10px;">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                            @else
                                                <span class="text-warning small">Path missing</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">Not uploaded</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($doc->is_verified)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @else
                                            <i class="fas fa-clock text-warning"></i>
                                        @endif
                                    </td>
                                    <td class="small">{{ $doc->remarks ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        No active joining document types in the master checklist. Configure them under FC Form Builder (document masters).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif --}}

    <!-- Summary Cards -->
    @unless($focusSection)
    <div class="row g-3 mb-4">
        @php
            $sdStats = [
                ['medicalExceptionsSection', $medicalExemptions->count(), 'Medical Exceptions', 'bi-heart-pulse-fill', '#b42318', '#fef3f2'],
                ['ptExemptionsSection', number_format((float) $ptExemptions->sum('total_days'), 0), 'PT Exemptions', 'bi-person-walking', '#027a48', '#ecfdf3'],
                ['stationedLeavesSection', number_format((float) $stationedLeaves->sum('total_days'), 0), 'Station Leave', 'bi-geo-alt-fill', '#5925dc', '#f4f3ff'],
                ['dutiesSection', $duties->count(), 'Duties Assigned', 'bi-list-task', '#b54708', '#fffaeb'],
                ['noticesSection', $notices->count(), 'Notices Received', 'bi-bell-fill', '#026aa2', '#f0f9ff'],
                ['memosSection', $memos->count(), 'Memos Issued', 'bi-file-earmark-text-fill', '#475467', '#f2f4f7'],
            ];
        @endphp
        @foreach($sdStats as [$sectionId, $count, $label, $icon, $color, $bg])
            <div class="col-lg-2 col-md-4 col-6">
                <div class="sd-stat h-100 p-3 d-flex align-items-center gap-3" onclick="scrollToSection('{{ $sectionId }}')">
                    <span class="sd-stat-icon" style="background: {{ $bg }}; color: {{ $color }};"><i class="bi {{ $icon }}"></i></span>
                    <div>
                        <div class="sd-stat-value">{{ $count }}</div>
                        <div class="sd-stat-label">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endunless

    <!-- Medical Exceptions -->
    @if(!$focusSection || $focusSection === 'medicalExceptionsSection')
    <div class="card border-0 shadow-sm rounded-3 mb-4 sd-section" id="medicalExceptionsSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Medical Exceptions ({{ $medicalExemptions->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-2" onclick="exportTableToExcel('medicalExemptionsTable', 'Medical_Exceptions')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2" onclick="printTable('medicalExemptionsTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($medicalExemptions->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover programme-dt-table align-middle mb-0" id="medicalExemptionsTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Category</th>
                                <th>Speciality</th>
                                <th>Description</th>
                                <th>Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($medicalExemptions as $exemption)
                                <tr>
                                    <td>{{ $exemption->course->course_name ?? 'N/A' }}</td>
                                    <td>{{ $exemption->from_date ? \Carbon\Carbon::parse($exemption->from_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                    <td>{{ $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                    <td>{{ $exemption->category->exemp_category_name ?? 'N/A' }}</td>
                                    <td>{{ $exemption->speciality->speciality_name ?? 'N/A' }}</td>
                                    <td>{{ $exemption->Description ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        @if($exemption->Doc_upload)
                                            <a href="{{ asset('storage/' . $exemption->Doc_upload) }}" target="_blank"
                                                class="btn btn-sm btn-info" title="View Document" data-bs-toggle="tooltip"
                                                data-bs-placement="top">
                                                <i class="fas fa-file-pdf"></i> View
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
            @else
                <p class="text-muted">No medical exceptions found.</p>
            @endif
        </div>
    </div>

    @endif

    <!-- PT Exemptions -->
    @if(!$focusSection || $focusSection === 'ptExemptionsSection')
    <div class="card border-0 shadow-sm rounded-3 mb-4 sd-section" id="ptExemptionsSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-running me-2"></i>PT Exemptions ({{ number_format((float) $ptExemptions->sum('total_days'), 0) }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-2" onclick="exportTableToExcel('ptExemptionsTable', 'PT_Exemptions')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2" onclick="printTable('ptExemptionsTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($ptExemptions->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover programme-dt-table align-middle mb-0" id="ptExemptionsTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Nature</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Total Days</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ptExemptions as $leave)
                                <tr>
                                    <td>{{ $leave->course->course_name ?? 'N/A' }}</td>
                                    <td>{{ $leave->nature->nature_name ?? 'N/A' }}</td>
                                    <td>{{ $leave->from_date ? $leave->from_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $leave->to_date ? $leave->to_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ number_format((float) $leave->total_days, 0) }}</td>
                                    <td>
                                        <span class="badge {{ $leave->status_badge_class }}">{{ $leave->status_label }}</span>
                                    </td>
                                    <td>{{ $leave->reason ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        @if($leave->attachments->isNotEmpty())
                                            @foreach($leave->attachments as $attachment)
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-info mb-1" title="View Document">
                                                    <i class="fas fa-file-pdf"></i> View
                                                </a>
                                            @endforeach
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No PT exemptions found.</p>
            @endif
        </div>
    </div>

    @endif

    <!-- Station Leave -->
    @if(!$focusSection || $focusSection === 'stationedLeavesSection')
    <div class="card border-0 shadow-sm rounded-3 mb-4 sd-section" id="stationedLeavesSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Station Leave ({{ number_format((float) $stationedLeaves->sum('total_days'), 0) }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-2" onclick="exportTableToExcel('stationedLeavesTable', 'Station_Leave')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2" onclick="printTable('stationedLeavesTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($stationedLeaves->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover programme-dt-table align-middle mb-0" id="stationedLeavesTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Nature</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Total Days</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Reason</th>
                                <th>Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stationedLeaves as $leave)
                                <tr>
                                    <td>{{ $leave->course->course_name ?? 'N/A' }}</td>
                                    <td>{{ $leave->nature->nature_name ?? 'N/A' }}</td>
                                    <td>{{ $leave->from_date ? $leave->from_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $leave->to_date ? $leave->to_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ number_format((float) $leave->total_days, 0) }}</td>
                                    <td>
                                        <span class="badge {{ $leave->status_badge_class }}">{{ $leave->status_label }}</span>
                                    </td>
                                    <td>{{ $leave->action_by_faculty_name }}</td>
                                    <td>{{ $leave->reason ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        @if($leave->attachments->isNotEmpty())
                                            @foreach($leave->attachments as $attachment)
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-info mb-1" title="View Document">
                                                    <i class="fas fa-file-pdf"></i> View
                                                </a>
                                            @endforeach
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No station leave applications found.</p>
            @endif
        </div>
    </div>

    @endif

    <!-- Duties Assigned -->
    @if(!$focusSection || $focusSection === 'dutiesSection')
    <div class="card border-0 shadow-sm rounded-3 mb-4 sd-section" id="dutiesSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Duties Assigned ({{ $duties->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-2" onclick="exportTableToExcel('dutiesTable', 'Duties_Assigned')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2" onclick="printTable('dutiesTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($duties->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover programme-dt-table align-middle mb-0" id="dutiesTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Duty Type</th>
                                <th>Date</th>
                                <th>Time From</th>
                                <th>Time To</th>
                                <th>Faculty</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($duties as $duty)
                                <tr>
                                    <td>{{ $duty->courseMaster->course_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $duty->mdoDutyTypeMaster->mdo_duty_type_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $duty->mdo_date ? \Carbon\Carbon::parse($duty->mdo_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                    <td>{{ $duty->Time_from ?? 'N/A' }}</td>
                                    <td>{{ $duty->Time_to ?? 'N/A' }}</td>
                                    <td>{{ $duty->facultyMaster->full_name ?? 'N/A' }}</td>
                                    <td>{{ $duty->Remark ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No duties assigned.</p>
            @endif
        </div>
    </div>

    @endif

    <!-- Notices Received -->
    @if(!$focusSection || $focusSection === 'noticesSection')
    <div class="card border-0 shadow-sm rounded-3 mb-4 sd-section" id="noticesSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notices Received ({{ $notices->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-2" onclick="exportTableToExcel('noticesTable', 'Notices_Received')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2" onclick="printTable('noticesTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($notices->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover programme-dt-table align-middle mb-0" id="noticesTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Session Date</th>
                                <th>Topic</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notices as $notice)
                                <tr>
                                    <td>{{ $notice->course_name ?? 'N/A' }}</td>
                                    <td>{{ $notice->session_date ? \Carbon\Carbon::parse($notice->session_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                    <td>{{ $notice->topic ?? 'N/A' }}</td>
                                    <td>
                                        @if($notice->status == 1)
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($notice->status == 2)
                                            <span class="badge bg-danger">Escalated</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No notices received.</p>
            @endif
        </div>
    </div>

    @endif

    <!-- Memos Issued -->
    @if(!$focusSection || $focusSection === 'memosSection')
    <div class="card border-0 shadow-sm rounded-3 mb-4 sd-section" id="memosSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Memos Issued ({{ $memos->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-2" onclick="exportTableToExcel('memosTable', 'Memos_Issued')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2" onclick="printTable('memosTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($memos->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover programme-dt-table align-middle mb-0" id="memosTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Session Date</th>
                                <th>Topic</th>
                                <th>Conclusion Type</th>
                                <th>Status</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($memos as $memo)
                                <tr>
                                    <td>{{ $memo->course_name ?? 'N/A' }}</td>
                                    <td>{{ $memo->session_date ? \Carbon\Carbon::parse($memo->session_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                    <td>{{ $memo->topic ?? 'N/A' }}</td>
                                    <td>{{ $memo->conclusion_type ?? 'N/A' }}</td>
                                    <td>
                                        @if($memo->status == 1)
                                            <span class="badge bg-success">Recorded</span>
                                        @elseif($memo->status == 2)
                                            <span class="badge bg-warning">Memo Sent</span>
                                        @else
                                            <span class="badge bg-secondary">Closed</span>
                                        @endif
                                    </td>
                                    <td>{{ $memo->response ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No memos issued.</p>
            @endif
        </div>
    </div>

    @endif

    <!-- Attendance Summary -->
    @if(!$focusSection && $attendanceSummary && $attendanceSummary->total_sessions > 0)
    <div class="card border-0 shadow-sm rounded-3 mb-4 sd-section">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Attendance Summary</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-2" onclick="exportTableToExcel('attendanceSummaryTable', 'Attendance_Summary')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2" onclick="printTable('attendanceSummaryTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Hidden table for export/print -->
            <table class="table table-bordered" id="attendanceSummaryTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Present</td>
                        <td>{{ $attendanceSummary->present_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Late</td>
                        <td>{{ $attendanceSummary->late_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Absent</td>
                        <td>{{ $attendanceSummary->absent_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Not Marked</td>
                        <td>{{ $attendanceSummary->not_marked_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Marked Sessions</td>
                        <td>{{ $attendanceSummary->total_sessions ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Total Sessions</td>
                        <td>{{ $attendanceSummary->total_expected_sessions ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
            <!-- Display cards -->
            <div class="row">
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-success">{{ $attendanceSummary->present_count ?? 0 }}</h4>
                        <small>Present</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-warning">{{ $attendanceSummary->late_count ?? 0 }}</h4>
                        <small>Late</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-danger">{{ $attendanceSummary->absent_count ?? 0 }}</h4>
                        <small>Absent</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-secondary">{{ $attendanceSummary->not_marked_count ?? 0 }}</h4>
                        <small>Not Marked</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-info">{{ $attendanceSummary->total_sessions ?? 0 }}</h4>
                        <small>Marked Sessions</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-primary">{{ $attendanceSummary->total_expected_sessions ?? 0 }}</h4>
                        <small>Total Sessions</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    // Function to scroll to section smoothly
    function scrollToSection(sectionId) {
        const element = document.getElementById(sectionId);
        if (!element) {
            console.error('Element not found:', sectionId);
            return;
        }

        // Try multiple scroll methods for better compatibility
        try {
            // Method 1: Use scrollIntoView (most reliable)
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
                inline: 'nearest'
            });

            // Method 2: Also try scrolling the container if scrollIntoView doesn't work well
            setTimeout(() => {
                // Find scrollable container
                let scrollContainer = document.querySelector('.body-wrapper');
                if (!scrollContainer) {
                    scrollContainer = document.querySelector('.page-wrapper');
                }
                
                if (scrollContainer && scrollContainer !== window) {
                    const containerRect = scrollContainer.getBoundingClientRect();
                    const elementRect = element.getBoundingClientRect();
                    const relativeTop = elementRect.top - containerRect.top + scrollContainer.scrollTop;
                    const offset = 100;
                    const targetScroll = Math.max(0, relativeTop - offset);
                    
                    scrollContainer.scrollTo({
                        top: targetScroll,
                        behavior: 'smooth'
                    });
                } else {
                    // Fallback to window scroll
                    const elementTop = element.getBoundingClientRect().top + window.pageYOffset;
                    const offset = 100;
                    const targetPosition = Math.max(0, elementTop - offset);
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }, 100);
        } catch (e) {
            console.error('Scroll error:', e);
            // Fallback: simple scroll
            element.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Highlight the section briefly
        setTimeout(() => {
            element.style.transition = 'box-shadow 0.3s ease';
            element.style.boxShadow = '0 0 20px rgba(0, 74, 147, 0.5)';
            setTimeout(() => {
                element.style.boxShadow = '';
            }, 2000);
        }, 500);
    }

    // Function to export table to Excel
    function exportTableToExcel(tableId, fileName) {
        const table = document.getElementById(tableId);
        if (!table) {
            alert('Table not found!');
            return;
        }

        // Get section title from card header h5
        const card = table.closest('.card');
        const sectionTitle = card ? (card.querySelector('.card-header h5')?.textContent?.trim() || fileName) : fileName;

        // Student details
        const studentFullName = '{{ $student->display_name ?? trim(($student->first_name ?? "") . " " . ($student->last_name ?? "")) }}';
        const otCode = '{{ $student->generated_OT_code ?? "N/A" }}';

        // Clone the table
        const clonedTable = table.cloneNode(true);

        // Remove badges and convert to plain text
        clonedTable.querySelectorAll('.badge').forEach(badge => {
            badge.parentElement.textContent = badge.textContent.trim();
        });

        // Convert document links to text for Excel export
        clonedTable.querySelectorAll('a[target="_blank"]').forEach(link => {
            if (link.textContent.includes('View')) {
                link.outerHTML = 'Available';
            }
        });

        // Count columns for spanning header rows
        const colCount = clonedTable.querySelectorAll('thead th').length || clonedTable.querySelectorAll('tr:first-child td').length || 1;

        // Build a wrapper table with header rows prepended
        const wrapperTable = document.createElement('table');

        const makeHeaderRow = (text) => {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = colCount;
            td.textContent = text;
            tr.appendChild(td);
            return tr;
        };

        wrapperTable.appendChild(makeHeaderRow(sectionTitle));
        wrapperTable.appendChild(makeHeaderRow(`Student: ${studentFullName} | OT Code: ${otCode}`));

        // Empty spacer row
        const spacer = document.createElement('tr');
        spacer.appendChild(document.createElement('td'));
        wrapperTable.appendChild(spacer);

        // Append all data rows from cloned table
        clonedTable.querySelectorAll('tr').forEach(row => {
            wrapperTable.appendChild(row.cloneNode(true));
        });

        // Create workbook from wrapper table
        const wb = XLSX.utils.table_to_book(wrapperTable, {sheet: fileName});

        // Generate filename with student name and current date
        const studentName = studentFullName.replace(/[^a-z0-9]/gi, '_');
        const date = new Date().toISOString().split('T')[0];
        const finalFileName = `${studentName}_${fileName}_${date}.xlsx`;

        // Save file
        XLSX.writeFile(wb, finalFileName);
    }

    // Function to print table
    function printTable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) {
            alert('Table not found!');
            return;
        }

        // Get table title from card header
        const card = table.closest('.card');
        const title = card ? card.querySelector('.card-header h5')?.textContent || 'Table' : 'Table';
        
        // Clone table for printing
        const clonedTable = table.cloneNode(true);
        
        // Remove badges and convert to plain text for print
        clonedTable.querySelectorAll('.badge').forEach(badge => {
            const text = badge.textContent.trim();
            badge.outerHTML = text;
        });
        
        // Convert document links to text for print
        clonedTable.querySelectorAll('a[target="_blank"]').forEach(link => {
            if (link.textContent.includes('View')) {
                link.outerHTML = 'Available';
            }
        });
        
        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print ${title}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 20px;
                        }
                        h2 {
                            color: #004a93;
                            margin-bottom: 20px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                        }
                        th, td {
                            border: 1px solid #ddd;
                            padding: 8px;
                            text-align: left;
                        }
                        th {
                            background-color: #004a93;
                            color: white;
                            font-weight: bold;
                        }
                        tr:nth-child(even) {
                            background-color: #f7f7f7;
                        }
                        @media print {
                            body { margin: 0; }
                            @page { margin: 1cm; }
                        }
                    </style>
                </head>
                <body>
                    <h2>${title}</h2>
                    <p><strong>Student:</strong> {{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }} | <strong>OT Code:</strong> {{ $student->generated_OT_code ?? 'N/A' }}</p>
                    <p><strong>Print Date:</strong> ${new Date().toLocaleString()}</p>
                    ${clonedTable.outerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }
</script>
@endpush

@endsection

