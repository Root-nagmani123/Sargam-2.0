@extends('admin.layouts.master')

@section('title', 'Medical Exception OT View - Sargam | Lal Bahadur')

@section('setup_content')

<style>
/* =======================
   GLOBAL CARD STYLES
======================= */
.info-card {
    background: #ffffff;
    border-left: 4px solid #004a93;
    border-radius: .75rem;
    box-shadow: 0 .25rem .75rem rgba(0,0,0,.05);
}

.section-divider {
    border-top: 1px solid #e9ecef;
    margin: 1.5rem 0;
}

/* =======================
   STUDENT HEADER
======================= */
.student-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px dashed #dee2e6;
}

/* =======================
   BADGES
======================= */
.exemption-count-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: #212529;
    font-weight: 600;
    padding: .5rem 1rem;
    border-radius: 999px;
    font-size: .9rem;
}

/* =======================
   EXEMPTION CARDS
======================= */
.exemption-item {
    background: #f8f9fa;
    border-left: 3px solid #b72a2a;
    border-radius: .5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.exemption-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: .75rem;
}

/* =======================
   LABEL / VALUE
======================= */
.detail-label {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6c757d;
    font-weight: 600;
}

.detail-value {
    font-size: .9rem;
    font-weight: 500;
    color: #212529;
}

/* =======================
   PRINT MODE
======================= */
@media print {
    /* Hide unnecessary elements */
    .btn,
    .breadcrumb,
    nav,
    .navbar,
    .sidebar,
    .header,
    footer,
    .d-print-none {
        display: none !important;
    }
    
    /* Reset body and container */
    body {
        background: white !important;
        margin: 0 !important;
        padding: 20px !important;
    }
    
    .container-fluid {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Card styling for print */
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
        page-break-inside: avoid;
    }
    
    .info-card {
        margin-bottom: 1rem !important;
    }
    
    /* Prevent page breaks inside exemption items */
    .exemption-item {
        page-break-inside: avoid;
    }
    
    /* Optimize colors for print */
    .exemption-count-badge {
        background: #f8f9fa !important;
        border: 1px solid #dee2e6 !important;
        color: #212529 !important;
    }
    
    /* Ensure text is readable */
    .detail-value,
    .detail-label {
        color: #000 !important;
    }
}
</style>

<div class="container-fluid">
    <div class="d-print-none">
        <x-breadcrum title="Medical Exception OT View"></x-breadcrum>
    </div>

    <div class="card info-card">
        <div class="card-body">

            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1 fw-semibold">Medical Exception OT View</h4>
                    <small class="text-muted">Medical exemption summary and history</small>
                </div>

                <button type="button"
                        class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1"
                        onclick="window.print()">
                    <i class="material-icons material-symbols-rounded fs-6">print</i>
                    Print
                </button>
            </div>

            <div class="section-divider"></div>

            @php
                $isStudentView = isset($studentData)
                    && isset($studentData['student_name'])
                    && isset($studentData['ot_code']);
            @endphp

            {{-- ============================
               STUDENT LOGIN VIEW
            ============================ --}}
            @if($isStudentView)

                <div class="card info-card mb-4">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <div class="detail-label">Student Name</div>
                                <div class="detail-value fs-6">{{ $studentData['student_name'] }}</div>
                            </div>

                            <div class="col-md-3">
                                <div class="detail-label">OT Code</div>
                                <div class="detail-value fs-6">{{ $studentData['ot_code'] }}</div>
                            </div>

                            <div class="col-md-3">
                                <div class="detail-label">Email</div>
                                <div class="detail-value fs-6">{{ $studentData['email'] ?? 'N/A' }}</div>
                            </div>

                            <div class="col-md-3">
                                <div class="detail-label">Total Exemptions</div>
                                <div class="detail-value fs-6 fw-bold text-primary">
                                    {{ $studentData['total_exemption_count'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- EXEMPTIONS --}}
                @if(isset($studentData['has_exemptions']) && $studentData['has_exemptions'] && count($studentData['exemptions']) > 0)
                    @foreach($studentData['exemptions'] as $exemption)
                        <div class="exemption-item">
                            <h6 class="fw-semibold text-primary mb-3 d-flex align-items-center gap-2">
                                <i class="material-icons material-symbols-rounded fs-6">school</i>
                                {{ $exemption['course_name'] }}
                            </h6>

                            <div class="exemption-details">
                                <div>
                                    <div class="detail-label">From Date</div>
                                    <div class="detail-value">
                                        {{ $exemption['from_date'] ? \Carbon\Carbon::parse($exemption['from_date'])->format('d/m/Y') : 'N/A' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="detail-label">To Date</div>
                                    <div class="detail-value">
                                        {{ $exemption['to_date'] ? \Carbon\Carbon::parse($exemption['to_date'])->format('d/m/Y') : 'Ongoing' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="detail-label">OPD Category</div>
                                    <div class="detail-value">{{ $exemption['opd_category'] ?? 'N/A' }}</div>
                                </div>

                                @if($exemption['doc_upload'])
                                <div>
                                    <div class="detail-label">Document</div>
                                    <a href="{{ asset('storage/' . $exemption['doc_upload']) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </div>
                                @endif

                                @if($exemption['description'])
                                <div style="grid-column: 1 / -1;">
                                    <div class="detail-label">Description</div>
                                    <div class="detail-value">{{ $exemption['description'] }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info text-center">
                        No medical exemptions found.
                    </div>
                @endif

            {{-- ============================
               ADMIN VIEW
            ============================ --}}
            @else

                @if(isset($studentData) && count($studentData) > 0)
                    @foreach($studentData as $student)
                        <div class="card info-card mb-4">
                            <div class="card-body">

                                <div class="student-header mb-3">
                                    <h6 class="mb-0 fw-semibold">
                                        {{ $student['student_name'] }}
                                        <span class="text-muted fw-normal">({{ $student['ot_code'] }})</span>
                                    </h6>

                                    <span class="exemption-count-badge">
                                        {{ $student['exemption_count'] }} Exemptions
                                    </span>
                                </div>

                                @if($student['exemptions']->count() > 0)
                                    @foreach($student['exemptions'] as $exemption)
                                        <div class="exemption-item">
                                            <div class="exemption-details">
                                                <div>
                                                    <div class="detail-label">Course</div>
                                                    <div class="detail-value">{{ $exemption->course->course_name ?? 'N/A' }}</div>
                                                </div>

                                                <div>
                                                    <div class="detail-label">Category</div>
                                                    <div class="detail-value">{{ $exemption->category->exemption_category_name ?? 'N/A' }}</div>
                                                </div>

                                                <div>
                                                    <div class="detail-label">Speciality</div>
                                                    <div class="detail-value">{{ $exemption->speciality->exemption_medical_speciality_name ?? 'N/A' }}</div>
                                                </div>

                                                <div>
                                                    <div class="detail-label">From Date</div>
                                                    <div class="detail-value">
                                                        {{ $exemption->from_date ? \Carbon\Carbon::parse($exemption->from_date)->format('d/m/Y') : 'N/A' }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="detail-label">To Date</div>
                                                    <div class="detail-value">
                                                        {{ $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date)->format('d/m/Y') : 'Ongoing' }}
                                                    </div>
                                                </div>

                                                @if($exemption->Description)
                                                <div style="grid-column: 1 / -1;">
                                                    <div class="detail-label">Description</div>
                                                    <div class="detail-value">{{ $exemption->Description }}</div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted mb-0">No medical exemptions found.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info text-center">
                        No student data found.
                    </div>
                @endif

            @endif

        </div>
    </div>
</div>

@endsection
