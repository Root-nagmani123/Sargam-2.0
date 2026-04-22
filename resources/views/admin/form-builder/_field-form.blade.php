{{-- Reusable field form partial for Add/Edit modals --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Field Name (DB Column) <span class="text-danger">*</span></label>
        <select name="field_name" class="form-select form-select-sm field-name-select2" required>
            <option value="">-- Select column --</option>
            @if(isset($field) && $field && $field->field_name)
                <option value="{{ $field->field_name }}" selected>{{ $field->field_name }}</option>
            @endif
        </select>
        <small class="text-muted">Select from columns of the step's target table</small>
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Label <span class="text-danger">*</span></label>
        <input type="text" name="label" class="form-control form-control-sm" value="{{ $field->label ?? '' }}" required placeholder="e.g. Full Name">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Field Type <span class="text-danger">*</span></label>
        <select name="field_type" class="form-select form-select-sm" required>
            @foreach(['text','number','email','date','select','radio','checkbox','textarea','file','hidden'] as $type)
                <option value="{{ $type }}" {{ ($field->field_type ?? 'text') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    @if($showTargetTable ?? true)
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Target Table</label>
        <input type="text" name="target_table" class="form-control form-control-sm" value="{{ $field->target_table ?? '' }}" placeholder="Leave blank for step default">
    </div>
    @endif
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Target Column <span class="text-danger">*</span></label>
        <input type="text" name="target_column" class="form-control form-control-sm target-column-input" value="{{ $field->target_column ?? '' }}" required placeholder="Auto-filled from field name">
        <small class="text-muted">Auto-set from selected field name (editable)</small>
    </div>
    <div class="col-md-8">
        <label class="form-label small fw-semibold">Validation Rules</label>
        <input type="text" name="validation_rules" class="form-control form-control-sm" value="{{ $field->validation_rules ?? '' }}" placeholder="e.g. required|string|max:200">
        <small class="text-muted">Laravel validation syntax</small>
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">CSS Class</label>
        <input type="text" name="css_class" class="form-control form-control-sm" value="{{ $field->css_class ?? 'col-md-6' }}" placeholder="col-md-6">
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Placeholder</label>
        <input type="text" name="placeholder" class="form-control form-control-sm" value="{{ $field->placeholder ?? '' }}">
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Section Heading</label>
        <input type="text" name="section_heading" class="form-control form-control-sm" value="{{ $field->section_heading ?? '' }}" placeholder="Groups fields under this heading">
    </div>
    <div class="col-md-12">
        <label class="form-label small fw-semibold">Help Text</label>
        <input type="text" name="help_text" class="form-control form-control-sm" value="{{ $field->help_text ?? '' }}" placeholder="Small text below the field">
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Default Value</label>
        <input type="text" name="default_value" class="form-control form-control-sm" value="{{ $field->default_value ?? '' }}">
    </div>

    {{-- Select/Radio options --}}
    <div class="col-md-12">
        <label class="form-label small fw-semibold">Options JSON <small class="text-muted">(for select/radio)</small></label>
        <textarea name="options_json" class="form-control form-control-sm" rows="2" placeholder='[{"value":"Male","label":"Male"},{"value":"Female","label":"Female"}]'>{{ $field->options_json ?? '' }}</textarea>
    </div>

    {{-- Lookup table (dynamic selects) --}}
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Lookup Table</label>
        <input type="text" name="lookup_table" class="form-control form-control-sm" value="{{ $field->lookup_table ?? '' }}" placeholder="e.g. service_masters">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Value Column</label>
        <input type="text" name="lookup_value_column" class="form-control form-control-sm" value="{{ $field->lookup_value_column ?? '' }}" placeholder="id">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Label Column</label>
        <input type="text" name="lookup_label_column" class="form-control form-control-sm" value="{{ $field->lookup_label_column ?? '' }}" placeholder="name">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Order Column</label>
        <input type="text" name="lookup_order_column" class="form-control form-control-sm" value="{{ $field->lookup_order_column ?? '' }}" placeholder="name">
    </div>

    {{-- File field options --}}
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Max File Size (KB)</label>
        <input type="number" name="file_max_kb" class="form-control form-control-sm" value="{{ $field->file_max_kb ?? '' }}" placeholder="500">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">File Extensions</label>
        <input type="text" name="file_extensions" class="form-control form-control-sm" value="{{ $field->file_extensions ?? '' }}" placeholder="jpeg,jpg,png,pdf">
    </div>

    {{-- Toggles --}}
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_required" value="1" {{ ($field->is_required ?? false) ? 'checked' : '' }}>
            <label class="form-check-label small">Required</label>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ ($field->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label small">Active</label>
        </div>
    </div>
</div>
