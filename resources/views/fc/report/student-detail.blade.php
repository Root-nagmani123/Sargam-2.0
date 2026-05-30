@extends('admin.layouts.master')
@section('title', 'Student Profile – ' . ($displayName ?? $userId))

@push('styles')
<style>
    @page {
        size: A4;
        margin: 10mm;
    }
    @media print {
        html, body {
            background: #fff !important;
            height: auto !important;
        }
        body {
            margin: 0 !important;
            padding: 0 !important;
        }

        .sargam-loader,
        #sargamLoader,
        .topbar,
        header.topbar,
        .header-top-bar,
        .left-sidebar,
        .side-mini-panel,
        aside.side-mini-panel,
        #sidebarTabContent,
        .mobile-tabbar,
        #mainNavbar,
        .navbar,
        footer,
        .no-print {
            display: none !important;
        }

        #main-wrapper,
        .page-wrapper,
        .body-wrapper,
        #main-content,
        #mainNavbarContent,
        #tab-setup {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            border: none !important;
            box-shadow: none !important;
        }

        body * {
            visibility: hidden;
        }
        .student-report-print-area,
        .student-report-print-area * {
            visibility: visible !important;
        }
        .student-report-print-area {
            position: absolute;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 8px !important;
            box-sizing: border-box;
        }

        .student-report-print-area .card {
            box-shadow: none !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .student-report-print-area .card-header {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .student-report-print-area table {
            font-size: 11px !important;
        }
        .student-report-print-area tr {
            page-break-inside: avoid;
        }
        .student-report-print-area a[href]:after {
            content: none !important;
        }
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
            <a href="{{ route('admin.reports.student.pdf', ['userId' => $userId]) }}" class="btn btn-sm btn-primary" target="_blank" rel="noopener">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
            </a>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>

    <div class="student-report-print-area">

    <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;background:linear-gradient(90deg,#1a3c6e,#2e6da4);color:#fff;">
        <div class="card-body py-3 px-4 d-flex align-items-center gap-4 flex-wrap">
            <div class="flex-shrink-0">
                @if(!empty($photoUrl))
                    <img src="{{ $photoUrl }}" alt="Photo"
                         style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:2px solid rgba(255,255,255,.5);">
                @else
                    <div style="width:72px;height:72px;border-radius:8px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-fill fs-2" style="opacity:.7;"></i>
                    </div>
                @endif
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1">{{ $displayName ?: '—' }}</h5>
                <div style="font-size:.82rem;opacity:.85;">
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
            <div class="text-end flex-shrink-0">
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
                        {{ $confirmation->confirmed_at?->format('d M Y, H:i') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($formProgress))
    <div class="card border-0 shadow-sm mb-3 px-4 py-3">
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
                    <table class="table table-sm mb-0" style="font-size:12px;">
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
