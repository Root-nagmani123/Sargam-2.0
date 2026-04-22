@extends('admin.layouts.master')
@section('title', 'Form Builder')

@section('setup_content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-gear-fill me-2"></i>FC Registration Form Builder</h4>
        <a href="{{ route('fc-reg.admin.forms.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-collection me-1"></i>Manage All Forms
        </a>
    </div>

    <div class="alert alert-info border-0 shadow-sm">
        <i class="bi bi-info-circle me-1"></i>
        Configure which fields appear in each registration step. Changes take effect immediately for FC users.
    </div>

    <div class="row g-4">
        @foreach($steps as $step)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100" style="border-radius:10px;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                 style="width:45px;height:45px;background:{{ $step->is_active ? '#1a3c6e' : '#6c757d' }};color:#fff;font-size:1.2rem;">
                                <i class="bi {{ $step->icon ?? 'bi-file-text' }}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $step->step_name }}</h6>
                                <small class="text-muted">Step {{ $step->step_number }}</small>
                            </div>
                            @if(! $step->is_active)
                                <span class="badge bg-secondary ms-auto">Inactive</span>
                            @endif
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <div class="text-center">
                                <div class="fw-bold text-primary" style="font-size:1.3rem;">
                                    @if($step->step_slug === 'documents')
                                        {{ $docMasterCount }}
                                    @else
                                        {{ $step->fields_count }}
                                    @endif
                                </div>
                                <small class="text-muted">{{ $step->step_slug === 'documents' ? 'Documents' : 'Fields' }}</small>
                            </div>
                            @if($step->field_groups_count > 0)
                                <div class="text-center">
                                    <div class="fw-bold text-primary" style="font-size:1.3rem;">{{ $step->field_groups_count }}</div>
                                    <small class="text-muted">Groups</small>
                                </div>
                            @endif
                        </div>

                        <p class="text-muted small mb-3">{{ Str::limit($step->description, 80) }}</p>

                        <div class="d-flex gap-2">
                            <a href="{{ route('fc-reg.admin.form-builder.step', $step) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square me-1"></i>Edit Fields
                            </a>
                            <a href="{{ route('fc-reg.admin.form-builder.preview', $step) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i>Preview
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
