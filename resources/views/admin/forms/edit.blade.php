@extends('admin.layouts.master')
@section('title', 'Edit: ' . $form->form_name)

@section('setup_content')
<div class="container-fluid py-4 px-4">
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
            <form method="POST" action="{{ route('fc-reg.admin.forms.update', $form) }}" class="fc-form-settings">
                @csrf @method('PUT')

                {{-- Single row: Name | Icon | Consolidation | Active + Save --}}
                <div class="row g-3 g-lg-4 align-items-start fc-form-settings__main-row">
                    <div class="col-xl-3 col-lg-6">
                        <label class="form-label small fw-semibold mb-1" for="fcEditFormName">Form Name</label>
                        <input type="text" name="form_name" id="fcEditFormName" class="form-control fc-form-settings__field rounded-2" value="{{ $form->form_name }}" required>
                        <small class="text-muted d-block mt-2 pt-2 border-top border-light-subtle">Slug: <code class="user-select-all">{{ $form->form_slug }}</code></small>
                    </div>
                    <div class="col-xl-3 col-lg-6">
                        @include('admin.forms.partials.fc-form-icon-picker', [
                            'selectedIcon' => old('icon', $form->icon ?: 'bi-file-text'),
                            'selectId' => 'fcEditFormIcon',
                            'formSelect' => '',
                            'label' => 'Form icon',
                            'labelClass' => 'form-label small fw-semibold mb-1',
                            'showHelp' => false,
                            'wrapperClass' => 'mb-0',
                            'toggleClass' => 'fc-form-settings__field rounded-2',
                        ])
                        <small class="text-muted d-block mt-2 pt-2 border-top border-light-subtle">User ID Column: <code class="user-select-all">{{ $form->user_identifier }}</code></small>
                    </div>
                    <div class="col-xl-3 col-lg-6">
                        <label class="form-label small fw-semibold mb-1" for="fcEditConsolidationTable">Consolidation / Tracking Table</label>
                        <select name="consolidation_table" id="fcEditConsolidationTable" class="form-select fc-form-settings__field rounded-2">
                            <option value="">-- None (no step tracking) --</option>
                            @foreach($tables as $table)
                                <option value="{{ $table }}" {{ $form->consolidation_table === $table ? 'selected' : '' }}>{{ $table }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-2 pt-2 border-top border-light-subtle">Existing table to track step completion. Leave blank if not needed.</small>
                    </div>
                    <div class="col-xl-3 col-lg-6">
                        <label class="form-label small fw-semibold mb-1" for="formActive">Status</label>
                        <div class="d-flex align-items-center gap-2 flex-wrap fc-form-settings__field fc-form-settings__actions-cell rounded-2">
                            <div class="form-check form-switch mb-0 flex-shrink-0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="formActive" {{ $form->is_active ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="formActive">Active</label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                                <i class="bi bi-check-lg me-1"></i>Save changes
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Description full width --}}
                <div class="row mt-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1" for="fcEditFormDescription">Description</label>
                        <textarea name="description" id="fcEditFormDescription" class="form-control rounded-2" rows="3" placeholder="Brief description of this form">{{ $form->description }}</textarea>
                    </div>
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
                <div class="px-3 pt-3 pb-2 border-bottom border-light">
                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle me-1 text-primary"></i>
                        Trainees see steps <strong>in order</strong>: a step is only shown as <strong>Completed</strong> after every earlier step is finished (even if <code>step2_done</code> or detail completion columns are already set in the database).
                        Set <strong>Consolidation / Tracking Table</strong> in Form Settings (e.g. <code>student_masters</code>) so tracker columns match where flags are stored.
                    </p>
                </div>
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
                                <th style="width:280px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="stepsList">
                            @foreach($form->steps as $step)
                                @php
                                    $stepEditJson = json_encode([
                                        'u' => route('fc-reg.admin.forms.step.update', $step),
                                        'step_number' => $step->step_number,
                                        'step_name' => $step->step_name,
                                        'step_slug' => $step->step_slug,
                                        'target_table' => $step->target_table,
                                        'completion_column' => $step->completion_column,
                                        'tracker_column' => $step->tracker_column,
                                        'description' => $step->description,
                                        'icon' => $step->icon ?: 'bi-file-text',
                                        'is_active' => (bool) $step->is_active,
                                    ], JSON_INVALID_UTF8_SUBSTITUTE);
                                    $stepEditJson = $stepEditJson === false ? '{}' : $stepEditJson;
                                    $stepEditB64 = base64_encode($stepEditJson);
                                @endphp
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
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary py-0 px-2"
                                            title="Edit step name, slug, tables, tracker…"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editStepModal"
                                            data-step-b64="{{ $stepEditB64 }}">
                                            <i class="bi bi-gear me-1"></i>Step
                                        </button>
                                        <a href="{{ route('fc-reg.admin.form-builder.step', $step) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit fields for this step">
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

    {{-- Quick Access: URLs for users (same layout for both) --}}
    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-body">
            <h6 class="text-uppercase small fw-bold text-muted mb-2">Form URL for Users</h6>
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                <input type="text" class="form-control" readonly value="{{ route('fc-reg.forms.dashboard', $form) }}" id="formUrl">
                <button type="button" class="btn btn-outline-secondary" onclick="copyInputToClipboard('formUrl', this)">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>

            <h6 class="text-uppercase small fw-bold text-muted mb-2">Landing Page URL for Users</h6>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                <input type="text" class="form-control" readonly value="{{ route('frontpage.index', ['form' => $form->getRouteKey()]) }}" id="landingUrl">
                <button type="button" class="btn btn-outline-secondary" onclick="copyInputToClipboard('landingUrl', this)">
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
                        @include('admin.forms.partials.fc-form-icon-picker', [
                            'selectedIcon' => old('icon', 'bi-file-text'),
                            'selectId' => 'fcAddStepIcon',
                            'formSelect' => 'form-select-sm',
                            'label' => 'Step icon',
                            'labelClass' => 'form-label small fw-semibold',
                        ])
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

{{-- EDIT STEP MODAL --}}
<div class="modal fade" id="editStepModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="" class="modal-content" id="editStepForm">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit step</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="editStepMeta">Step <span id="editStepNumberLabel"></span> — update slug or target table only if you know downstream URLs and data rely on them.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Step Name <span class="text-danger">*</span></label>
                        <input type="text" name="step_name" id="editStepName" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Step Slug <span class="text-danger">*</span></label>
                        <input type="text" name="step_slug" id="editStepSlug" class="form-control" required pattern="[a-z0-9\-_]+" title="Lowercase letters, numbers, hyphens, underscores">
                        <small class="text-muted">Must stay unique across all forms.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Table <span class="text-danger">*</span></label>
                        <select name="target_table" id="editStepTargetTable" class="form-select" required>
                            @foreach($tables as $table)
                                <option value="{{ $table }}">{{ $table }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        @include('admin.forms.partials.fc-form-icon-picker', [
                            'selectedIcon' => 'bi-file-text',
                            'selectId' => 'fcEditStepIcon',
                            'formSelect' => 'form-select-sm',
                            'label' => 'Step icon',
                            'labelClass' => 'form-label small fw-semibold',
                            'showHelp' => false,
                        ])
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Completion Column</label>
                        <input type="text" name="completion_column" id="editStepCompletionCol" class="form-control" placeholder="e.g. step1_completed">
                        <small class="text-muted">Column in target table set when step is done</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tracker Column</label>
                        <input type="text" name="tracker_column" id="editStepTrackerCol" class="form-control" placeholder="e.g. step1_done">
                        <small class="text-muted">Column in consolidation table for completion</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" id="editStepDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editStepActive">
                            <label class="form-check-label small fw-semibold" for="editStepActive">Step active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save step</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Match text inputs, selects, and icon trigger to one control height */
    .fc-form-settings .fc-form-settings__field {
        min-height: calc(1.5em + 0.75rem + 2px);
    }
    .fc-form-settings .fc-form-icon-picker [data-fc-icon-toggle].btn.fc-form-settings__field {
        padding-top: 0.375rem;
        padding-bottom: 0.375rem;
    }
    .fc-form-settings .fc-form-settings__actions-cell {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
</style>
@endpush

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

(function () {
    const editModal = document.getElementById('editStepModal');
    if (!editModal) return;
    const editForm = document.getElementById('editStepForm');
    const stepsList = document.getElementById('stepsList');
    let stepEditTriggerBtn = null;

    function parseStepPayloadFromB64(b64) {
        if (!b64) return null;
        try {
            const bin = atob(b64.trim());
            const bytes = new Uint8Array(bin.length);
            for (let i = 0; i < bin.length; i++) {
                bytes[i] = bin.charCodeAt(i);
            }
            const text = new TextDecoder('utf-8').decode(bytes);
            return JSON.parse(text);
        } catch (e) {
            return null;
        }
    }

    if (stepsList) {
        stepsList.addEventListener('click', function (e) {
            const b = e.target.closest('button[data-step-b64]');
            if (b) stepEditTriggerBtn = b;
        }, true);
    }

    editModal.addEventListener('show.bs.modal', function (event) {
        const rel = event.relatedTarget;
        const btn = (rel && rel.getAttribute && rel.getAttribute('data-step-b64')) ? rel : stepEditTriggerBtn;
        stepEditTriggerBtn = null;
        const b64 = btn && btn.getAttribute('data-step-b64');
        if (!b64) return;
        const d = parseStepPayloadFromB64(b64);
        if (!d) return;
        editForm.action = d.u;
        document.getElementById('editStepNumberLabel').textContent = d.step_number;
        document.getElementById('editStepName').value = d.step_name || '';
        document.getElementById('editStepSlug').value = d.step_slug || '';
        const tbl = document.getElementById('editStepTargetTable');
        if (tbl) {
            Array.from(tbl.querySelectorAll('option')).forEach(function (o) {
                if (o.textContent.indexOf('(not in list)') !== -1) {
                    o.remove();
                }
            });
            tbl.value = d.target_table || '';
            if (d.target_table && !Array.from(tbl.options).some(function (o) { return o.value === d.target_table; })) {
                const opt = document.createElement('option');
                opt.value = d.target_table;
                opt.textContent = d.target_table + ' (not in list)';
                opt.selected = true;
                tbl.appendChild(opt);
            }
        }
        document.getElementById('editStepCompletionCol').value = d.completion_column || '';
        document.getElementById('editStepTrackerCol').value = d.tracker_column || '';
        document.getElementById('editStepDescription').value = d.description || '';
        document.getElementById('editStepActive').checked = !!d.is_active;

        const icon = d.icon || 'bi-file-text';
        const wrap = editModal.querySelector('[data-fc-icon-picker]');
        if (wrap) {
            const esc = (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(icon) : icon.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            const optBtn = wrap.querySelector('[data-fc-icon-option][data-value="' + esc + '"]');
            if (optBtn) {
                optBtn.click();
            } else {
                const input = wrap.querySelector('[data-fc-icon-input]');
                const iconEl = wrap.querySelector('[data-fc-icon-toggle-icon]');
                const labelEl = wrap.querySelector('[data-fc-icon-current-label]');
                if (input) input.value = icon;
                if (iconEl) iconEl.className = 'bi ' + icon + ' fs-5 text-primary flex-shrink-0';
                if (labelEl) labelEl.textContent = 'Saved: ' + icon;
            }
        }
    });
})();

function copyTextToClipboard(text) {
    if (!text) {
        return Promise.reject(new Error('Nothing to copy'));
    }
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        ta.style.top = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        ta.setSelectionRange(0, text.length);
        try {
            var ok = document.execCommand('copy');
            document.body.removeChild(ta);
            if (ok) {
                resolve();
            } else {
                reject(new Error('Copy failed'));
            }
        } catch (err) {
            document.body.removeChild(ta);
            reject(err);
        }
    });
}

function copyInputToClipboard(inputId, btn) {
    var el = document.getElementById(inputId);
    if (!el) {
        return;
    }
    var defaultHtml = '<i class="bi bi-clipboard"></i> Copy';
    copyTextToClipboard(el.value || '').then(function () {
        btn.innerHTML = '<i class="bi bi-check"></i> Copied';
        setTimeout(function () {
            btn.innerHTML = defaultHtml;
        }, 2000);
    }).catch(function () {
        btn.innerHTML = '<i class="bi bi-x"></i> Failed';
        setTimeout(function () {
            btn.innerHTML = defaultHtml;
        }, 2000);
        window.alert('Could not copy automatically. Select the URL in the box and press Ctrl+C (or Cmd+C).');
    });
}

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
