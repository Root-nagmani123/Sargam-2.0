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

/* Time sits beside its date, quieter than it, so the date still reads first. */
.me-text-time {
    margin-left: .35rem;
    padding: .05rem .35rem;
    border-radius: .25rem;
    background: #f1f3f5;
    color: #495057;
    font-size: .8em;
    font-weight: 500;
    white-space: nowrap;
}

.detail-value {
    font-size: .9rem;
    font-weight: 500;
    color: #212529;
}

/* =======================
   PRINT MODE
======================= */
/* The letterhead belongs to the printed sheet only. */
.me-print-head { display: none; }

@media print {
    @page { size: A4 portrait; margin: 12mm 10mm; }

    /* Print ONLY the report card. Hiding the shell selector by selector is
       fragile — the admin layout's wrappers change and a stray topbar or
       sidebar column leaks onto the page. Blanking everything and re-showing
       the print area is immune to that. */
    body * { visibility: hidden !important; }
    #meOtPrintArea, #meOtPrintArea * { visibility: visible !important; }
    #meOtPrintArea {
        position: absolute !important;
        left: 0; top: 0;
        width: 100% !important;
        margin: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    .d-print-none, .btn { display: none !important; }

    body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Keep the fills and the navy rules — a report printed as bare text loses
       the structure that makes it readable. */
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    .me-print-head {
        display: block !important;
        text-align: center;
        border-bottom: 2px solid #003366;
        padding-bottom: 6px;
        margin-bottom: 10px;
    }
    .me-print-head img { max-height: 52px; margin-bottom: 4px; }
    .me-print-head .mph-academy { font-size: 13pt; font-weight: 700; color: #003366; line-height: 1.25; }
    .me-print-head .mph-place { font-size: 8.5pt; color: #486581; }
    .me-print-head .mph-title {
        margin-top: 6px; font-size: 11.5pt; font-weight: 700; color: #004a93;
        text-transform: uppercase; letter-spacing: .02em;
    }
    .me-print-head .mph-meta { font-size: 8pt; color: #555; margin-top: 2px; }

    .card { box-shadow: none !important; border: 0 !important; page-break-inside: avoid; }
    .card-body { padding: 0 !important; }
    .info-card { margin-bottom: .5rem !important; }

    /* One exemption block per unit — never split across a page. */
    .exemption-item {
        page-break-inside: avoid;
        border: 1px solid #9bb0c9 !important;
        border-radius: 0 !important;
        padding: 8px 10px !important;
        margin-bottom: 8px !important;
    }

    /* The on-screen grid collapses to one long column on paper; two fixed
       columns keep a label beside its value. */
    .exemption-details {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 4px 14px !important;
    }
    .exemption-details > div { break-inside: avoid; }

    .detail-label { font-size: 7.5pt !important; color: #333 !important; }
    .detail-value { font-size: 9pt !important; color: #000 !important; }
    .me-text-time { background: #eceff3 !important; color: #000 !important; }

    .exemption-count-badge {
        background: #f0f4fa !important;
        border: 1px solid #cbd6e6 !important;
        color: #003366 !important;
    }

    .section-divider { border-top: 1px solid #cbd6e6 !important; }
}
</style>

<div class="container-fluid">
    <div class="d-print-none">
        <x-breadcrum title="Medical Exception OT View"></x-breadcrum>
    </div>

    <div class="card info-card" id="meOtPrintArea">
        <div class="card-body">

            {{-- Letterhead: print only. On screen the page already sits inside
                 the admin shell, which carries the branding. --}}
            <div class="me-print-head">
                <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
                <div class="mph-academy">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
                <div class="mph-place">Mussoorie, Uttarakhand</div>
                <div class="mph-title">Medical Exemption — Officer Trainee</div>
                <div class="mph-meta">
                    {{ $studentData['student_name'] ?? '' }}
                    @if(! empty($studentData['ot_code'])) ({{ $studentData['ot_code'] }}) @endif
                    &nbsp;|&nbsp; Printed on {{ now()->format('d-m-Y H:i') }}
                </div>
            </div>

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
                // array_key_exists, not isset: isset() is false for a NULL value, so
                // an OT whose generated_OT_code is NULL used to fall through to the
                // admin branch below — which iterates $studentData as a list of
                // students and fatals on this associative array. The admin payload is
                // numerically keyed, so the key test still tells the two shapes apart.
                $isStudentView = isset($studentData)
                    && is_array($studentData)
                    && array_key_exists('student_name', $studentData)
                    && array_key_exists('ot_code', $studentData);
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

                            {{-- from_date / to_date are datetime columns, so the date
                                 and the time are two views of one value. Time shows
                                 only where one was actually recorded — older rows
                                 were saved at midnight with no time entered. --}}
                            @php
                                $fromDate = $exemption['from_date'] ? \Carbon\Carbon::parse($exemption['from_date']) : null;
                                $toDate = $exemption['to_date'] ? \Carbon\Carbon::parse($exemption['to_date']) : null;
                            @endphp

                            <div class="exemption-details">
                                <div>
                                    <div class="detail-label">Doctor Name</div>
                                    <div class="detail-value">{{ $exemption['doctor_name'] ?: 'N/A' }}</div>
                                </div>

                                <div>
                                    <div class="detail-label">Date &amp; Time From</div>
                                    <div class="detail-value">
                                        @if($fromDate)
                                            {{ $fromDate->format('d/m/Y') }}
                                            @if($fromDate->format('H:i') !== '00:00')
                                                <span class="me-text-time">{{ $fromDate->format('h:i A') }}</span>
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="detail-label">Date &amp; Time To</div>
                                    <div class="detail-value">
                                        @if($toDate)
                                            {{ $toDate->format('d/m/Y') }}
                                            @if($toDate->format('H:i') !== '00:00')
                                                <span class="me-text-time">{{ $toDate->format('h:i A') }}</span>
                                            @endif
                                        @else
                                            Ongoing
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="detail-label">Exemption Category</div>
                                    <div class="detail-value">{{ $exemption['exemption_category'] ?: 'N/A' }}</div>
                                </div>

                                <div>
                                    <div class="detail-label">Medical Speciality</div>
                                    <div class="detail-value">{{ $exemption['medical_speciality'] ?: 'N/A' }}</div>
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

                                <div style="grid-column: 1 / -1;">
                                    <div class="detail-label">Diagnosis / Remarks</div>
                                    <div class="detail-value">{{ $exemption['description'] ?: 'N/A' }}</div>
                                </div>
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
