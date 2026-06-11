@extends('admin.layouts.master')
@section('title', 'Edit Step: ' . $step->step_name)

@push('styles')
<style>
    .fc-fb-actions-col { width: 1%; white-space: nowrap; }
    .fc-fb-actions {
        display: inline-flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 0.25rem;
    }
    .fc-fb-actions .btn { flex-shrink: 0; }
    .fc-fb-actions__form { display: inline-flex; margin: 0; padding: 0; }
    .fc-field-form-section .border { border-color: var(--bs-border-color) !important; }
</style>
@endpush

@section('setup_content')
<div class="container py-4">
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
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
        <a href="{{ $step->form ? route('fc-reg.admin.forms.edit', $step->form) : route('fc-reg.admin.forms.index') }}" class="btn btn-sm btn-outline-secondary me-3" title="Back to form">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0"><i class="bi {{ $step->icon ?? 'bi-file-text' }} me-2"></i>{{ $step->step_name }}</h4>
        @if($step->form)
            <span class="badge bg-info ms-2">{{ $step->form->form_name }}</span>
        @endif
        <a href="{{ route('fc-reg.admin.form-builder.preview', $step) }}" class="btn btn-sm btn-outline-primary ms-auto">
            <i class="bi bi-eye me-1"></i>Preview
        </a>
    </div>

    {{-- Step Settings Card --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted ls-1">Step Settings</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('fc-reg.admin.form-builder.step.update', $step) }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Step Name</label>
                        <input type="text" name="step_name" class="form-control" value="{{ $step->step_name }}">
                    </div>
                    <div class="col-md-4">
                        @include('admin.forms.partials.fc-form-icon-picker', [
                            'selectedIcon' => old('icon', $step->icon ?: 'bi-file-text'),
                            'selectId' => 'fcFormBuilderStepIcon',
                            'formSelect' => 'form-select-sm',
                            'label' => 'Step icon',
                            'labelClass' => 'form-label small fw-semibold',
                        ])
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" value="{{ $step->description }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="stepActive" {{ $step->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small" for="stepActive">Active</label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <small class="text-muted">Target: <code>{{ $step->target_table }}</code></small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Completion: <code>{{ $step->completion_column ?? '—' }}</code></small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Tracker: <code>{{ $step->tracker_column ?? '—' }}</code></small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- FIELDS SECTION (flat fields — not step 3 groups or documents checklist) --}}
    @if(! $step->usesFieldGroups() && ! $step->isDocumentsStep())
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted" id="fcFieldsCountLabel">Fields ({{ $step->fields->count() }})</h6>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addFieldModal">
                <i class="bi bi-plus-circle me-1"></i>Add Field
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th style="width:40px;">#</th>
                            <th>Label</th>
                            <th>Field Name</th>
                            <th>Type</th>
                            <th>Target Column</th>
                            <th>Section</th>
                            <th>Required</th>
                            <th>Active</th>
                            <th class="fc-fb-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fieldsList">
                        @foreach($step->fields as $field)
                            @include('admin.form-builder.partials.field-row', ['field' => $field])
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- DOCUMENT CHECKLIST SECTION (Documents step) --}}
    @if($step->isDocumentsStep())
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted">Document Checklist ({{ $docMasters->count() }})</h6>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDocMasterModal">
                <i class="bi bi-plus-circle me-1"></i>Add Document
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th style="width:40px;">#</th>
                            <th>Document Name</th>
                            <th>Code</th>
                            <th>Mandatory</th>
                            <th>Active</th>
                            <th class="fc-fb-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="docMastersList">
                        @foreach($docMasters as $doc)
                            <tr data-id="{{ $doc->id }}">
                                <td class="text-muted small">{{ $doc->display_order }}</td>
                                <td class="fw-semibold small">{{ $doc->document_name }}</td>
                                <td><code class="small">{{ $doc->document_code }}</code></td>
                                <td>
                                    @if($doc->is_mandatory)
                                        <span class="badge bg-danger">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($doc->is_active)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="fc-fb-actions-col">
                                    <div class="fc-fb-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editDocMaster({{ json_encode($doc) }})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveDocMaster({{ $doc->id }}, 'up')" title="Move Up">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveDocMaster({{ $doc->id }}, 'down')" title="Move Down">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                        <form method="POST" action="{{ route('fc-reg.admin.form-builder.doc-master.delete', $doc) }}" class="fc-fb-actions__form" onsubmit="return confirm('Delete this document?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- GROUPS SECTION (Step 3 / Other Details) --}}
    @if($step->usesFieldGroups())
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted">Field Groups / Tabs ({{ $step->fieldGroups->count() }})</h6>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                <i class="bi bi-plus-circle me-1"></i>Add Group
            </button>
        </div>
        <div class="card-body">
            {{-- Group tabs --}}
            <ul class="nav nav-tabs mb-3 flex-wrap" role="tablist">
                @foreach($step->fieldGroups as $gi => $group)
                    <li class="nav-item">
                        <a class="nav-link text-nowrap {{ $gi === 0 ? 'active' : '' }}" data-bs-toggle="tab" href="#grp-{{ $group->id }}">
                            {{ $group->group_label }}
                            <span class="badge bg-secondary ms-1">{{ $group->groupFields->count() }}</span>
                            @if(! $group->is_active)
                                <i class="bi bi-eye-slash text-muted ms-1"></i>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach($step->fieldGroups as $gi => $group)
                    <div class="tab-pane {{ $gi === 0 ? 'show active' : '' }}" id="grp-{{ $group->id }}">
                        {{-- Group settings --}}
                        <div class="border rounded p-3 mb-3 bg-light">
                            <form method="POST" action="{{ route('fc-reg.admin.form-builder.group.update', $group) }}" class="row g-2 align-items-end">
                                @csrf @method('PUT')
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Label</label>
                                    <input type="text" name="group_label" class="form-control form-control-sm" value="{{ $group->group_label }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Target Table</label>
                                    <input type="text" name="target_table" class="form-control form-control-sm" value="{{ $group->target_table }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Save Mode</label>
                                    <select name="save_mode" class="form-select form-select-sm">
                                        <option value="replace_all" {{ $group->save_mode === 'replace_all' ? 'selected' : '' }}>Replace All</option>
                                        <option value="upsert" {{ $group->save_mode === 'upsert' ? 'selected' : '' }}>Upsert</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small fw-semibold">Min</label>
                                    <input type="number" name="min_rows" class="form-control form-control-sm" value="{{ $group->min_rows }}" min="0">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small fw-semibold">Max</label>
                                    <input type="number" name="max_rows" class="form-control form-control-sm" value="{{ $group->max_rows }}" min="1">
                                </div>
                                <div class="col-md-1">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $group->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label small">Active</label>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    <form method="POST" action="{{ route('fc-reg.admin.form-builder.group.delete', $group) }}" onsubmit="return confirm('Delete this group and all its fields?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </form>
                        </div>

                        {{-- Group fields table --}}
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="openAddGroupFieldModal({{ $group->id }})">
                                <i class="bi bi-plus-circle me-1"></i>Add Field
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr class="small text-muted">
                                        <th>#</th><th>Label</th><th>Field Name</th><th>Type</th><th>Target Column</th><th>Required</th><th>Active</th><th class="fc-fb-actions-col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group->groupFields as $gf)
                                        <tr>
                                            <td class="text-muted small">{{ $gf->display_order }}</td>
                                            <td class="fw-semibold small">{{ $gf->label }}</td>
                                            <td><code class="small">{{ $gf->field_name }}</code></td>
                                            <td><span class="badge bg-light text-dark">{{ $gf->field_type }}</span></td>
                                            <td><code class="small">{{ $gf->target_column }}</code></td>
                                            <td>{!! $gf->is_required ? '<span class="badge bg-danger">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                                            <td>{!! $gf->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                                            <td class="fc-fb-actions-col">
                                                <div class="fc-fb-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editGroupField({{ json_encode($gf) }})" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    @if($gf->is_active)
                                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Cannot delete — field is in use"
                                                            onclick="alert('This field is currently in use on the form and cannot be deleted. Set it to inactive first, then try again.')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    @else
                                                        <form method="POST" action="{{ route('fc-reg.admin.form-builder.group-field.delete', $gf) }}" class="fc-fb-actions__form" onsubmit="return confirm('Delete?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- ADD FIELD MODAL --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="addFieldModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="addFieldForm" action="{{ route('fc-reg.admin.form-builder.field.store', $step) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addFieldFormAlert" class="alert py-2 small mb-3 d-none" role="alert"></div>
                @include('admin.form-builder._field-form', ['prefix' => 'add', 'field' => null, 'showTargetTable' => true])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="addFieldSubmitBtn">Add Field</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT FIELD MODAL --}}
<div class="modal fade" id="editFieldModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="editFieldForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.form-builder._field-form', ['prefix' => 'edit', 'field' => null, 'showTargetTable' => true])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Field</button>
            </div>
        </form>
    </div>
</div>

{{-- ADD GROUP MODAL (Step 3) --}}
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('fc-reg.admin.form-builder.group.store', $step) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Add Field Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label small fw-semibold">Group Name (slug)</label><input type="text" name="group_name" class="form-control" required placeholder="e.g. qualifications"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Group Label</label><input type="text" name="group_label" class="form-control" required placeholder="e.g. Educational Qualifications"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Target Table</label><input type="text" name="target_table" class="form-control" required placeholder="e.g. student_master_qualification_details"></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label small fw-semibold">Save Mode</label><select name="save_mode" class="form-select"><option value="replace_all">Replace All</option><option value="upsert">Upsert</option></select></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Min Rows</label><input type="number" name="min_rows" class="form-control" value="0" min="0"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Max Rows</label><input type="number" name="max_rows" class="form-control" value="20" min="1"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Group</button></div>
        </form>
    </div>
</div>

{{-- ADD/EDIT GROUP FIELD MODAL --}}
<div class="modal fade" id="groupFieldModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="groupFieldForm" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="gfMethod" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="groupFieldModalTitle">Add Group Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.form-builder._field-form', ['prefix' => 'gf', 'field' => null, 'showTargetTable' => false])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="gfSubmitBtn">Add Field</button>
            </div>
        </form>
    </div>
</div>

{{-- ADD DOCUMENT MASTER MODAL --}}
<div class="modal fade" id="addDocMasterModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('fc-reg.admin.form-builder.doc-master.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Name</label>
                    <input type="text" name="document_name" class="form-control" required placeholder="e.g. Aadhaar Card">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Code</label>
                    <input type="text" name="document_code" class="form-control" required placeholder="e.g. AADHAAR">
                    <small class="text-muted">Unique code for this document type</small>
                </div>
                <div class="d-flex gap-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="addDocMandatory">
                        <label class="form-check-label small" for="addDocMandatory">Mandatory</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addDocActive" checked>
                        <label class="form-check-label small" for="addDocActive">Active</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Document</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT DOCUMENT MASTER MODAL --}}
<div class="modal fade" id="editDocMasterModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editDocMasterForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Name</label>
                    <input type="text" name="document_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Code</label>
                    <input type="text" name="document_code" class="form-control" required>
                </div>
                <div class="d-flex gap-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="editDocMandatory">
                        <label class="form-check-label small" for="editDocMandatory">Mandatory</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editDocActive">
                        <label class="form-check-label small" for="editDocActive">Active</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Document</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const STEP_TARGET_TABLE = '{{ $step->target_table }}';

function syncTargetColumnFromFieldName(form) {
    const nameInput = form.querySelector('.field-name-input');
    const hidden = form.querySelector('.target-column-sync');
    if (nameInput && hidden) {
        hidden.value = (nameInput.value || '').trim();
    }
}

function bindFieldNameSync(form) {
    const nameInput = form.querySelector('.field-name-input');
    if (!nameInput || nameInput.dataset.syncBound === '1') {
        return;
    }
    nameInput.dataset.syncBound = '1';
    nameInput.addEventListener('input', function() {
        syncTargetColumnFromFieldName(form);
    });
    nameInput.addEventListener('blur', function() {
        syncTargetColumnFromFieldName(form);
        const labelInput = form.querySelector('[name="label"]');
        const raw = (nameInput.value || '').trim();
        if (labelInput && !labelInput.value && raw) {
            labelInput.value = raw.replace(/_/g, ' ').replace(/\bid\b/gi, 'ID')
                .replace(/\b\w/g, function(c) { return c.toUpperCase(); });
        }
    });
}

function fcOptionsJsonToCommaList(jsonStr) {
    if (!jsonStr || !String(jsonStr).trim()) return '';
    try {
        var arr = JSON.parse(jsonStr);
        if (!Array.isArray(arr)) return String(jsonStr).trim();
        return arr.map(function (item) {
            if (item && typeof item === 'object') {
                var v = item.label != null ? item.label : item.value;
                return v != null ? String(v).trim() : '';
            }
            return String(item).trim();
        }).filter(Boolean).join(', ');
    } catch (e) {
        return String(jsonStr).trim();
    }
}

function fcCommaListToOptionsJson(commaStr) {
    var parts = String(commaStr || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    if (!parts.length) return '';
    return JSON.stringify(parts.map(function (p) { return { value: p, label: p }; }));
}

function syncOptionsJsonFromList(form) {
    var visible = form.querySelector('.fc-options-list-input');
    var hidden = form.querySelector('.fc-options-json-input');
    if (!hidden) return;
    hidden.value = visible ? fcCommaListToOptionsJson(visible.value) : '';
}

function syncOptionsListFromJson(form, jsonStr) {
    var visible = form.querySelector('.fc-options-list-input');
    var hidden = form.querySelector('.fc-options-json-input');
    if (!hidden) return;
    hidden.value = jsonStr || '';
    if (visible) visible.value = fcOptionsJsonToCommaList(jsonStr);
}

function getFcChoiceSource(form) {
    var checked = form.querySelector('.fc-choice-source-input:checked');
    return checked ? checked.value : 'fixed';
}

function setFcChoiceSource(form, source) {
    var radio = form.querySelector('.fc-choice-source-input[value="' + source + '"]');
    if (radio) radio.checked = true;
}

function detectFcChoiceSourceFromForm(form) {
    var lookupTable = form.querySelector('[name="lookup_table"]');
    if (lookupTable && String(lookupTable.value || '').trim()) {
        return 'lookup';
    }
    return 'fixed';
}

function clearInactiveChoiceFields(form) {
    var typeEl = form.querySelector('[name="field_type"]');
    if (!typeEl) return;
    var type = typeEl.value;
    if (['select', 'radio', 'checkbox'].indexOf(type) === -1) return;

    var source = type === 'select' ? getFcChoiceSource(form) : 'fixed';
    if (source === 'fixed') {
        ['lookup_table', 'lookup_value_column', 'lookup_label_column', 'lookup_order_column'].forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el) el.value = '';
        });
    } else {
        var hidden = form.querySelector('.fc-options-json-input');
        var visible = form.querySelector('.fc-options-list-input');
        if (hidden) hidden.value = '';
        if (visible) visible.value = '';
    }
}

function toggleFcFieldFormSections(form) {
    if (!form) return;
    var typeEl = form.querySelector('[name="field_type"]');
    if (!typeEl) return;
    var type = typeEl.value;
    var needsChoices = ['select', 'radio', 'checkbox'].indexOf(type) !== -1;
    var showPicker = type === 'select';
    var showFile = type === 'file';

    if (needsChoices && type !== 'select') {
        setFcChoiceSource(form, 'fixed');
    }

    var source = type === 'select' ? getFcChoiceSource(form) : 'fixed';
    var showStatic = needsChoices && source === 'fixed';
    var showLookup = type === 'select' && source === 'lookup';

    form.querySelectorAll('[data-fc-field-section="choice-picker"]').forEach(function (el) {
        el.classList.toggle('d-none', !showPicker);
    });
    form.querySelectorAll('[data-fc-field-section="choice-static"]').forEach(function (el) {
        el.classList.toggle('d-none', !showStatic);
    });
    form.querySelectorAll('[data-fc-field-section="choice-lookup"]').forEach(function (el) {
        el.classList.toggle('d-none', !showLookup);
    });
    form.querySelectorAll('[data-fc-field-section="file"]').forEach(function (el) {
        el.classList.toggle('d-none', !showFile);
    });

    if (showFile) {
        var maxKb = form.querySelector('[name="file_max_kb"]');
        var ext = form.querySelector('[name="file_extensions"]');
        if (maxKb && !String(maxKb.value || '').trim()) maxKb.value = '500';
        if (ext && !String(ext.value || '').trim()) ext.value = 'jpeg,jpg,png,pdf';
    }
}

function bindFcFieldTypeVisibility(form) {
    var typeEl = form.querySelector('[name="field_type"]');
    if (!typeEl) return;
    if (typeEl.dataset.fcTypeBound !== '1') {
        typeEl.dataset.fcTypeBound = '1';
        typeEl.addEventListener('change', function () {
            toggleFcFieldFormSections(form);
        });
    }
    form.querySelectorAll('.fc-choice-source-input').forEach(function (radio) {
        if (radio.dataset.fcSourceBound === '1') return;
        radio.dataset.fcSourceBound = '1';
        radio.addEventListener('change', function () {
            toggleFcFieldFormSections(form);
        });
    });
    toggleFcFieldFormSections(form);
}

function showAddFieldAlert(message, type) {
    var alertEl = document.getElementById('addFieldFormAlert');
    if (!alertEl) return;
    alertEl.className = 'alert py-2 small mb-3 alert-' + (type || 'success');
    alertEl.textContent = message;
    alertEl.classList.remove('d-none');
}

function hideAddFieldAlert() {
    var alertEl = document.getElementById('addFieldFormAlert');
    if (alertEl) alertEl.classList.add('d-none');
}

function resetAddFieldForm(form) {
    if (!form) return;
    form.reset();
    form.querySelectorAll('.target-column-sync').forEach(function (el) { el.value = ''; });
    form.querySelectorAll('.fc-options-json-input, .fc-options-list-input').forEach(function (el) { el.value = ''; });
    ['lookup_table', 'lookup_value_column', 'lookup_label_column', 'lookup_order_column'].forEach(function (name) {
        var el = form.querySelector('[name="' + name + '"]');
        if (el) el.value = '';
    });
    var typeEl = form.querySelector('[name="field_type"]');
    if (typeEl) typeEl.value = 'text';
    setFcChoiceSource(form, 'fixed');
    var css = form.querySelector('[name="css_class"]');
    if (css) css.value = 'col-md-6';
    var active = form.querySelector('[name="is_active"]');
    if (active) active.checked = true;
    var req = form.querySelector('[name="is_required"]');
    if (req) req.checked = false;
    toggleFcFieldFormSections(form);
    var nameInput = form.querySelector('.field-name-input');
    if (nameInput) nameInput.focus();
}

function bindOptionsListSync(form) {
    var optionsInput = form.querySelector('.fc-options-list-input');
    if (!optionsInput || optionsInput.dataset.fcOptionsBound === '1') return;
    optionsInput.dataset.fcOptionsBound = '1';
    optionsInput.addEventListener('input', function () { syncOptionsJsonFromList(form); });
    optionsInput.addEventListener('blur', function () { syncOptionsJsonFromList(form); });
}

document.querySelectorAll('#editFieldModal form, #groupFieldModal form').forEach(function (form) {
    bindFieldNameSync(form);
    bindFcFieldTypeVisibility(form);
    bindOptionsListSync(form);
    form.addEventListener('submit', function () {
        syncTargetColumnFromFieldName(form);
        syncOptionsJsonFromList(form);
        clearInactiveChoiceFields(form);
    });
});

var addFieldForm = document.getElementById('addFieldForm');
if (addFieldForm) {
    bindFieldNameSync(addFieldForm);
    bindFcFieldTypeVisibility(addFieldForm);
    bindOptionsListSync(addFieldForm);

    addFieldForm.addEventListener('submit', function (e) {
        e.preventDefault();
        syncTargetColumnFromFieldName(addFieldForm);
        syncOptionsJsonFromList(addFieldForm);
        clearInactiveChoiceFields(addFieldForm);

        var submitBtn = document.getElementById('addFieldSubmitBtn');
        var originalHtml = submitBtn ? submitBtn.innerHTML : 'Add Field';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';
        }

        fetch(addFieldForm.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: new FormData(addFieldForm)
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
            });
        })
        .then(function (result) {
            if (!result.ok) {
                var msg = result.data.message || 'Could not add field.';
                if (result.data.errors) {
                    msg = Object.values(result.data.errors).flat().join(' ');
                }
                showAddFieldAlert(msg, 'danger');
                return;
            }
            showAddFieldAlert(result.data.message || 'Field added successfully.', 'success');
            var tbody = document.getElementById('fieldsList');
            if (tbody && result.data.row_html) {
                tbody.insertAdjacentHTML('beforeend', result.data.row_html);
            }
            var countEl = document.getElementById('fcFieldsCountLabel');
            if (countEl && result.data.fields_count != null) {
                countEl.textContent = 'Fields (' + result.data.fields_count + ')';
            }
            resetAddFieldForm(addFieldForm);
        })
        .catch(function () {
            showAddFieldAlert('Could not add field. Please try again.', 'danger');
        })
        .finally(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        });
    });
}

var addFieldModalEl = document.getElementById('addFieldModal');
if (addFieldModalEl) {
    addFieldModalEl.addEventListener('shown.bs.modal', function () {
        if (addFieldForm) bindFcFieldTypeVisibility(addFieldForm);
    });
    addFieldModalEl.addEventListener('hidden.bs.modal', function () {
        hideAddFieldAlert();
        resetAddFieldForm(addFieldForm);
    });
}

['editFieldModal', 'groupFieldModal'].forEach(function (modalId) {
    var modalEl = document.getElementById(modalId);
    if (!modalEl) return;
    modalEl.addEventListener('shown.bs.modal', function () {
        var form = modalEl.querySelector('form');
        if (form) bindFcFieldTypeVisibility(form);
    });
});

function normalizeFieldColumnLayout(value) {
    var allowed = ['col-md-3', 'col-md-6', 'col-md-9', 'col-md-12'];
    var v = (value || '').trim();
    if (allowed.indexOf(v) !== -1) return v;
    if (v === 'col-12' || v === 'col-md-12') return 'col-md-12';
    if (v === 'col-md-4' || v === 'col-3' || v === 'col-md-3') return 'col-md-3';
    if (v === 'col-9' || v === 'col-md-9') return 'col-md-9';
    if (v === 'col-6' || v === 'col-md-6') return 'col-md-6';
    return 'col-md-6';
}

// ── Edit field ─────────────────────────────────────────────────────
function editField(field) {
    const form = document.getElementById('editFieldForm');
    form.action = '{{ url("fc-reg/admin/form-builder/fields") }}/' + field.id;

    const inputs = {
        field_name: field.field_name,
        label: field.label, field_type: field.field_type,
        target_table: field.target_table, target_column: field.target_column,
        validation_rules: field.validation_rules, placeholder: field.placeholder,
        help_text: field.help_text, default_value: field.default_value,
        lookup_table: field.lookup_table,
        lookup_value_column: field.lookup_value_column, lookup_label_column: field.lookup_label_column,
        lookup_order_column: field.lookup_order_column, section_heading: field.section_heading,
        file_max_kb: field.file_max_kb, file_extensions: field.file_extensions
    };
    for (const [k, v] of Object.entries(inputs)) {
        const el = form.querySelector(`[name="${k}"]`);
        if (el) el.value = v || '';
    }
    const cssEl = form.querySelector('[name="css_class"]');
    if (cssEl) cssEl.value = normalizeFieldColumnLayout(field.css_class);
    syncOptionsListFromJson(form, field.options_json || '');
    setFcChoiceSource(form, (field.lookup_table && String(field.lookup_table).trim()) ? 'lookup' : 'fixed');
    syncTargetColumnFromFieldName(form);
    form.querySelector('[name="is_required"]').checked = !!field.is_required;
    form.querySelector('[name="is_active"]').checked = field.is_active !== false && field.is_active !== 0;
    toggleFcFieldFormSections(form);

    new bootstrap.Modal(document.getElementById('editFieldModal')).show();
}

function moveField(fieldId, direction) {
    const rows = [...document.querySelectorAll('#fieldsList tr')];
    const ids = rows.map(r => parseInt(r.dataset.id));
    const idx = ids.indexOf(fieldId);
    if (direction === 'up' && idx > 0) { [ids[idx-1], ids[idx]] = [ids[idx], ids[idx-1]]; }
    else if (direction === 'down' && idx < ids.length - 1) { [ids[idx], ids[idx+1]] = [ids[idx+1], ids[idx]]; }
    else return;

    fetch('{{ route("fc-reg.admin.form-builder.field.reorder") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ order: ids })
    }).then(() => location.reload());
}

function openAddGroupFieldModal(groupId) {
    const form = document.getElementById('groupFieldForm');
    form.action = '{{ url("fc-reg/admin/form-builder/groups") }}/' + groupId + '/fields';
    document.getElementById('gfMethod').value = 'POST';
    document.getElementById('groupFieldModalTitle').textContent = 'Add Group Field';
    document.getElementById('gfSubmitBtn').textContent = 'Add Field';
    form.querySelectorAll('input[type=text], input[type=number], textarea').forEach(el => el.value = '');
    form.querySelectorAll('.fc-options-json-input').forEach(el => { el.value = ''; });
    form.querySelectorAll('.target-column-sync').forEach(el => { el.value = ''; });
    form.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = false);
    form.querySelector('[name="css_class"]').value = 'col-md-6';
    var typeEl = form.querySelector('[name="field_type"]');
    if (typeEl) typeEl.value = 'text';
    setFcChoiceSource(form, 'fixed');
    const isActiveEl = form.querySelector('[name="is_active"]');
    if (isActiveEl) isActiveEl.checked = true;
    toggleFcFieldFormSections(form);

    new bootstrap.Modal(document.getElementById('groupFieldModal')).show();
}

function editGroupField(field) {
    const form = document.getElementById('groupFieldForm');
    form.action = '{{ url("fc-reg/admin/form-builder/group-fields") }}/' + field.id;
    document.getElementById('gfMethod').value = 'PUT';
    document.getElementById('groupFieldModalTitle').textContent = 'Edit Group Field';
    document.getElementById('gfSubmitBtn').textContent = 'Update Field';
    const inputs = {
        field_name: field.field_name,
        label: field.label, field_type: field.field_type,
        target_column: field.target_column, validation_rules: field.validation_rules,
        placeholder: field.placeholder,
        lookup_table: field.lookup_table, lookup_value_column: field.lookup_value_column,
        lookup_label_column: field.lookup_label_column
    };
    for (const [k, v] of Object.entries(inputs)) {
        const el = form.querySelector(`[name="${k}"]`);
        if (el) el.value = v || '';
    }
    const gfCssEl = form.querySelector('[name="css_class"]');
    if (gfCssEl) gfCssEl.value = normalizeFieldColumnLayout(field.css_class);
    syncOptionsListFromJson(form, field.options_json || '');
    setFcChoiceSource(form, (field.lookup_table && String(field.lookup_table).trim()) ? 'lookup' : 'fixed');
    syncTargetColumnFromFieldName(form);
    form.querySelector('[name="is_required"]').checked = !!field.is_required;
    const isActiveEl = form.querySelector('[name="is_active"]');
    if (isActiveEl) isActiveEl.checked = field.is_active !== false && field.is_active !== 0;
    toggleFcFieldFormSections(form);

    new bootstrap.Modal(document.getElementById('groupFieldModal')).show();
}

function editDocMaster(doc) {
    const form = document.getElementById('editDocMasterForm');
    form.action = '{{ url("fc-reg/admin/form-builder/doc-masters") }}/' + doc.id;
    form.querySelector('[name="document_name"]').value = doc.document_name || '';
    form.querySelector('[name="document_code"]').value = doc.document_code || '';
    form.querySelector('[name="is_mandatory"]').checked = !!doc.is_mandatory;
    form.querySelector('[name="is_active"]').checked = doc.is_active !== false && doc.is_active !== 0;
    new bootstrap.Modal(document.getElementById('editDocMasterModal')).show();
}

function moveDocMaster(docId, direction) {
    const rows = [...document.querySelectorAll('#docMastersList tr')];
    const ids = rows.map(r => parseInt(r.dataset.id));
    const idx = ids.indexOf(docId);
    if (direction === 'up' && idx > 0) { [ids[idx-1], ids[idx]] = [ids[idx], ids[idx-1]]; }
    else if (direction === 'down' && idx < ids.length - 1) { [ids[idx], ids[idx+1]] = [ids[idx+1], ids[idx]]; }
    else return;

    fetch('{{ route("fc-reg.admin.form-builder.doc-master.reorder") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ order: ids })
    }).then(() => location.reload());
}
</script>
@endpush
