@extends('admin.layouts.master')
@section('title','Document Checklist Report')
@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-file-earmark-check me-2"></i>Document Checklist Report</h4>
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $s)<option value="{{ $s->id }}" {{ request('session_id')==$s->id?'selected':'' }}>{{ $s->session_name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Doc Status</label>
                <select name="doc_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="complete"   {{ request('doc_status')=='complete'?'selected':'' }}>All Mandatory Done</option>
                    <option value="incomplete" {{ request('doc_status')=='incomplete'?'selected':'' }}>Pending Documents</option>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary px-3">Filter</button></div>
        </div>
    </form>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:11px;">
                <thead class="table-dark">
                    <tr>
                        <th class="px-3" style="white-space:nowrap">#</th>
                        <th style="white-space:nowrap">Username</th>
                        <th style="white-space:nowrap">Full Name</th>
                        <th style="white-space:nowrap">Service</th>
                        @foreach($docMasters as $dm)
                            <th class="text-center" style="max-width:70px;white-space:nowrap;" title="{{ $dm->document_name }}">
                                {{ $dm->document_code ?? 'D'.str_pad($dm->id,2,'0',STR_PAD_LEFT) }}
                                @if($dm->is_mandatory)<span class="text-danger">*</span>@endif
                            </th>
                        @endforeach
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $i => $s)
                    @php
                        $uploaded = $allUploaded[(string) $s->username] ?? $allUploaded[$s->username] ?? collect();
                        $uploadedIds = $uploaded->pluck('document_master_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
                        $totalUploaded = $uploaded->count();
                    @endphp
                    <tr>
                        <td class="px-3">{{ $i+1 }}</td>
                        <td><code style="font-size:10px">{{ $s->username }}</code></td>
                        <td style="white-space:nowrap">{{ $s->full_name }}</td>
                        <td><span class="badge bg-primary-subtle text-primary" style="font-size:9px;">{{ $s->service_code }}</span></td>
                        @foreach($docMasters as $dm)
                            <td class="text-center">
                                @if(in_array((int) $dm->id, $uploadedIds, true))
                                    <i class="bi bi-check-circle-fill text-success" style="font-size:12px;" title="Uploaded"></i>
                                @elseif($dm->is_mandatory)
                                    <i class="bi bi-x-circle-fill text-danger" style="font-size:12px;" title="Missing (Mandatory)"></i>
                                @else
                                    <i class="bi bi-dash-circle text-secondary" style="font-size:12px;opacity:.3;" title="Not uploaded"></i>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center fw-bold">{{ $totalUploaded }}/{{ $docMasters->count() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 5 + $docMasters->count() }}" class="text-center text-muted py-3">No students found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Document Code Legend --}}
    <div class="card border-0 shadow-sm mt-3 px-3 py-2" style="border-radius:8px;">
        <div class="fw-semibold small mb-2">Document Legend</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($docMasters as $dm)
                <span class="badge bg-light text-dark border" style="font-size:10px;">
                    <strong>{{ $dm->document_code ?? 'D'.str_pad($dm->id,2,'0',STR_PAD_LEFT) }}</strong>
                    @if($dm->is_mandatory)<span class="text-danger">*</span>@endif
                    = {{ $dm->document_name }}
                </span>
            @endforeach
        </div>
        <div class="mt-1" style="font-size:10px;color:#666;"><span class="text-danger">*</span> = Mandatory</div>
    </div>
</div>
@endsection
