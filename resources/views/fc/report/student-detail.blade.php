@extends('admin.layouts.master')
@section('title', 'Student Profile – ' . ($displayName ?? $userId))

@push('styles')
<style>
    @page {
        size: A4;
        margin: 8mm 10mm 10mm 10mm;
    }
    @media print {
        html, body {
            background: #fff !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* ── Hide all chrome ── */
        .sargam-loader, #sargamLoader, .topbar, header.topbar, .header-top-bar,
        .left-sidebar, .side-mini-panel, aside.side-mini-panel,
        #sidebarTabContent, .mobile-tabbar, #mainNavbar, .navbar, footer,
        .no-print, .btn, button, form, .form-check, .table th:last-child,
        .table td:last-child, .progress, .breadcrumb {
            display: none !important;
        }

        /* ── Keep only the print area visible ── */
        body * { visibility: hidden; }
        .student-report-print-area,
        .student-report-print-area * { visibility: visible !important; }
        .student-report-print-area {
            position: absolute;
            left: 0 !important; top: 0 !important;
            width: 100% !important; max-width: 100% !important;
            padding: 0 !important;
            box-sizing: border-box;
        }

        /* ── Government-form double rule on every printed page ──
           position:fixed repeats per page; a bordered wrapper would only draw its top edge
           on page 1 and its bottom edge on the last page. */
        .print-page-frame,
        .print-page-frame-inner {
            display: block !important;
            visibility: visible !important;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
            pointer-events: none;
        }
        .print-page-frame { border: 1.6pt solid #0a3d6b; }
        .print-page-frame-inner { border: 0.5pt solid #0a3d6b; margin: 2.4pt; }
        .student-report-print-area { padding: 3mm 3.5mm 2mm !important; }

        /* ── Print masthead ── */
        .print-masthead {
            display: block !important;
            border: 2px solid #0a3d6b;
            padding: 5px 10px;
            margin-bottom: 4px;
            background: #f0f5fb !important;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-masthead-hi   { font-size: 10.5pt; font-weight: bold; color: #0a3d6b; margin: 0 0 1px; }
        .print-masthead-en   { font-size: 8.8pt;  color: #333; margin: 0 0 1px; }
        .print-masthead-name { font-size: 9.5pt;  font-weight: bold; color: #0a3d6b; margin: 0; }
        .print-masthead-sub  { font-size: 8pt;    color: #555; margin: 0; }
        /* Crest on the left; the empty right cell of equal width keeps the name centred. */
        .print-masthead-grid { width: 100%; border-collapse: collapse; }
        .print-masthead-grid td { border: 0; padding: 0; vertical-align: middle; }
        .print-masthead-side { width: 58px; }
        .print-masthead-logo { text-align: left; }
        .print-masthead-logo img { width: 54px; height: auto; }

        /* Specimen signature under the photograph in the identity box */
        .student-hero-card .hero-sign-box {
            width: 72px;
            margin-top: 4px;
            border: 1px solid #888 !important;
            background: #fff !important;
            padding: 2px;
            text-align: center;
        }
        .student-hero-card .hero-sign-box img { width: 66px; height: auto; max-height: 28px; }
        .student-hero-card .hero-sign-cap { font-size: 5.5pt; color: #444 !important; line-height: 1.2; }
        .print-doc-title {
            display: block !important;
            text-align: center;
            margin: 3px 0 4px;
            padding: 3px;
            border-top: 1px solid #0a3d6b;
            border-bottom: 2px solid #0a3d6b;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.6px;
            color: #0a3d6b;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Identity card ── */
        .student-hero-card {
            border: 1.5px solid #0a3d6b !important;
            border-radius: 0 !important;
            background: none !important;
            color: #111 !important;
            box-shadow: none !important;
            margin-bottom: 4px !important;
        }
        .student-hero-card .hero-photo {
            width: 72px; height: 88px;
            object-fit: cover;
            border: 1px solid #888;
            border-radius: 3px;
        }
        .student-hero-card .hero-status { display: none !important; }
        .student-hero-card .hero-name   { font-size: 12pt !important; color: #000 !important; }
        .student-hero-card .hero-meta { color: #222 !important; }
        .student-hero-card .hero-meta span { color: #222 !important; font-size: 8.5pt !important; }
        /* Hidden on screen, restored for the printed document. */
        .student-hero-card .hero-course-line { display: flex !important; }

        /* ── Progress bar: skip ── */
        .print-progress { display: none !important; }

        /* ── Section cards ── */
        .card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            border-radius: 0 !important;
            margin-bottom: 4px !important;
            /* Do NOT avoid page-break inside cards — large sections would jump
               to the next page and leave a blank gap. Only avoid orphaned headers. */
            break-inside: auto !important;
            page-break-inside: auto !important;
        }
        /* Prevent header being stranded at the bottom of a page without content */
        .card-header {
            break-after: avoid-page !important;
            page-break-after: avoid !important;
            background: #0a3d6b !important;
            color: #fff !important;
            padding: 3px 7px !important;
            font-size: 8.8pt !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        /* Keep individual field rows together */
        .field-row {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }
        /* Keep table rows together */
        .table tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }
        .card-header .text-muted { color: rgba(255,255,255,.8) !important; }

        /* ── 2-up fields grid ── */
        .section-fields {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 0 !important;
        }
        .section-fields .field-group-hd {
            grid-column: 1 / -1;
            background: #dce6f0 !important;
            color: #0a3d6b !important;
            font-size: 8.2pt !important;
            font-weight: bold !important;
            padding: 2px 6px !important;
            border-bottom: 1px solid #b0bfce !important;
            break-after: avoid-page !important;
            page-break-after: avoid !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .section-fields .field-row {
            display: flex;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd;
            font-size: 8.2pt !important;
        }
        .section-fields .field-row:nth-child(odd) { border-left: 1px solid #ddd; }
        .section-fields .field-lbl {
            width: 42%;
            background: #f0f4f8 !important;
            color: #0a2a50 !important;
            font-weight: 600 !important;
            font-size: 8pt !important;
            padding: 2px 5px !important;
            flex-shrink: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .section-fields .field-val {
            padding: 2px 5px !important;
            font-size: 8.2pt !important;
            word-break: break-word;
        }

        /* ── Tables ── */
        .table { font-size: 8pt !important; }
        .table th { font-size: 8pt !important; }
        .table td { padding: 2px 4px !important; }
        /* Hide last column (admin action) from document table in print */
        .doc-table th:last-child,
        .doc-table td:last-child { display: none !important; }

        a[href]:after { content: none !important; }
    }

    /* Screen-only helpers */
    .print-masthead, .print-doc-title,
    .print-page-frame, .print-page-frame-inner { display: none; }

    /* ── Identity header ──
       The trainee name inherited the admin theme's dark heading colour, which on the blue
       card was all but unreadable; it has to be set explicitly. */
    .student-hero-card .hero-name {
        color: #fff !important;
        font-size: 1.15rem;
        line-height: 1.25;
        letter-spacing: .2px;
    }
    .student-hero-card .hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .15rem 1.25rem;
        font-size: .82rem;
        color: rgba(255, 255, 255, .92);
    }
    .student-hero-card .hero-meta span { white-space: nowrap; }
    .student-hero-card .hero-meta i { opacity: .8; }
    /* Course / duration / coordinator: printed document only, not the working screen. */
    .student-hero-card .hero-course-line { display: none; }

    /* ── Screen-only: compact "Descriptive Roll" layout matching the downloaded PDF.
         Wrapped in @media screen so print output (already PDF-matched above) is untouched. ── */
    @media screen {
        .student-report-page .dyn-section { border: 1px solid #cdd9e6 !important; }
        .student-report-page .dyn-section-hd {
            background: #0a3d6b !important;
            color: #fff !important;
            border-bottom: 1px solid #0a3d6b !important;
        }
        .student-report-page .section-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        .student-report-page .section-fields .field-group-hd {
            grid-column: 1 / -1;
            background: #dce6f0 !important;
            color: #0a3d6b !important;
            font-weight: 600;
        }
        .student-report-page .section-fields .field-row {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .student-report-page .section-fields .field-row:nth-child(odd) {
            border-left: 1px solid #e2e8f0;
        }
        .student-report-page .section-fields .field-lbl {
            width: 42%;
            padding: 5px 8px !important;
            background: #f0f4f9 !important;
            color: #0a2a50 !important;
            font-weight: 600;
            flex-shrink: 0;
        }
        .student-report-page .section-fields .field-val {
            padding: 5px 8px !important;
            word-break: break-word;
        }
    }
    /* Stack to single column on small screens */
    @media screen and (max-width: 575.98px) {
        .student-report-page .section-fields { grid-template-columns: 1fr; }
        .student-report-page .section-fields .field-row { border-left: 1px solid #e2e8f0; }
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-3 student-report-page">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="{{ request('ref') ? request('ref') : route('admin.reports.overview') }}">Reports</a>
                </li>
                <li class="breadcrumb-item active">{{ $displayName ?: $userId }}</li>
            </ol>
        </nav>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.reports.student.documents', ['username' => $userId] + (request('ref') ? ['ref' => request('ref')] : [])) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-file-earmark-check me-1"></i>Document Verification
            </a>
            <a href="{{ route('admin.reports.student.pdf', ['username' => $userId]) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
            </a>
            {{-- Opens the PDF template as HTML and prints that, so paper output matches the
                 downloaded PDF exactly instead of printing this screen's own layout. --}}
            <a href="{{ route('admin.reports.student.print', ['username' => $userId]) }}"
               target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </a>
        </div>
    </div>

    <div class="student-report-print-area">

    {{-- Print-only page frame (repeats on every printed page) --}}
    <div class="print-page-frame" aria-hidden="true"></div>
    <div class="print-page-frame-inner" aria-hidden="true"></div>

    {{-- Print-only masthead --}}
    <div class="print-masthead">
        <table class="print-masthead-grid">
            <tr>
                @if(!empty($lbsnaaLogoUrl))
                    <td class="print-masthead-side print-masthead-logo">
                        <img src="{{ $lbsnaaLogoUrl }}" alt="LBSNAA">
                    </td>
                @endif
                <td>
                    <p class="print-masthead-hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी</p>
                    <p class="print-masthead-en">Lal Bahadur Shastri National Academy of Administration</p>
                    <p class="print-masthead-name">Government of India &nbsp;|&nbsp; भारत सरकार</p>
                    <p class="print-masthead-sub">Mussoorie – Uttarakhand &nbsp;|&nbsp; मसूरी – उत्तराखंड</p>
                </td>
                @if(!empty($lbsnaaLogoUrl))
                    <td class="print-masthead-side">&nbsp;</td>
                @endif
            </tr>
        </table>
    </div>
    <div class="print-doc-title">DESCRIPTIVE REGISTRATION PROFILE &nbsp;|&nbsp; वर्णनात्मक पंजीकरण प्रोफ़ाइल</div>

    <div class="card border-0 shadow-sm mb-3 student-hero-card" style="border-radius:10px;background:linear-gradient(90deg,#1a3c6e,#2e6da4);color:#fff;">
        <div class="card-body py-3 px-4 d-flex align-items-center gap-4 flex-wrap">
            <div class="flex-shrink-0">
                @if(!empty($photoUrl))
                    <img src="{{ $photoUrl }}" alt="Photo" class="hero-photo"
                         style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:2px solid rgba(255,255,255,.5);">
                @else
                    <div style="width:72px;height:72px;border-radius:8px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-fill fs-2" style="opacity:.7;"></i>
                    </div>
                @endif

                {{-- Step-1 signature, boxed under the photograph --}}
                <div class="hero-sign-box"
                     style="width:72px;margin-top:6px;border-radius:6px;background:#fff;border:1px solid rgba(255,255,255,.5);padding:2px;text-align:center;">
                    @if(!empty($signatureUrl))
                        <img src="{{ $signatureUrl }}" alt="Signature" style="width:66px;height:auto;max-height:28px;object-fit:contain;">
                    @else
                        <span style="display:block;height:28px;line-height:28px;font-size:10px;color:#999;">—</span>
                    @endif
                    <div class="hero-sign-cap" style="font-size:8px;color:#555;line-height:1.2;">Signature</div>
                </div>
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1 hero-name">{{ $displayName ?: '—' }}</h5>
                {{-- Name, course and email only. The service / state / session chips printed a
                     bare "—" whenever the trainee had none, which was most of the header. --}}
                <div class="hero-meta">
                    @if($reportForm)
                        <span><i class="bi bi-file-earmark-text me-1"></i>{{ $reportForm->form_name }}</span>
                    @endif
                    @if(!empty($headerMeta['email']))
                        <span><i class="bi bi-envelope me-1"></i>{{ $headerMeta['email'] }}</span>
                    @endif
                </div>
                {{-- Course window (Path Page) + coordinator (Front Page). Print only: on screen
                     this is header clutter, but the printed page is a document and needs it. --}}
                @php
                    $ch = $courseHeader ?? [];
                    $heroCoordinator = $ch['coordinator_name'] ?? '';
                    if ($heroCoordinator !== '' && !empty($ch['coordinator_designation']) && $ch['coordinator_designation'] !== $heroCoordinator) {
                        $heroCoordinator .= ' ('.$ch['coordinator_designation'].')';
                    }
                @endphp
                @if(!empty($ch['course_title']) || !empty($ch['course_duration']) || $heroCoordinator !== '')
                    <div class="hero-meta hero-course-line mt-1">
                        @if(!empty($ch['course_title']))
                            <span><i class="bi bi-mortarboard me-1"></i>{{ $ch['course_title'] }}</span>
                        @endif
                        @if(!empty($ch['course_duration']))
                            <span><i class="bi bi-calendar-range me-1"></i>Course Duration: {{ $ch['course_duration'] }}</span>
                        @endif
                        @if($heroCoordinator !== '')
                            <span><i class="bi bi-person-workspace me-1"></i>Coordinator: {{ $heroCoordinator }}</span>
                        @endif
                    </div>
                @endif
            </div>
            <div class="text-end flex-shrink-0 hero-status">
                @if($master?->status === 'SUBMITTED')
                    <span class="badge bg-success fs-6 px-3 py-2">SUBMITTED</span>
                @elseif(!empty($registrationComplete))
                    <span class="badge bg-success fs-6 px-3 py-2">COMPLETE</span>
                @else
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">INCOMPLETE</span>
                @endif
                @if($confirmation?->declaration_accepted)
                    <div class="mt-1" style="font-size:.75rem;opacity:.85;">
                        <i class="bi bi-patch-check-fill text-warning me-1"></i>Declaration accepted
                        {{ $confirmation->confirmed_at?->format('d-m-Y H:i') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($formProgress))
    <div class="card border-0 shadow-sm mb-3 px-4 py-3 print-progress">
        <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
            <span class="fw-semibold">Registration Progress</span>
            <span class="text-muted">{{ $progressDone ?? 0 }}/{{ $progressTotal ?? 0 }} steps</span>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            @foreach($formProgress as $item)
                <div style="flex:1;min-width:72px;text-align:center;">
                    <div style="height:8px;border-radius:4px;background:{{ !empty($item['done']) ? '#16a34a' : '#e5e7eb' }};margin-bottom:4px;"></div>
                    <div style="font-size:10px;color:{{ !empty($item['done']) ? '#16a34a' : '#9ca3af' }};">{{ Str::limit($item['label'], 16) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="row g-3">
        @if(!empty($sections))
            @include('fc.report.partials.dynamic-sections', ['sections' => $sections])
        @else
            <div class="col-12">
                <div class="alert alert-info small mb-0">No form-specific report sections are configured for this trainee.</div>
            </div>
        @endif

        <div class="col-12 no-print">
            <div class="card border-0 shadow-sm" style="border-radius:8px;">
                <div class="card-body py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="fw-semibold small" style="color:#1a3c6e;">
                        <i class="bi bi-file-earmark-check me-1"></i>Document Upload Status &amp; Verification
                        <span class="text-muted fw-normal ms-1">— manage uploaded documents and mark them verified.</span>
                    </div>
                    <a href="{{ route('admin.reports.student.documents', ['username' => $userId] + (request('ref') ? ['ref' => request('ref')] : [])) }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Document Verification
                    </a>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- .student-report-print-area --}}
</div>
@endsection
