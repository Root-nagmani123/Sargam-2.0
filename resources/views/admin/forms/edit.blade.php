@extends('admin.layouts.master')
@section('title', 'Edit: ' . $form->form_name)

@section('setup_content')
<div class="container py-4">
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Validation errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('fc-reg.admin.forms.index') }}" class="btn btn-sm btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0"><i class="bi {{ $form->icon ?? 'bi-file-text' }} me-2"></i>{{ $form->form_name }}</h4>
        <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-sm btn-outline-primary ms-auto" target="_blank">
            <i class="bi bi-eye me-1"></i>User View
        </a>
    </div>

    {{-- Form Settings Card --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted">Form Settings</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('fc-reg.admin.forms.update', $form) }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Form Name</label>
                        <input type="text" name="form_name" class="form-control" value="{{ $form->form_name }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Icon Class</label>
                        <input type="text" name="icon" class="form-control" value="{{ $form->icon }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Consolidation Table</label>
                        <select name="consolidation_table" class="form-select form-select-sm">
                            <option value="">-- None --</option>
                            @foreach($tables as $table)
                                <option value="{{ $table }}" {{ $form->consolidation_table === $table ? 'selected' : '' }}>{{ $table }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="formActive" {{ $form->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small" for="formActive">Active</label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <small class="text-muted">Slug: <code>{{ $form->form_slug }}</code></small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">User ID Column: <code>{{ $form->user_identifier }}</code></small>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2">{{ $form->description }}</textarea>
                </div>
            </form>
        </div>
    </div>

    {{-- Steps Section --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted">Steps ({{ $form->steps->count() }})</h6>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStepModal">
                <i class="bi bi-plus-circle me-1"></i>Add Step
            </button>
        </div>
        <div class="card-body p-0">
            @if($form->steps->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-layers display-4"></i>
                    <p class="mt-3 mb-0">No steps yet. Click "Add Step" to add the first step.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="small text-muted">
                                <th style="width:50px;">#</th>
                                <th>Step Name</th>
                                <th>Slug</th>
                                <th>Target Table</th>
                                <th>Tracker</th>
                                <th>Active</th>
                                <th style="width:200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="stepsList">
                            @foreach($form->steps as $step)
                                <tr data-id="{{ $step->id }}">
                                    <td class="text-muted">{{ $step->step_number }}</td>
                                    <td>
                                        <i class="bi {{ $step->icon ?? 'bi-file-text' }} me-1 text-primary"></i>
                                        <span class="fw-semibold">{{ $step->step_name }}</span>
                                    </td>
                                    <td><code class="small">{{ $step->step_slug }}</code></td>
                                    <td><code class="small">{{ $step->target_table }}</code></td>
                                    <td><code class="small">{{ $step->tracker_column ?? '—' }}</code></td>
                                    <td>
                                        @if($step->is_active)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('fc-reg.admin.form-builder.step', $step) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit Fields">
                                            <i class="bi bi-pencil me-1"></i>Fields
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveStep({{ $step->id }}, 'up')" title="Move Up">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveStep({{ $step->id }}, 'down')" title="Move Down">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                        <form method="POST" action="{{ route('fc-reg.admin.forms.step.delete', $step) }}" class="d-inline" onsubmit="return confirm('Delete this step and all its fields?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Access: Form URL --}}
    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-body">
            <h6 class="text-uppercase small fw-bold text-muted mb-2">Form URL for Users</h6>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                <input type="text" class="form-control" readonly value="{{ route('fc-reg.forms.dashboard', $form) }}" id="formUrl">
                <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('formUrl').value); this.innerHTML='<i class=\'bi bi-check\'></i> Copied'; setTimeout(()=>this.innerHTML='<i class=\'bi bi-clipboard\'></i> Copy', 2000);">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ADD STEP MODAL --}}
<div class="modal fade" id="addStepModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('fc-reg.admin.forms.step.store', $form) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Step to {{ $form->form_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Step Name <span class="text-danger">*</span></label>
                        <input type="text" name="step_name" class="form-control" required placeholder="e.g. Personal Details">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Step Slug <span class="text-danger">*</span></label>
                        <input type="text" name="step_slug" class="form-control" required placeholder="e.g. personal-details">
                        <small class="text-muted">Unique slug (lowercase, hyphens, underscores only)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Table <span class="text-danger">*</span></label>
                        <select name="target_table" class="form-select" required id="addStepTargetTable">
                            <option value="">-- Select existing table --</option>
                            @foreach($tables as $table)
                                <option value="{{ $table }}" {{ (isset($referenceSteps[$nextStepNumber]) && $referenceSteps[$nextStepNumber] === $table) ? 'selected' : '' }}>{{ $table }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Auto-selected based on step {{ $nextStepNumber }} reference table. You can change it.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Icon Class</label>
                        <input type="text" name="icon" class="form-control" value="bi-file-text" placeholder="bi-person-fill">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Completion Column</label>
                        <input type="text" name="completion_column" class="form-control" placeholder="e.g. step1_completed">
                        <small class="text-muted">Existing column in target table marked as 1 when step is done</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tracker Column</label>
                        <input type="text" name="tracker_column" class="form-control" placeholder="e.g. step1_done">
                        <small class="text-muted">Existing column in consolidation table to track completion</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of what this step collects"></textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="has_groups" value="1" id="hasGroups">
                            <label class="form-check-label small" for="hasGroups">
                                This step uses field groups (tabs with repeatable rows, like Step 3)
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Step</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-generate slug from step name
document.querySelector('#addStepModal [name="step_name"]').addEventListener('input', function() {
    const slugInput = this.closest('.modal-body').querySelector('[name="step_slug"]');
    if (!slugInput.dataset.manual) {
        slugInput.value = this.value.toLowerCase()
            .replace(/[^a-z0-9\s\-_]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }
});

function moveStep(stepId, direction) {
    const rows = [...document.querySelectorAll('#stepsList tr')];
    const ids = rows.map(r => parseInt(r.dataset.id));
    const idx = ids.indexOf(stepId);
    if (direction === 'up' && idx > 0) { [ids[idx-1], ids[idx]] = [ids[idx], ids[idx-1]]; }
    else if (direction === 'down' && idx < ids.length - 1) { [ids[idx], ids[idx+1]] = [ids[idx+1], ids[idx]]; }
    else return;

    fetch('{{ route("fc-reg.admin.forms.step.reorder") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ order: ids })
    }).then(() => location.reload());
}
</script>
@endpush
