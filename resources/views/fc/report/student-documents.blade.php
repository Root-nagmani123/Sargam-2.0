@extends('admin.layouts.master')
@section('title', 'Document Verification – ' . ($displayName ?? $userId))
@section('setup_content')
<div class="container-fluid px-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.reports.student', ['username' => $userId] + (request('ref') ? ['ref' => request('ref')] : [])) }}">
                        {{ $displayName ?: $userId }}
                    </a>
                </li>
                <li class="breadcrumb-item active">Document Verification</li>
            </ol>
        </nav>
        <a href="{{ route('admin.reports.student', ['username' => $userId] + (request('ref') ? ['ref' => request('ref')] : [])) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Profile
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small d-flex justify-content-between align-items-center" style="color:#1a3c6e;">
            <span><i class="bi bi-file-earmark-check me-1"></i>Document Upload Status &amp; Verification</span>
            <span class="text-muted fw-normal">{{ $displayName ?: $userId }} &middot; <code>{{ $userId }}</code></span>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3 mb-0 py-2 small" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3 mb-0 py-2 small" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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
                            : route('admin.reports.student.documents.verify', ['username' => $userId, 'documentMasterId' => $doc->documentMaster?->id]);
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
@endsection
