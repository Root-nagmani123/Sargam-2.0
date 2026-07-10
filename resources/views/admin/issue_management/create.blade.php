@extends('admin.layouts.master')

@section('title', 'Log New Issue')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<style>
/* =====================================================================
   Log New Issue — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Scoped to .issue-create-page so nothing leaks to other pages.
   ===================================================================== */

/* Let Choices dropdowns escape the card without clipping */
.issue-create-page .ds-card { overflow: visible; }

/* Section heading inside the form card (matches the detail page) */
.issue-create-page .im-section-title {
    display: flex;
    align-items: center;
    gap: var(--ds-space-2);
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--ds-ink);
    margin: var(--ds-space-5) 0 var(--ds-space-4);
    padding-bottom: var(--ds-space-2);
    border-bottom: 1px solid var(--ds-line);
}
.issue-create-page .im-section-title:first-child { margin-top: 0; }
.issue-create-page .im-section-title i { font-size: 20px; color: var(--bs-primary); }

/* Form labels */
.issue-create-page .form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ds-ink);
    margin-bottom: 0.35rem;
}
.issue-create-page .form-text { font-size: 0.8125rem; color: var(--ds-ink-muted); }

.issue-create-page .form-control,
.issue-create-page .form-select {
    border-radius: var(--ds-radius-1);
    font-size: 0.9rem;
}
.issue-create-page .form-control:focus,
.issue-create-page .form-select:focus {
    border-color: #86b7fe;
    box-shadow: var(--ds-focus-ring);
}
.issue-create-page textarea.form-control { resize: vertical; }

/* Location — segmented chip radios */
.issue-create-page .im-loc-group { display: flex; flex-wrap: wrap; gap: var(--ds-space-2); }
.issue-create-page .im-loc-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
    padding: 0.55rem 1rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    background: #fff;
    color: var(--ds-ink);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    margin: 0;
    transition: border-color .15s ease, background-color .15s ease, color .15s ease, box-shadow .15s ease;
}
.issue-create-page .im-loc-chip i { font-size: 18px; color: var(--ds-ink-muted); }
.issue-create-page .im-loc-chip:hover { border-color: #c4ccd6; background: var(--ds-surface-2); }
.issue-create-page .btn-check:checked + .im-loc-chip {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb, 0 74 147), 0.08);
    color: var(--bs-primary);
    box-shadow: inset 0 0 0 1px var(--bs-primary);
}
.issue-create-page .btn-check:checked + .im-loc-chip i { color: var(--bs-primary); }
.issue-create-page .btn-check:focus-visible + .im-loc-chip { box-shadow: var(--ds-focus-ring); }

/* Building / location detail panel */
.issue-create-page .im-panel {
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    background: var(--ds-surface-2);
    padding: var(--ds-space-4);
}
.issue-create-page .im-panel-title {
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--ds-ink-muted);
    margin-bottom: var(--ds-space-3);
}

/* Escalation hierarchy read-only panel */
.issue-create-page .im-escalation {
    border: 1px solid #cfe0f5;
    border-radius: var(--ds-radius-2);
    background: #eef5ff;
    padding: 0.9rem 1.1rem;
}
.issue-create-page .im-escalation .im-esc-row {
    display: flex;
    gap: var(--ds-space-2);
    font-size: 0.875rem;
    color: var(--ds-ink);
}
.issue-create-page .im-escalation .im-esc-row + .im-esc-row { margin-top: 0.35rem; }
.issue-create-page .im-escalation .im-esc-label { font-weight: 600; color: #0d5bbd; min-width: 62px; }

/* Footer action bar */
.issue-create-page .im-form-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--ds-space-2);
    margin-top: var(--ds-space-5);
    padding-top: var(--ds-space-4);
    border-top: 1px solid var(--ds-line);
}
.issue-create-page .im-form-footer .btn { border-radius: var(--ds-radius-1); font-weight: 600; }

/* Char counter aligns right under the textarea */
.issue-create-page .im-char-count { text-align: right; }

/* --- Choices.js aligned with Bootstrap 5 form-select / focus ring --- */
.issue-create-page .choices { margin-bottom: 0; font-size: 0.9rem; max-width: 100%; }
.issue-create-page .choices .choices__inner {
    display: inline-block;
    width: 100%; min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 0.9rem; font-weight: 400; line-height: 1.5;
    color: var(--bs-body-color);
    background-color: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: var(--ds-radius-1);
}
.issue-create-page .choices.is-focused .choices__inner,
.issue-create-page .choices.is-open .choices__inner {
    border-color: #86b7fe;
    box-shadow: var(--ds-focus-ring);
}
.issue-create-page .choices[data-type*="select-one"] .choices__inner { padding-bottom: 0.375rem; }
.issue-create-page .choices__list--single { padding: 0; }
.issue-create-page .choices__list--single .choices__item { padding: 0; }
.issue-create-page .choices[data-type*="select-one"] .choices__input {
    padding: 0.375rem 0.75rem; background-color: var(--bs-body-bg);
}
.issue-create-page .choices__list--dropdown .choices__item,
.issue-create-page .choices__list[aria-expanded] .choices__item { padding: 0.375rem 0.75rem; }
.issue-create-page .choices__list--dropdown .choices__item--selectable.is-highlighted,
.issue-create-page .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
    background-color: rgba(var(--bs-primary-rgb, 0 74 147), 0.10);
    color: var(--bs-primary);
}
.issue-create-page .choices__list--dropdown,
.issue-create-page .choices__list[aria-expanded] {
    border-color: var(--ds-line);
    border-radius: var(--ds-radius-1);
    box-shadow: var(--ds-shadow-sm);
    z-index: 1060;
}
</style>
@endpush

@section('content')
<div class="container-fluid issue-create-page">

    {{-- Page header + back action --}}
    <x-breadcrum title="Log New Issue">
        <a href="{{ route('admin.issue-management.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">arrow_back</i>
            <span>Back to Issues</span>
        </a>
    </x-breadcrum>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Success - </strong> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Error - </strong> {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Validation Error - </strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $message)
            <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="datatables">
        <div class="ds-card">
            <div class="ds-card-body p-4 p-md-4">
                <form action="{{ route('admin.issue-management.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- ── Complaint classification ─────────────────────────── --}}
                    <h6 class="im-section-title">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">category</i>
                        Complaint Classification
                    </h6>

                    <div class="row g-3 g-md-4">
                        <div class="col-md-4">
                            <label class="form-label">Complaint Category <span class="text-danger">*</span></label>
                            <select name="issue_category_id" id="issue_category" class="form-select choices-select" required>
                                <option value="">— Select category —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->pk }}" {{ old('issue_category_id') == $category->pk ? 'selected' : '' }}>{{ $category->issue_category }}</option>
                                @endforeach
                            </select>
                            @error('issue_category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Complaint Sub-Category <span class="text-danger">*</span></label>
                            <select name="issue_sub_category_id" id="sub_categories" class="form-select choices-select" required>
                                <option value="">— Select sub-category —</option>
                            </select>
                            @error('issue_sub_category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="issue_priority_id" id="issue_priority" class="form-select choices-select" required>
                                <option value="">— Select priority —</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->pk }}" {{ old('issue_priority_id') == $priority->pk ? 'selected' : '' }}>{{ $priority->priority }}</option>
                                @endforeach
                            </select>
                            @error('issue_priority_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- ── Complainant & escalation ─────────────────────────── --}}
                    <h6 class="im-section-title">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">person</i>
                        Complainant &amp; Escalation
                    </h6>

                    <div class="row g-3 g-md-4">
                        <div class="col-md-4">
                            <label class="form-label">Complainant <span class="text-danger">*</span></label>
                            <select name="created_by" id="complainant" class="form-select choices-select" required>
                                <option value="">Search complainant by name...</option>
                                @if(isset($employees))
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_pk }}" data-mobile="{{ $employee->mobile }}" {{ (old('created_by', isset($currentUserEmployeeId) ? $currentUserEmployeeId : null) == $employee->employee_pk) ? 'selected' : '' }}>{{ $employee->employee_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-text">Type to search when creating issue on behalf of others.</div>
                            @error('created_by')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" class="form-control bg-light form-control-sm" placeholder="Auto-filled" readonly id="mobile_number" name="mobile_number" aria-readonly="true">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nodal Employee (Level 1) <span class="text-danger">*</span></label>
                            <select name="nodal_employee_id" id="nodal_employee" class="form-select choices-select" required>
                                <option value="">— Select category first —</option>
                            </select>
                            <div class="form-text">Auto-selected from escalation matrix.</div>
                            @error('nodal_employee_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <input type="hidden" name="sub_category_name" id="sub_category_name" required value="{{ old('sub_category_name') }}">

                        <div id="escalation_levels_display" class="col-12 d-none">
                            <label class="form-label">Escalation Hierarchy <span class="fw-normal text-body-secondary">(read-only)</span></label>
                            <div class="im-escalation">
                                <div class="im-esc-row"><span class="im-esc-label">Level 2</span><span id="level2_display">—</span></div>
                                <div class="im-esc-row"><span class="im-esc-label">Level 3</span><span id="level3_display">—</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Issue details & location ─────────────────────────── --}}
                    <h6 class="im-section-title">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">description</i>
                        Issue Details &amp; Location
                    </h6>

                    <div class="mb-4">
                        <label class="form-label">Detail Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="5" maxlength="1000" placeholder="Enter detailed description of the issue…" required>{{ old('description') }}</textarea>
                        <div class="form-text im-char-count"><span id="char-count">0</span>/1000 characters</div>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Location <span class="text-danger">*</span></label>
                        <div class="im-loc-group pt-1">
                            <input class="btn-check" type="radio" name="location" id="loc_hostel" value="H" required {{ old('location') == 'H' ? 'checked' : '' }}>
                            <label class="im-loc-chip" for="loc_hostel"><i class="material-icons material-symbols-rounded" aria-hidden="true">apartment</i>Hostel</label>

                            <input class="btn-check" type="radio" name="location" id="loc_other" value="O" {{ old('location') == 'O' ? 'checked' : '' }}>
                            <label class="im-loc-chip" for="loc_other"><i class="material-icons material-symbols-rounded" aria-hidden="true">location_city</i>Others</label>

                            <input class="btn-check" type="radio" name="location" id="loc_residential" value="R" {{ old('location') == 'R' ? 'checked' : '' }}>
                            <label class="im-loc-chip" for="loc_residential"><i class="material-icons material-symbols-rounded" aria-hidden="true">home</i>Residential</label>
                        </div>
                        @error('location')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="building_section" class="d-none im-panel mb-4">
                        <div class="im-panel-title">Building details</div>
                        <div class="row g-3 g-md-4">
                            <div class="col-md-4">
                                <label class="form-label">Building / Hostel <span class="text-danger">*</span></label>
                                <select name="building_master_pk" id="building_select" class="form-select choices-select">
                                    <option value="">— Select —</option>
                                    @foreach($buildings as $building)
                                        <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Floor</label>
                                <select id="floor_select" class="form-select choices-select" name="floor_id">
                                    <option value="">— Select floor —</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Room / House no.</label>
                                <select name="room_name" id="room_select" class="form-select choices-select">
                                    <option value="">— Select room —</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ── Attachments ──────────────────────────────────────── --}}
                    <h6 class="im-section-title">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">attach_file</i>
                        Attachments
                    </h6>

                    <div class="row g-3 g-md-4">
                        <div class="col-md-4">
                            <label class="form-label">Attach image (optional)</label>
                            <input type="file" name="complaint_img_url[]" id="complaint_img_url" class="form-control {{ $errors->has('complaint_img_url') ? 'is-invalid' : '' }}" accept=".jpg,.jpeg,.png" multiple>
                            <div class="form-text">Max 5MB per file. JPG and PNG only.</div>
                            <div id="attachment_validation_error" class="text-danger small mt-1 d-none"></div>
                            @error('complaint_img_url')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- ── Actions ──────────────────────────────────────────── --}}
                    <div class="im-form-footer">
                        <a href="{{ route('admin.issue-management.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2" id="btn_log_issue">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">check</i>
                            Log Issue
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('components.jquery-3-6')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    var complainantMobileMap = {};

    function rebuildComplainantMobileMap() {
        complainantMobileMap = {};
        $('#complainant option').each(function() {
            var v = $(this).val();
            if (v) complainantMobileMap[v] = $(this).attr('data-mobile');
        });
    }

    function destroyIssueChoices($el) {
        var el = $el && $el[0];
        if (!el) return;
        if (el._issueChoices) {
            try { el._issueChoices.destroy(); } catch (e) {}
            el._issueChoices = null;
        }
    }

    function initIssueChoices($el, placeholder) {
        var el = $el && $el[0];
        if (!el || typeof window.Choices === 'undefined') return;
        destroyIssueChoices($el);
        el._issueChoices = new Choices(el, {
            searchEnabled: true,
            shouldSort: false,
            allowHTML: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: placeholder || '— Search / Select —',
            searchPlaceholderValue: 'Search…',
            position: 'bottom'
        });
    }

    rebuildComplainantMobileMap();
    initIssueChoices($('#issue_category'), '— Select category —');
    initIssueChoices($('#sub_categories'), '— Select sub-category —');
    initIssueChoices($('#issue_priority'), '— Select priority —');
    initIssueChoices($('#complainant'), 'Search complainant by name...');
    initIssueChoices($('#nodal_employee'), '— Select category first —');
    initIssueChoices($('#building_select'), '— Select —');
    initIssueChoices($('#floor_select'), '— Select floor —');
    initIssueChoices($('#room_select'), '— Select room —');

    // Character counter for description (max 1000)
    function updateCharCount() {
        var len = $('#description').val().length;
        $('#char-count').text(len);
    }
    $('#description').on('input keyup', updateCharCount);
    updateCharCount();

    // Load sub-categories when category changes
    $('#issue_category').change(function() {
        var categoryId = $(this).val();

        // Reset nodal employee dropdown when category changes
        destroyIssueChoices($('#nodal_employee'));
        $('#nodal_employee').html('<option value="">- Select -</option>');
        initIssueChoices($('#nodal_employee'), '— Select category first —');

        if(categoryId) {
            // Load sub-categories
            $.ajax({
                url: '/admin/issue-management/sub-categories/' + categoryId,
                type: 'GET',
                success: function(data) {
                    destroyIssueChoices($('#sub_categories'));
                    $('#sub_categories').html('<option value="">— Select sub-category —</option>');
                    $.each(data, function(key, value) {
                        $('#sub_categories').append('<option value="'+ value.pk +'">'+ value.issue_sub_category +'</option>');
                    });
                    initIssueChoices($('#sub_categories'), '— Select sub-category —');
                }
            });

            // Load nodal employees (Level 1 only) - auto-select first; Level 2 & 3 for display only
            $.ajax({
                url: '/admin/issue-management/nodal-employees/' + categoryId,
                type: 'GET',
                success: function(response) {
                    if(response.success && response.level1 && response.level1.length > 0) {
                        destroyIssueChoices($('#nodal_employee'));
                        $('#nodal_employee').html('<option value="">- Select -</option>');
                        var autoSelect = response.level1_auto_select;
                        $.each(response.level1, function(key, employee) {
                            var fullName = employee.employee_name || (employee.first_name + ' ' + (employee.middle_name ? employee.middle_name + ' ' : '') + employee.last_name);
                            var selected = (autoSelect && employee.employee_pk == autoSelect) ? 'selected' : '';
                            $('#nodal_employee').append('<option value="'+ employee.employee_pk +'" '+ selected +'>'+ fullName +'</option>');
                        });
                        initIssueChoices($('#nodal_employee'), '— Select —');
                        // Level 2 & 3 - display only
                        if(response.level2) {
                            $('#level2_display').text(response.level2.employee_name + ' (' + response.level2.days_notify + ' days)');
                        } else {
                            $('#level2_display').text('—');
                        }
                        if(response.level3) {
                            $('#level3_display').text(response.level3.employee_name + ' (' + response.level3.days_notify + ' days)');
                        } else {
                            $('#level3_display').text('—');
                        }
                        $('#escalation_levels_display').removeClass('d-none');
                    } else {
                        destroyIssueChoices($('#nodal_employee'));
                        $('#nodal_employee').html('<option value="">No Level 1 employees - configure Escalation Matrix</option>');
                        initIssueChoices($('#nodal_employee'), '— Select —');
                        $('#escalation_levels_display').addClass('d-none');
                    }
                },
                error: function() {
                    console.log('Error loading nodal employees');
                    destroyIssueChoices($('#nodal_employee'));
                    $('#nodal_employee').html('<option value="">Error loading employees</option>');
                    initIssueChoices($('#nodal_employee'), '— Select —');
                    $('#escalation_levels_display').addClass('d-none');
                }
            });
        } else {
            destroyIssueChoices($('#sub_categories'));
            destroyIssueChoices($('#nodal_employee'));
            $('#sub_categories').html('<option value="">— Select sub-category —</option>');
            $('#nodal_employee').html('<option value="">— Select category first —</option>');
            initIssueChoices($('#sub_categories'), '— Select sub-category —');
            initIssueChoices($('#nodal_employee'), '— Select category first —');
            $('#escalation_levels_display').addClass('d-none');
        }
    });

    // Auto-fill sub_category_name when sub-category is selected
    $('#sub_categories').change(function() {
        var selectedText = $(this).find('option:selected').text();
        $('#sub_category_name').val(selectedText);
    });

    // Helper to normalize mobile value (handles numbers / strings / null)
    function getNormalizedMobile(mobile) {
        if (mobile === null || mobile === undefined) return '';
        var str = String(mobile).trim();
        return str;
    }

    // Auto-fill mobile number when complainant is selected
    $('#complainant').change(function() {
        var selected = $(this).val();
        var mobile = complainantMobileMap[selected];
        var normalized = getNormalizedMobile(mobile);
        $('#mobile_number').val(!selected ? '' : (normalized ? normalized : 'Mobile number is not available'));
    });

    // Auto-fill mobile on page load if complainant is pre-selected (logged-in user)
    if ($('#complainant').val()) {
        var mobile = complainantMobileMap[$('#complainant').val()];
        var normalized = getNormalizedMobile(mobile);
        $('#mobile_number').val(normalized ? normalized : 'Mobile number is not available');
    }

    // Show/hide location sections based on location type
    $('input[name="location"]').change(function() {
        var type = $(this).val();
        $('#building_section').addClass('d-none');

        // Reset building details
        destroyIssueChoices($('#building_select'));
        destroyIssueChoices($('#floor_select'));
        destroyIssueChoices($('#room_select'));
        $('#building_select').html('<option value="">— Select —</option>');
        $('#floor_select').html('<option value="">— Select floor —</option>');
        $('#room_select').html('<option value="">— Select room —</option>');
        initIssueChoices($('#building_select'), '— Select —');
        initIssueChoices($('#floor_select'), '— Select floor —');
        initIssueChoices($('#room_select'), '— Select room —');

        if(type == 'H' || type == 'R' || type == 'O') {
            $('#building_section').removeClass('d-none');

            // Load buildings based on location type
            $.ajax({
                url: '/admin/issue-management/buildings',
                type: 'GET',
                data: { type: type },
                success: function(response) {
                    if(response.status) {
                        destroyIssueChoices($('#building_select'));
                        $('#building_select').html('<option value="">— Select —</option>');
                        $.each(response.data, function(key, value) {
                            $('#building_select').append('<option value="'+ value.pk +'">'+ value.building_name +'</option>');
                        });
                        initIssueChoices($('#building_select'), '— Select —');
                    }
                },
                error: function() {
                    console.log('Error loading buildings');
                }
            });
        }
    });

    // Load floors based on building/hostel
    $('#building_select').change(function() {
        var buildingId = $(this).val();
        var locationType = $('input[name="location"]:checked').val();

        if(buildingId) {
            $.ajax({
                url: '/admin/issue-management/floors',
                type: 'GET',
                data: { building_id: buildingId, type: locationType },
                success: function(response) {
                    if(response.status) {
                        destroyIssueChoices($('#floor_select'));
                        $('#floor_select').html('<option value="">— Select floor —</option>');
                        $.each(response.data, function(key, value) {
                            // Use ?? so 0 is preserved (|| would treat 0 as falsy and show undefined)
                            var floorId = value.floor_id ?? value.pk ?? value.estate_unit_sub_type_master_pk ?? '';
                            var floorName = value.floor_name ?? value.floor ?? value.unit_sub_type ?? '';
                            $('#floor_select').append('<option value="'+ floorId +'">'+ floorName +'</option>');
                        });
                        initIssueChoices($('#floor_select'), '— Select floor —');
                    }
                },
                error: function() {
                    console.log('Error loading floors');
                }
            });
        } else {
            destroyIssueChoices($('#floor_select'));
            destroyIssueChoices($('#room_select'));
            $('#floor_select').html('<option value="">— Select floor —</option>');
            $('#room_select').html('<option value="">— Select room —</option>');
            initIssueChoices($('#floor_select'), '— Select floor —');
            initIssueChoices($('#room_select'), '— Select room —');
        }
    });

    // Load rooms based on floor
    $('#floor_select').change(function() {
        var floorId = $(this).val();
        var buildingId = $('#building_select').val();
        var locationType = $('input[name="location"]:checked').val();

        if(floorId && buildingId) {
            $.ajax({
                url: '/admin/issue-management/rooms',
                type: 'GET',
                data: { building_id: buildingId, floor_id: floorId, type: locationType },
                success: function(response) {
                    if(response.status) {
                        destroyIssueChoices($('#room_select'));
                        $('#room_select').html('<option value="">— Select room —</option>');
                        $.each(response.data, function(key, value) {
                            // Use ?? so 0 is preserved (|| would treat 0 as falsy and show undefined)
                            var roomId = value.pk;
                            var roomName = value.room_name ?? value.house_no ?? value.floor ?? '';
                            $('#room_select').append('<option value="'+ roomName +'">'+ roomName +'</option>');
                        });
                        initIssueChoices($('#room_select'), '— Select room —');
                    }
                },
                error: function() {
                    console.log('Error loading rooms');
                }
            });
        } else {
            destroyIssueChoices($('#room_select'));
            $('#room_select').html('<option value="">— Select room —</option>');
            initIssueChoices($('#room_select'), '— Select room —');
        }
    });

    // Client-side attachment validation so form is not submitted and filled details are not cleared
    var allowedExtensions = ['jpg', 'jpeg', 'png'];
    var maxSizeBytes = 5 * 1024 * 1024; // 5MB

    $('form').on('submit', function(e) {
        var errEl = $('#attachment_validation_error');
        errEl.addClass('d-none').text('');
        $('#complaint_img_url').removeClass('is-invalid');

        var input = document.getElementById('complaint_img_url');
        var files = input && input.files ? input.files : [];
        if (files.length === 0) return true; // no attachments, allow submit

        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var ext = (f.name.split('.').pop() || '').toLowerCase();
            if (allowedExtensions.indexOf(ext) === -1) {
                e.preventDefault();
                errEl.text('Only JPG and PNG images are allowed. File "' + f.name + '" is not allowed.').removeClass('d-none');
                $('#complaint_img_url').addClass('is-invalid');
                return false;
            }
            if (f.size > maxSizeBytes) {
                e.preventDefault();
                errEl.text('Each file must not exceed 5MB. File "' + f.name + '" is too large.').removeClass('d-none');
                $('#complaint_img_url').addClass('is-invalid');
                return false;
            }
        }
        return true;
    });

    // Clear attachment error when user changes file selection
    $('#complaint_img_url').on('change', function() {
        $('#attachment_validation_error').addClass('d-none').text('');
        $(this).removeClass('is-invalid');
    });

    // Restore form state after validation error (old input)
    var oldCategory = {!! json_encode(old('issue_category_id')) !!};
    var oldSubCategory = {!! json_encode(old('issue_sub_category_id')) !!};
    var oldLocation = {!! json_encode(old('location')) !!};
    var oldBuilding = {!! json_encode(old('building_master_pk')) !!};
    var oldNodal = {!! json_encode(old('nodal_employee_id')) !!};
    if (oldCategory) {
        $('#issue_category').val(oldCategory).trigger('change');
        if (oldSubCategory) {
            setTimeout(function() {
                $.ajax({
                    url: '/admin/issue-management/sub-categories/' + oldCategory,
                    type: 'GET',
                    success: function(data) {
                        destroyIssueChoices($('#sub_categories'));
                        $('#sub_categories').html('<option value="">— Select sub-category —</option>');
                        $.each(data, function(k, v) {
                            $('#sub_categories').append(new Option(v.issue_sub_category, v.pk, v.pk == oldSubCategory, v.pk == oldSubCategory));
                        });
                        initIssueChoices($('#sub_categories'), '— Select sub-category —');
                        var selText = $('#sub_categories option:selected').text();
                        if (selText) $('#sub_category_name').val(selText);
                    }
                });
            }, 200);
        }
        if (oldNodal) {
            setTimeout(function() {
                $.ajax({
                    url: '/admin/issue-management/nodal-employees/' + oldCategory,
                    type: 'GET',
                    success: function(res) {
                        if (res.success && res.level1) {
                            destroyIssueChoices($('#nodal_employee'));
                            $('#nodal_employee').html('<option value="">- Select -</option>');
                            $.each(res.level1, function(k, emp) {
                                var pk = emp.employee_pk;
                                var name = emp.employee_name || ((emp.first_name || '') + ' ' + (emp.middle_name ? emp.middle_name + ' ' : '') + (emp.last_name || ''));
                                $('#nodal_employee').append(new Option(name, pk, pk == oldNodal, pk == oldNodal));
                            });
                            initIssueChoices($('#nodal_employee'), '— Select —');
                        }
                    }
                });
            }, 400);
        }
    }
    if (oldLocation) {
        $('input[name="location"][value="' + oldLocation + '"]').prop('checked', true);
        $('#building_section').removeClass('d-none');
        if (oldBuilding) {
            $.ajax({
                url: '/admin/issue-management/buildings',
                type: 'GET',
                data: { type: oldLocation },
                success: function(response) {
                    if (response.status && response.data) {
                        destroyIssueChoices($('#building_select'));
                        $('#building_select').html('<option value="">— Select —</option>');
                        $.each(response.data, function(k, v) {
                            $('#building_select').append(new Option(v.building_name, v.pk, v.pk == oldBuilding, v.pk == oldBuilding));
                        });
                        initIssueChoices($('#building_select'), '— Select —');
                    }
                }
            });
        }
    }

});
</script>
@endpush
