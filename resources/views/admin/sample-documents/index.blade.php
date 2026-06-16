@extends('admin.layouts.master')
@section('title', 'Sample Document Master')

@section('setup_content')
<div class="container-fluid py-3">

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Sample Document Master</h4>
        <span class="badge bg-info">Joining Documents</span>
        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addSampleModal">
            <i class="bi bi-plus-circle me-1"></i>Add Sample Document
        </button>
    </div>

    <p class="text-muted small">
        Manage the downloadable <strong>sample / blank form</strong> shown against each joining document in the
        registration form. Uploading or replacing a file here updates it for every form that uses that document —
        candidate uploads are not affected.
    </p>

    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">Order</th>
                            <th>Document Title</th>
                            <th>Field Code</th>
                            <th>Section</th>
                            <th style="width:130px;">Sample</th>
                            <th style="width:90px;">Active</th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($samples as $sample)
                            <tr>
                                <td class="text-center">{{ $sample->display_order }}</td>
                                <td class="small fw-semibold">{{ $sample->document_title ?: '—' }}</td>
                                <td><code class="small">{{ $sample->field_name }}</code></td>
                                <td class="small text-muted">{{ $sample->section ?: '—' }}</td>
                                <td class="text-center">
                                    @if($sample->sample_file_path)
                                        <a href="{{ asset(ltrim($sample->sample_file_path, '/')) }}" target="_blank"
                                           rel="noopener" class="btn btn-link btn-sm p-0">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>View
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($sample->is_active)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#editSampleModal{{ $sample->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('fc-reg.admin.sample-documents.destroy', $sample) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Remove this sample document?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No sample documents yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Edit modals (kept OUTSIDE the table — modals inside <tbody> break rendering) --}}
@foreach($samples as $sample)
    <div class="modal fade" id="editSampleModal{{ $sample->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content"
                  action="{{ route('fc-reg.admin.sample-documents.update', $sample) }}"
                  method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h6 class="modal-title">Edit — {{ $sample->field_name }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Document Field Code <span class="text-danger">*</span></label>
                        <input type="text" name="field_name" class="form-control form-control-sm"
                               value="{{ $sample->field_name }}" required placeholder="e.g. doc_police_verification">
                        <small class="text-muted">Must match a <code>doc_*</code> field on the form for the sample to show to candidates.</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Document Title</label>
                        <input type="text" name="document_title" class="form-control form-control-sm"
                               value="{{ $sample->document_title }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Section</label>
                        <input type="text" name="section" class="form-control form-control-sm"
                               value="{{ $sample->section }}"
                               placeholder="e.g. Administration Section Related Documents">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Display Order</label>
                            <input type="number" name="display_order" class="form-control form-control-sm"
                                   value="{{ $sample->display_order }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Active</label>
                            <select name="is_active" class="form-select form-select-sm">
                                <option value="1" {{ $sample->is_active ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ ! $sample->is_active ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small">Replace Sample File (optional)</label>
                        <input type="file" name="sample_file" class="form-control form-control-sm"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        @if($sample->sample_file_path)
                            <small class="text-muted">Current: {{ $sample->sample_original_name ?: basename($sample->sample_file_path) }}</small>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

{{-- Add modal --}}
<div class="modal fade" id="addSampleModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="{{ route('fc-reg.admin.sample-documents.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title">Add Sample Document</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Document Field Code <span class="text-danger">*</span></label>
                    <input type="text" name="field_name" id="addFieldName" class="form-control form-control-sm"
                           autocomplete="off" required
                           placeholder="e.g. doc_family_details">
                    <small class="text-muted">Type a document code (matches a <code>doc_*</code> field on the form). Each code can have one sample.</small>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Document Title</label>
                    <input type="text" name="document_title" id="addTitle" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Section</label>
                    <input type="text" name="section" id="addSection" class="form-control form-control-sm"
                           placeholder="e.g. Administration Section Related Documents">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Sample File <span class="text-danger">*</span></label>
                    <input type="file" name="sample_file" class="form-control form-control-sm"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    <small class="text-muted">PDF / JPG / PNG / DOC, max 10MB.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>

@endsection
