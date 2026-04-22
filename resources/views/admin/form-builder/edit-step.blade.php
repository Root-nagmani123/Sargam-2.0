@extends('admin.layouts.master')
@section('title', 'Edit Step: ' . $step->step_name)

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
        @if($step->form)
            <a href="{{ route('fc-reg.admin.forms.edit', $step->form) }}" class="btn btn-sm btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
        @else
            <a href="{{ route('fc-reg.admin.form-builder.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
        @endif
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
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Step Name</label>
                        <input type="text" name="step_name" class="form-control" value="{{ $step->step_name }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Icon Class</label>
                        <input type="text" name="icon" class="form-control" value="{{ $step->icon }}" placeholder="bi-person-fill">
                    </div>
                    <div class="col-md-4">
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

    {{-- FIELDS SECTION --}}
    @if($step->step_slug !== 'step3')
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted">Fields ({{ $step->fields->count() }})</h6>
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
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fieldsList">
                        @foreach($step->fields as $field)
                            <tr data-id="{{ $field->id }}">
                                <td class="text-muted small">{{ $field->display_order }}</td>
                                <td class="fw-semibold small">{{ $field->label }}</td>
                                <td><code class="small">{{ $field->field_name }}</code></td>
                                <td><span class="badge bg-light text-dark">{{ $field->field_type }}</span></td>
                                <td><code class="small">{{ $field->target_column }}</code></td>
                                <td class="small text-muted">{{ $field->section_heading ?? '—' }}</td>
                                <td>
                                    @if($field->is_required)
                                        <span class="badge bg-danger">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($field->is_active)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editField({{ json_encode($field) }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveField({{ $field->id }}, 'up')" title="Move Up">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveField({{ $field->id }}, 'down')" title="Move Down">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <form method="POST" action="{{ route('fc-reg.admin.form-builder.field.delete', $field) }}" class="d-inline" onsubmit="return confirm('Delete this field?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- DOCUMENT CHECKLIST SECTION (Documents step) --}}
    @if($step->step_slug === 'documents')
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
                            <th style="width:140px;">Actions</th>
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
                                <td>
                                    <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editDocMaster({{ json_encode($doc) }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveDocMaster({{ $doc->id }}, 'up')" title="Move Up">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveDocMaster({{ $doc->id }}, 'down')" title="Move Down">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <form method="POST" action="{{ route('fc-reg.admin.form-builder.doc-master.delete', $doc) }}" class="d-inline" onsubmit="return confirm('Delete this document?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- GROUPS SECTION (Step 3) --}}
    @if($step->step_slug === 'step3')
    <div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 text-uppercase small fw-bold text-muted">Field Groups / Tabs ({{ $step->fieldGroups->count() }})</h6>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                <i class="bi bi-plus-circle me-1"></i>Add Group
            </button>
        </div>
        <div class="card-body">
            {{-- Group tabs --}}
            <ul class="nav nav-tabs mb-3" role="tablist">
                @foreach($step->fieldGroups as $gi => $group)
                    <li class="nav-item">
                        <a class="nav-link {{ $gi === 0 ? 'active' : '' }}" data-bs-toggle="tab" href="#grp-{{ $group->id }}">
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
                                        <th>#</th><th>Label</th><th>Field Name</th><th>Type</th><th>Target Column</th><th>Required</th><th>Active</th><th>Actions</th>
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
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editGroupField({{ json_encode($gf) }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" action="{{ route('fc-reg.admin.form-builder.group-field.delete', $gf) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="bi bi-trash"></i></button>
                                                </form>
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
        <form method="POST" action="{{ route('fc-reg.admin.form-builder.field.store', $step) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('admin.form-builder._field-form', ['prefix' => 'add', 'field' => null, 'showTargetTable' => true])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Field</button>
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
// ── Select2: Load columns from target table ──────────────────────
const COLUMNS_API = '{{ route("fc-reg.admin.forms.api.table-columns") }}';
const STEP_TARGET_TABLE = '{{ $step->target_table }}';
let columnsCache = {};

function loadColumnsForSelect(selectEl, tableName, selectedValue) {
    const $select = $(selectEl);
    if (!tableName) return;

    // Check cache
    if (columnsCache[tableName]) {
        populateSelect($select, columnsCache[tableName], selectedValue);
        return;
    }

    $.get(COLUMNS_API, { table: tableName }, function(data) {
        columnsCache[tableName] = data;
        populateSelect($select, data, selectedValue);
    });
}

function populateSelect($select, columns, selectedValue) {
    $select.empty().append('<option value="">-- Select column --</option>');
    columns.forEach(function(col) {
        const option = new Option(col.text + ' (' + col.type + ')', col.id, false, col.id === selectedValue);
        $select.append(option);
    });
    $select.trigger('change.select2');
}

function initSelect2ForModal(modalEl) {
    const $modal = $(modalEl);
    $modal.find('.field-name-select2').each(function() {
        const $sel = $(this);
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Select column --',
            allowClear: true,
            dropdownParent: $modal
        });

        // Auto-fill target_column and generate label
        $sel.off('select2:select').on('select2:select', function(e) {
            const val = e.params.data.id;
            const form = this.closest('form');
            const targetCol = form.querySelector('[name="target_column"]');
            if (targetCol) targetCol.value = val;

            // Auto-generate label from column name (full_name → Full Name)
            const labelInput = form.querySelector('[name="label"]');
            if (labelInput && !labelInput.value) {
                labelInput.value = val.replace(/_/g, ' ').replace(/\bid\b/gi, 'ID')
                    .replace(/\b\w/g, c => c.toUpperCase());
            }
        });

        $sel.off('select2:clear').on('select2:clear', function() {
            const form = this.closest('form');
            const targetCol = form.querySelector('[name="target_column"]');
            if (targetCol) targetCol.value = '';
        });
    });
}

// ── Init on modal open ───────────────────────────────────────────
document.querySelectorAll('.modal').forEach(function(modal) {
    modal.addEventListener('shown.bs.modal', function() {
        initSelect2ForModal(this);

        // Load columns for the step's target table
        this.querySelectorAll('.field-name-select2').forEach(function(sel) {
            const form = sel.closest('form');
            // Use custom target_table if set, otherwise step default
            const customTable = form.querySelector('[name="target_table"]');
            const table = (customTable && customTable.value) ? customTable.value : STEP_TARGET_TABLE;
            const currentVal = sel.value || '';
            loadColumnsForSelect(sel, table, currentVal);
        });
    });
});

// ── Fallback: copy field_name to target_column on form submit ────
document.querySelectorAll('#addFieldModal form, #editFieldModal form, #groupFieldModal form').forEach(function(form) {
    form.addEventListener('submit', function() {
        const sel = form.querySelector('.field-name-select2');
        const targetCol = form.querySelector('[name="target_column"]');
        if (sel && targetCol && !targetCol.value && sel.value) {
            targetCol.value = sel.value;
        }
    });
});

// ── When target_table changes, reload columns ────────────────────
document.querySelectorAll('[name="target_table"]').forEach(function(input) {
    input.addEventListener('change', function() {
        const form = this.closest('form');
        const sel = form.querySelector('.field-name-select2');
        if (sel) {
            const table = this.value || STEP_TARGET_TABLE;
            loadColumnsForSelect(sel, table, '');
            const targetCol = form.querySelector('[name="target_column"]');
            if (targetCol) targetCol.value = '';
        }
    });
});

// ── Edit field: set Select2 value ────────────────────────────────
function editField(field) {
    const form = document.getElementById('editFieldForm');
    form.action = '{{ url("fc-reg/admin/form-builder/fields") }}/' + field.id;

    const inputs = {
        label: field.label, field_type: field.field_type,
        target_table: field.target_table, target_column: field.target_column,
        validation_rules: field.validation_rules, placeholder: field.placeholder,
        help_text: field.help_text, default_value: field.default_value,
        options_json: field.options_json, lookup_table: field.lookup_table,
        lookup_value_column: field.lookup_value_column, lookup_label_column: field.lookup_label_column,
        lookup_order_column: field.lookup_order_column, section_heading: field.section_heading,
        css_class: field.css_class, file_max_kb: field.file_max_kb, file_extensions: field.file_extensions
    };
    for (const [k, v] of Object.entries(inputs)) {
        const el = form.querySelector(`[name="${k}"]`);
        if (el) el.value = v || '';
    }
    form.querySelector('[name="is_required"]').checked = !!field.is_required;
    form.querySelector('[name="is_active"]').checked = field.is_active !== false && field.is_active !== 0;

    // Show the modal (Select2 will init on shown.bs.modal, then we load columns with selected value)
    const modalEl = document.getElementById('editFieldModal');
    const modal = new bootstrap.Modal(modalEl);

    // After modal opens, load columns with existing field_name selected
    modalEl.addEventListener('shown.bs.modal', function handler() {
        const table = field.target_table || STEP_TARGET_TABLE;
        const sel = form.querySelector('.field-name-select2');
        loadColumnsForSelect(sel, table, field.field_name);
        modalEl.removeEventListener('shown.bs.modal', handler);
    });

    modal.show();
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
    form.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = false);
    form.querySelector('[name="css_class"]').value = 'col-md-6';
    const isActiveEl = form.querySelector('[name="is_active"]');
    if (isActiveEl) isActiveEl.checked = true;

    // Clear Select2
    const $sel = $(form).find('.field-name-select2');
    if ($sel.length) {
        $sel.val('').trigger('change');
    }

    // Find group's target table to load correct columns
    @if($step->fieldGroups->isNotEmpty())
    const groupMap = {!! json_encode($step->fieldGroups->pluck('target_table', 'id')) !!};
    const groupTable = groupMap[groupId] || STEP_TARGET_TABLE;
    const modalEl = document.getElementById('groupFieldModal');
    modalEl.addEventListener('shown.bs.modal', function handler() {
        const sel = form.querySelector('.field-name-select2');
        loadColumnsForSelect(sel, groupTable, '');
        modalEl.removeEventListener('shown.bs.modal', handler);
    });
    @endif

    new bootstrap.Modal(document.getElementById('groupFieldModal')).show();
}

function editGroupField(field) {
    const form = document.getElementById('groupFieldForm');
    form.action = '{{ url("fc-reg/admin/form-builder/group-fields") }}/' + field.id;
    document.getElementById('gfMethod').value = 'PUT';
    document.getElementById('groupFieldModalTitle').textContent = 'Edit Group Field';
    document.getElementById('gfSubmitBtn').textContent = 'Update Field';
    const inputs = {
        label: field.label, field_type: field.field_type,
        target_column: field.target_column, validation_rules: field.validation_rules,
        placeholder: field.placeholder, options_json: field.options_json,
        lookup_table: field.lookup_table, lookup_value_column: field.lookup_value_column,
        lookup_label_column: field.lookup_label_column, css_class: field.css_class
    };
    for (const [k, v] of Object.entries(inputs)) {
        const el = form.querySelector(`[name="${k}"]`);
        if (el) el.value = v || '';
    }
    form.querySelector('[name="is_required"]').checked = !!field.is_required;
    const isActiveEl = form.querySelector('[name="is_active"]');
    if (isActiveEl) isActiveEl.checked = field.is_active !== false && field.is_active !== 0;

    // Show modal, then load columns with field_name selected
    const modalEl = document.getElementById('groupFieldModal');
    modalEl.addEventListener('shown.bs.modal', function handler() {
        // Find group's target table
        @if($step->fieldGroups->isNotEmpty())
        const groupMap = {!! json_encode($step->fieldGroups->pluck('target_table', 'id')) !!};
        const groupTable = groupMap[field.group_id] || STEP_TARGET_TABLE;
        @else
        const groupTable = STEP_TARGET_TABLE;
        @endif
        const sel = form.querySelector('.field-name-select2');
        loadColumnsForSelect(sel, groupTable, field.field_name);
        modalEl.removeEventListener('shown.bs.modal', handler);
    });

    new bootstrap.Modal(modalEl).show();
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
