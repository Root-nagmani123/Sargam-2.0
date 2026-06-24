{{-- Reusable field form partial for Add/Edit modals --}}
@php
    $selectedLayout = \App\Models\FC\FcFormField::normalizeColumnLayout($field->css_class ?? 'col-md-6');
    $layoutSelectId = ($prefix ?? 'field') . '_column_layout';
    $validationColClass = ($showTargetTable ?? true) ? 'col-md-8' : 'col-md-12';
@endphp
<div class="row g-3 fc-field-form">
    <div class="col-12">
        <p class="small text-uppercase fw-semibold text-muted mb-0 pb-2 border-bottom">Basic settings</p>
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Field Name (DB Column) <span class="text-danger">*</span></label>
        <input type="text" name="field_name" class="form-control form-control-sm field-name-input" value="{{ isset($field) && $field ? $field->field_name : '' }}" required placeholder="e.g. emergency_contact_phone" autocomplete="off">
        <small class="text-muted">Internal name stored in the database. Use lowercase words separated by underscores (e.g. first_name). A new column is added to the table if needed.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Label <span class="text-danger">*</span></label>
        <input type="text" name="label" class="form-control form-control-sm" value="{{ $field->label ?? '' }}" required placeholder="e.g. Full Name">
        <small class="text-muted">Text shown to the person filling the form.</small>
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Field Type <span class="text-danger">*</span></label>
        <select name="field_type" class="form-select form-select-sm fc-field-type-select" required>
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
    <input type="hidden" name="target_column" class="target-column-sync" value="{{ $field->target_column ?? '' }}">
    <div class="{{ $validationColClass }}">
        <label class="form-label small fw-semibold">Validation Rules</label>
        <input type="text" name="validation_rules" class="form-control form-control-sm" value="{{ $field->validation_rules ?? '' }}" placeholder="e.g. required|string|max:200">
        <small class="text-muted">Optional. Laravel-style rules (e.g. required, email, max length).</small>
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="{{ $layoutSelectId }}">Field width</label>
        <select name="css_class" id="{{ $layoutSelectId }}" class="form-select form-select-sm" required>
            @foreach(\App\Models\FC\FcFormField::columnLayoutOptions() as $layoutValue => $layoutLabel)
                <option value="{{ $layoutValue }}" @selected($selectedLayout === $layoutValue)>{{ $layoutLabel }}</option>
            @endforeach
        </select>
        <small class="text-muted">How much horizontal space this field uses on the form row.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Placeholder</label>
        <input type="text" name="placeholder" class="form-control form-control-sm" value="{{ $field->placeholder ?? '' }}" placeholder="Hint inside the input box">
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Section Heading</label>
        <input type="text" name="section_heading" class="form-control form-control-sm" value="{{ $field->section_heading ?? '' }}" placeholder="Groups fields under this heading">
    </div>
  @if($showTargetTable ?? true)
    <div class="col-md-12">
        <label class="form-label small fw-semibold">Help Text</label>
        <input type="text" name="help_text" class="form-control form-control-sm" value="{{ $field->help_text ?? '' }}" placeholder="Small text below the field">
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Default Value</label>
        <input type="text" name="default_value" class="form-control form-control-sm" value="{{ $field->default_value ?? '' }}">
    </div>
  @endif

    @php
        $fcChoiceSource = (isset($field) && $field && ! empty($field->lookup_table)) ? 'lookup' : 'fixed';
        $fcChoiceSourcePrefix = $prefix ?? 'field';
    @endphp

    {{-- Step 1: how choices are provided (dropdown only) --}}
    <div class="col-12 fc-field-form-section d-none" data-fc-field-section="choice-picker">
        <div class="border rounded-3 p-3 bg-body-tertiary">
            <h6 class="small fw-semibold mb-2">How should choices be provided?</h6>
            <div class="d-flex flex-column gap-2">
                <div class="form-check mb-0">
                    <input class="form-check-input fc-choice-source-input" type="radio"
                        name="fc_choice_source_{{ $fcChoiceSourcePrefix }}" id="fc_choice_source_fixed_{{ $fcChoiceSourcePrefix }}"
                        value="fixed" @checked($fcChoiceSource === 'fixed')>
                    <label class="form-check-label small" for="fc_choice_source_fixed_{{ $fcChoiceSourcePrefix }}">
                        Enter options manually (comma-separated list)
                    </label>
                </div>
                <div class="form-check mb-0">
                    <input class="form-check-input fc-choice-source-input" type="radio"
                        name="fc_choice_source_{{ $fcChoiceSourcePrefix }}" id="fc_choice_source_lookup_{{ $fcChoiceSourcePrefix }}"
                        value="lookup" @checked($fcChoiceSource === 'lookup')>
                    <label class="form-check-label small" for="fc_choice_source_lookup_{{ $fcChoiceSourcePrefix }}">
                        Load options from another database table
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 2a: fixed choices (select / radio / checkbox) --}}
    <div class="col-12 fc-field-form-section d-none" data-fc-field-section="choice-static">
        <div class="border rounded-3 p-3 bg-body-tertiary">
            <h6 class="small fw-semibold mb-1">Option list</h6>
            <p class="small text-muted mb-2 mb-md-3">Type the choices you want users to pick from.</p>
            <label class="form-label small fw-semibold">Options</label>
            <input type="text"
                class="form-control form-control-sm fc-options-list-input"
                value="{{ isset($field) && $field ? \App\Models\FC\FcFormField::optionsJsonToCommaList($field->options_json ?? null) : '' }}"
                placeholder="Male, Female, Other"
                autocomplete="off">
            <input type="hidden" name="options_json" class="fc-options-json-input" value="{{ isset($field) && $field ? ($field->options_json ?? '') : '' }}">
            <small class="text-muted">Enter choices separated by commas. Each option is saved for use in dropdown, radio, or checkbox groups.</small>
        </div>
    </div>

    {{-- Database lookup: select only --}}
    <div class="col-12 fc-field-form-section d-none" data-fc-field-section="choice-lookup">
        <div class="border rounded-3 p-3 bg-body-tertiary">
            <h6 class="small fw-semibold mb-1">Database lookup</h6>
            <p class="small text-muted mb-2 mb-md-3">Tell the form which table and columns to load dropdown options from.</p>
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label small fw-semibold">Lookup Table</label>
                    <input type="text" name="lookup_table" class="form-control form-control-sm" value="{{ $field->lookup_table ?? '' }}" placeholder="e.g. service_masters">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label small fw-semibold">Value Column</label>
                    <input type="text" name="lookup_value_column" class="form-control form-control-sm" value="{{ $field->lookup_value_column ?? '' }}" placeholder="id">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label small fw-semibold">Label Column</label>
                    <input type="text" name="lookup_label_column" class="form-control form-control-sm" value="{{ $field->lookup_label_column ?? '' }}" placeholder="name">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label small fw-semibold">Order Column</label>
                    <input type="text" name="lookup_order_column" class="form-control form-control-sm" value="{{ $field->lookup_order_column ?? '' }}" placeholder="name">
                </div>
            </div>
        </div>
    </div>

    {{-- File upload settings --}}
    <div class="col-12 fc-field-form-section d-none" data-fc-field-section="file">
        <div class="border rounded-3 p-3 bg-body-tertiary">
            <h6 class="small fw-semibold mb-1">File upload settings</h6>
            <p class="small text-muted mb-2 mb-md-3">Only applies when the field type is <strong>File</strong>.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Max File Size (KB)</label>
                    <input type="number" name="file_max_kb" class="form-control form-control-sm" value="{{ $field->file_max_kb ?? '' }}" placeholder="500" min="1">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Allowed extensions</label>
                    <input type="text" name="file_extensions" class="form-control form-control-sm" value="{{ $field->file_extensions ?? '' }}" placeholder="jpeg,jpg,png,pdf">
                    <small class="text-muted">Comma-separated, without dots (e.g. jpeg,png,pdf).</small>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Fillable Form Template</label>
                    <select name="form_template" class="form-select form-select-sm">
                        @foreach(\App\Support\FC\DocumentFormTemplates::options() as $tplKey => $tplLabel)
                            <option value="{{ $tplKey }}" {{ (($field->form_template ?? '') === $tplKey) ? 'selected' : '' }}>{{ $tplLabel }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Choose a template to let candidates <strong>fill this document online</strong> (a PDF is generated) instead of uploading a file.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 pt-2 border-top">
        <p class="small text-uppercase fw-semibold text-muted mb-2">Status</p>
        <div class="d-flex flex-wrap gap-4">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="is_required" value="1" id="{{ ($prefix ?? 'field') }}_is_required" {{ ($field->is_required ?? false) ? 'checked' : '' }}>
                <label class="form-check-label small" for="{{ ($prefix ?? 'field') }}_is_required">Required</label>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ ($prefix ?? 'field') }}_is_active" {{ ($field->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label small" for="{{ ($prefix ?? 'field') }}_is_active">Active</label>
            </div>
        </div>
    </div>
</div>
