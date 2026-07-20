@extends('admin.layouts.master')
@section('title','Document Checklist Report')
@section('setup_content')
<style>
.doc-num-th {
    width: 32px;
    min-width: 32px;
    max-width: 32px;
    text-align: center;
    padding: 4px 2px !important;
    font-size: 10px;
    font-weight: 700;
    vertical-align: middle;
}
.doc-num-td {
    width: 32px;
    min-width: 32px;
    max-width: 32px;
    text-align: center;
    padding: 5px 2px !important;
    vertical-align: middle;
}
.progress-bar-mini {
    height: 4px;
    border-radius: 2px;
    margin-top: 2px;
}
</style>

<div class="container-fluid px-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
            <i class="bi bi-file-earmark-check me-2"></i>Document Checklist Report
        </h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(request('form_id'))
                <a href="{{ route('admin.reports.documents.export') }}?form_id={{ request('form_id') }}"
                   class="btn btn-sm btn-success"
                   title="Download all uploaded documents as a ZIP, organised by student">
                    <i class="bi bi-file-zip me-1"></i>Export Docs
                </a>
            @endif
            @include('fc.report.partials.scoped-form-back', ['scopedForm' => $scopedForm ?? null])
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            @include('fc.report.partials.form-filter-select', ['forms' => $forms])
            <div class="col-md-3">
                <label class="form-label small mb-1">Doc Status</label>
                <select name="doc_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="complete"   {{ request('doc_status')=='complete'   ? 'selected' : '' }}>All Mandatory Done</option>
                    <option value="incomplete" {{ request('doc_status')=='incomplete' ? 'selected' : '' }}>Pending Documents</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
            </div>
        </div>
    </form>

    {{-- Summary strip --}}
    @php
        $totalStudents  = $students->count();
        $mandatoryCount = $docMasters->filter(fn($dm) => $dm->is_mandatory)->count();
        $allUploadedCnt = $students->filter(fn($s) =>
            ($allUploaded[(string)$s->user_id] ?? collect())->count() >= $docMasters->count()
        )->count();
        $noneUploadedCnt = $students->filter(fn($s) =>
            ($allUploaded[(string)$s->user_id] ?? collect())->count() === 0
        )->count();
        $partialCnt = $totalStudents - $allUploadedCnt - $noneUploadedCnt;
    @endphp
    @if($totalStudents > 0)
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-2 px-1" style="border-radius:8px; border-left:3px solid #1a3c6e !important;">
                <div class="fw-bold" style="font-size:1.4rem; color:#1a3c6e;">{{ $totalStudents }}</div>
                <div class="text-muted" style="font-size:11px;">Total Students</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-2 px-1" style="border-radius:8px; border-left:3px solid #16a34a !important;">
                <div class="fw-bold" style="font-size:1.4rem; color:#16a34a;">{{ $allUploadedCnt }}</div>
                <div class="text-muted" style="font-size:11px;">All Docs Uploaded</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-2 px-1" style="border-radius:8px; border-left:3px solid #d97706 !important;">
                <div class="fw-bold" style="font-size:1.4rem; color:#d97706;">{{ $partialCnt }}</div>
                <div class="text-muted" style="font-size:11px;">Partial Upload</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-2 px-1" style="border-radius:8px; border-left:3px solid #dc2626 !important;">
                <div class="fw-bold" style="font-size:1.4rem; color:#dc2626;">{{ $noneUploadedCnt }}</div>
                <div class="text-muted" style="font-size:11px;">Not Started</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Legend (above table so numbers are clear before reading the table) --}}
    <div class="card border-0 shadow-sm mb-3 px-3 py-3" style="border-radius:8px;">
        <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
            <span class="fw-semibold" style="font-size:13px;">Document Key</span>
            <span style="font-size:11px;color:#666;">
                <span class="badge text-bg-danger me-1" style="font-size:9px;">M</span>= Mandatory &nbsp;
                <span class="badge text-bg-secondary me-1" style="font-size:9px;">O</span>= Optional
            </span>
        </div>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap:5px 16px;">
            @foreach($docMasters as $i => $dm)
            <div style="display:flex; align-items:flex-start; gap:6px; font-size:11px; line-height:1.4;">
                <span style="
                    flex-shrink:0; min-width:22px; height:18px;
                    background:{{ $dm->is_mandatory ? '#dc2626' : '#6b7280' }};
                    color:#fff; border-radius:3px; font-size:10px; font-weight:700;
                    display:inline-flex; align-items:center; justify-content:center;
                ">{{ $i+1 }}</span>
                <span style="color:#374151;">{{ $dm->document_name }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Main table --}}
    <div class="card border-0 shadow-sm" style="border-radius:8px; overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0" style="font-size:11px; border-color:#e5e7eb;">
                <thead>
                    <tr style="background:#1e3a5f; color:#fff;">
                        <th style="padding:8px 10px; white-space:nowrap; vertical-align:middle; width:32px;">#</th>
                        <th style="padding:8px 10px; white-space:nowrap; vertical-align:middle; min-width:80px;">ID</th>
                        <th style="padding:8px 10px; white-space:nowrap; vertical-align:middle; min-width:150px;">Full Name</th>
                        <th style="padding:8px 10px; white-space:nowrap; vertical-align:middle; min-width:70px;">Service</th>
                        {{-- Document number headers --}}
                        @foreach($docMasters as $i => $dm)
                        <th class="doc-num-th"
                            style="background:{{ $dm->is_mandatory ? '#991b1b' : '#1e3a5f' }}; color:#fff; border-color:#2d4a7a;"
                            title="{{ ($i+1) }}. {{ $dm->document_name }}{{ $dm->is_mandatory ? ' — MANDATORY' : '' }}">
                            {{ $i+1 }}
                        </th>
                        @endforeach
                        <th style="padding:8px 6px; white-space:nowrap; vertical-align:middle; min-width:80px; text-align:center;">
                            Done
                        </th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $i => $s)
                    @php
                        $uploaded      = $allUploaded[(string) $s->user_id] ?? collect();
                        $uploadedIds   = $uploaded->pluck('document_master_id')
                                            ->filter()->map(fn ($id) => (int) $id)
                                            ->unique()->values()->all();
                        $totalUploaded = count($uploadedIds);
                        $totalDocs     = $docMasters->count();
                        $pct           = $totalDocs > 0 ? round($totalUploaded / $totalDocs * 100) : 0;
                        $barColor      = $pct >= 100 ? '#16a34a' : ($pct > 0 ? '#f59e0b' : '#e5e7eb');
                        $textColor     = $pct >= 100 ? '#16a34a' : ($pct > 0 ? '#b45309' : '#9ca3af');
                    @endphp
                    <tr style="{{ $i % 2 === 0 ? 'background:#f9fafb;' : 'background:#fff;' }}">
                        <td style="padding:6px 10px; color:#9ca3af; vertical-align:middle;">{{ $i+1 }}</td>
                        <td style="padding:6px 10px; vertical-align:middle;">
                            <a href="{{ route('admin.reports.student', $s->route_user_id ?? $s->user_id) }}"
                               style="color:#1a3c6e; font-family:monospace; font-size:11px; font-weight:600; text-decoration:none;"
                               title="User ID: {{ $s->user_id }}">
                                {{ $s->login_username ?? $s->user_id }}
                            </a>
                        </td>
                        <td style="padding:6px 10px; vertical-align:middle; white-space:nowrap; font-weight:500; color:#111827;">
                            {{ $s->full_name ?: '—' }}
                        </td>
                        <td style="padding:6px 10px; vertical-align:middle;">
                            @if($s->service_code)
                                <span class="badge" style="background:#eff6ff; color:#1d4ed8; font-size:9px; font-weight:600;">
                                    {{ $s->service_code }}
                                </span>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        @foreach($docMasters as $dm)
                        <td class="doc-num-td" style="border-color:#e5e7eb;">
                            @if(in_array((int) $dm->id, $uploadedIds, true))
                                <i class="bi bi-check-circle-fill" style="font-size:13px; color:#16a34a;"
                                   title="{{ $dm->document_name }}: Uploaded"></i>
                            @elseif($dm->is_mandatory)
                                <i class="bi bi-x-circle-fill" style="font-size:13px; color:#dc2626;"
                                   title="{{ $dm->document_name }}: Missing (Mandatory)"></i>
                            @else
                                <i class="bi bi-circle" style="font-size:12px; color:#d1d5db;"
                                   title="{{ $dm->document_name }}: Not uploaded"></i>
                            @endif
                        </td>
                        @endforeach
                        <td style="padding:6px 10px; vertical-align:middle; text-align:center; white-space:nowrap; min-width:80px;">
                            <div style="font-size:12px; font-weight:700; color:{{ $textColor }};">
                                {{ $totalUploaded }}/{{ $totalDocs }}
                            </div>
                            <div class="progress-bar-mini" style="background:#e5e7eb; width:52px; margin:2px auto 0;">
                                <div style="width:{{ $pct }}%; height:4px; background:{{ $barColor }}; border-radius:2px;"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + $docMasters->count() }}" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:6px; opacity:.4;"></i>
                            No students found for the selected filters.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table footer icon key --}}
        <div class="px-3 py-2" style="background:#f9fafb; border-top:1px solid #e5e7eb; font-size:11px; color:#6b7280;">
            <i class="bi bi-check-circle-fill text-success me-1"></i>Uploaded &nbsp;
            <i class="bi bi-x-circle-fill text-danger me-1 ms-2"></i>Missing (Mandatory) &nbsp;
            <i class="bi bi-circle me-1 ms-2" style="color:#d1d5db;"></i>Not uploaded (Optional)
        </div>
    </div>

</div>
@endsection
