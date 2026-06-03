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
        .student-hero-card .hero-meta span { color: #222 !important; font-size: 8.5pt !important; }

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
    .print-masthead, .print-doc-title { display: none; }
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
            <a href="{{ route('admin.reports.student.pdf', ['username' => $userId]) }}" class="btn btn-sm btn-primary" target="_blank" rel="noopener">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
            </a>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>

    <div class="student-report-print-area">

    {{-- Print-only masthead --}}
    <div class="print-masthead">
        <p class="print-masthead-hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी</p>
        <p class="print-masthead-en">Lal Bahadur Shastri National Academy of Administration</p>
        <p class="print-masthead-name">Government of India &nbsp;|&nbsp; भारत सरकार</p>
        <p class="print-masthead-sub">Mussoorie – Uttarakhand &nbsp;|&nbsp; मसूरी – उत्तराखंड</p>
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
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1 hero-name">{{ $displayName ?: '—' }}</h5>
                <div class="hero-meta" style="font-size:.82rem;opacity:.85;">
                    <span class="me-3"><i class="bi bi-person-badge me-1"></i>{{ $displayName ?: $userId }}</span>
                    @if($reportForm)
                        <span class="me-3"><i class="bi bi-file-earmark-text me-1"></i>{{ $reportForm->form_name }}</span>
                    @endif
                    <span class="me-3"><i class="bi bi-briefcase me-1"></i>{{ $headerMeta['service_label'] ?? '—' }}</span>
                    <span class="me-3"><i class="bi bi-geo-alt me-1"></i>{{ $headerMeta['state_label'] ?? '—' }}</span>
                    <span class="me-3"><i class="bi bi-calendar3 me-1"></i>{{ $headerMeta['session_label'] ?? '—' }}</span>
                    @if(!empty($headerMeta['email']))
                        <span><i class="bi bi-envelope me-1"></i>{{ $headerMeta['email'] }}</span>
                    @endif
                </div>
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

        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-file-earmark-check me-1"></i>Document Upload Status
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 doc-table" style="font-size:12px;">
                        <thead class="table-light">
                            <tr><th>#</th><th>Document</th><th class="text-center">Mandatory</th><th class="text-center">Uploaded</th><th class="text-center">Verified</th><th>Remarks / Admin Action</th></tr>
                        </thead>
                        <tbody>
                        @forelse($documents as $i => $doc)
                            @php
                                $isMandatory = ($documentSource ?? 'legacy') === 'dynamic'
                                    ? !empty($doc->is_mandatory)
                                    : (bool) ($doc->documentMaster?->is_mandatory ?? false);
                                $canVerify = $doc->is_uploaded && (
                                    (($documentSource ?? 'legacy') === 'legacy' && $doc->documentMaster?->id)
                                    || (($documentSource ?? 'legacy') === 'dynamic' && !empty($doc->form_field_id))
                                );
                                $verifyAction = ($documentSource ?? 'legacy') === 'dynamic' && !empty($doc->form_field_id)
                                    ? route('admin.reports.student.form-documents.verify', ['userId' => $userId, 'formFieldId' => $doc->form_field_id])
                                    : route('admin.reports.student.documents.verify', ['userId' => $userId, 'documentMasterId' => $doc->documentMaster?->id]);
                            @endphp
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $doc->documentMaster?->document_name ?? $doc->document_name }}</td>
                                <td class="text-center">
                                    @if($isMandatory)
                                        <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">Yes</span>
                                    @else — @endif
                                </td>
                                <td class="text-center">
                                    @if($doc->is_uploaded)
                                        @php $docFileUrl = view_file_link($doc->file_path); @endphp
                                        @if($docFileUrl)
                                            <a href="{{ $docFileUrl }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:10px;"><i class="bi bi-eye me-1"></i>View</a>
                                        @else
                                            <span class="text-warning" style="font-size:11px;">Path missing</span>
                                        @endif
                                    @else
                                        <span class="text-muted" style="font-size:11px;">Not uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($doc->is_uploaded)
                                        @if($doc->is_verified)<i class="bi bi-patch-check-fill text-success"></i>@else<i class="bi bi-clock text-warning"></i>@endif
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                <td style="font-size:11px;min-width:260px;">
                                    @if($canVerify)
                                        <form method="POST" action="{{ $verifyAction }}">
                                            @csrf
                                            <input type="hidden" name="is_verified" value="0">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <input class="form-check-input mt-0" type="checkbox" name="is_verified" value="1" {{ $doc->is_verified ? 'checked' : '' }}>
                                                <span class="small text-muted">Mark verified</span>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <input type="text" name="remarks" class="form-control form-control-sm" maxlength="500" placeholder="Optional admin remark" value="{{ old('remarks', $doc->remarks) }}">
                                                <button type="submit" class="btn btn-sm btn-outline-primary px-2">Save</button>
                                            </div>
                                        </form>
                                        @if($doc->remarks)
                                            <div class="d-none d-print-block small text-muted">{{ $doc->remarks }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">Not uploaded</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-2">
                                @if(($documentSource ?? 'legacy') === 'dynamic')
                                    No joining document fields are configured for this form.
                                @else
                                    No joining document types are configured in the master checklist, or none are active.
                                @endif
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- .student-report-print-area --}}
</div>
@endsection
