@extends('admin.layouts.master')
@section('title', 'Manage Forms')

@section('setup_content')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-collection me-2"></i>Dynamic Forms</h4>
        <a href="{{ route('fc-reg.admin.forms.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Create New Form
        </a>
    </div>

    <div class="alert alert-info border-0 shadow-sm">
        <i class="bi bi-info-circle me-1"></i>
        Create and manage multiple forms. Each form can have its own steps, fields, and groups — all stored independently.
    </div>

    <div class="row g-4">
        @forelse($forms as $form)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100" style="border-radius:10px;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                 style="width:45px;height:45px;background:{{ $form->is_active ? '#1a3c6e' : '#6c757d' }};color:#fff;font-size:1.2rem;">
                                <i class="bi {{ $form->icon ?? 'bi-file-text' }}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $form->form_name }}</h6>
                                <small class="text-muted">{{ $form->form_slug }}</small>
                            </div>
                            @if(! $form->is_active)
                                <span class="badge bg-secondary ms-auto">Inactive</span>
                            @endif
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <div class="text-center">
                                <div class="fw-bold text-primary" style="font-size:1.3rem;">{{ $form->steps_count }}</div>
                                <small class="text-muted">Steps</small>
                            </div>
                        </div>

                        <p class="text-muted small mb-3">{{ Str::limit($form->description, 100) }}</p>

                        @if($form->consolidation_table)
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-database me-1"></i>Tracking: <code>{{ $form->consolidation_table }}</code>
                                </small>
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <a href="{{ route('fc-reg.admin.forms.edit', $form) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square me-1"></i>Edit / Steps
                            </a>
                            <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="bi bi-eye me-1"></i>User View
                            </a>
                            <form method="POST" action="{{ route('fc-reg.admin.forms.destroy', $form) }}" class="d-inline" onsubmit="return confirm('Delete this form and ALL its steps/fields? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4"></i>
                    <p class="mt-3">No forms created yet. Click "Create New Form" to get started.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
