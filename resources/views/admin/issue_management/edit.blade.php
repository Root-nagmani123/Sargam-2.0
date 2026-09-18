@extends('admin.layouts.master')

@section('title', 'Edit Issue')

@section('setup_content')
{{-- Same shell as Log New Issue (create.blade.php): `issue-log-choices` scopes the
     Choices.js overrides in @push('styles') below, `ic-page` adds the Centcom chrome. --}}
<div class="container-fluid ic-page issue-log-choices">
    <x-breadcrum title="Edit Issue" />

    <x-session_message />

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Validation Error —</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php
        // Assigned = the status history carries an assignee. The two selects below
        // are marked readonly when that happens; note that `readonly` is a no-op on
        // a <select>, so this has never actually locked them — kept as-is rather
        // than silently changing who may re-categorise an assigned issue.
        $isAssigned = $issue->statusHistory && $issue->statusHistory->whereNotNull('assign_to')->count() > 0;

        // Current building / floor / room, by location type.
        $currentBuilding = null;
        $currentFloor = null;
        $currentRoom = null;
        if ($issue->location === 'O' && $issue->buildingMapping) {
            $currentBuilding = $issue->buildingMapping->building_master_pk;
            $currentFloor = $issue->buildingMapping->floor_name;
            $currentRoom = $issue->buildingMapping->room_name;
        } elseif ($issue->hostelMapping) {
            $currentBuilding = $issue->hostelMapping->hostel_building_master_pk;
            $currentFloor = $issue->hostelMapping->floor_name;
            $currentRoom = $issue->hostelMapping->room_name;
        }

        $currentSubCategory = $issue->subCategoryMappings->first()->issue_sub_category_master_pk ?? '';
    @endphp

    <div class="ic-card">
        <div class="ic-card__body p-3 p-md-4">
            <h6 class="fw-semibold mb-3">Issue #{{ $issue->pk }}</h6>

            <form action="{{ route('admin.issue-management.update', $issue->pk) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @if(request('embed'))
                    <input type="hidden" name="from_modal" value="1">
                @endif

                <div class="ic-form-grid">
                    <div>
                        <label for="issue_category" class="ic-field-label">Complaint Category<span class="ic-req">*</span></label>
                        <select name="issue_category_id" id="issue_category" class="form-select ic-input choices-select"
                                {{ $isAssigned ? 'readonly' : '' }} required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}"
                                    {{ old('issue_category_id', $issue->issue_category_master_pk) == $category->pk ? 'selected' : '' }}>
                                    {{ $category->issue_category }}
                                </option>
                            @endforeach
                        </select>
                        @error('issue_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="sub_categories" class="ic-field-label">Complaint Sub-Category<span class="ic-req">*</span></label>
                        {{-- Repopulated over ajax when the category changes; seeded here so the
                             current value survives a validation error round-trip. --}}
                        <select name="issue_sub_category_id" id="sub_categories" class="form-select ic-input choices-select" required>
                            <option value="">Select Sub-Category</option>
                            @foreach($issue->subCategoryMappings as $mapping)
                                <option value="{{ $mapping->issue_sub_category_master_pk }}" selected>
                                    {{ $mapping->subCategory->issue_sub_category ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('issue_sub_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="issue_priority" class="ic-field-label">Priority<span class="ic-req">*</span></label>
                        <select name="issue_priority_id" id="issue_priority" class="form-select ic-input choices-select"
                                {{ $isAssigned ? 'readonly' : '' }} required>
                            <option value="">Select Priority</option>
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->pk }}"
                                    {{ old('issue_priority_id', $issue->issue_priority_master_pk) == $priority->pk ? 'selected' : '' }}>
                                    {{ $priority->priority }}
                                </option>
                            @endforeach
                        </select>
                        @error('issue_priority_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="complainant" class="ic-field-label">Complainant<span class="ic-req">*</span></label>
                        <select name="created_by" id="complainant" class="form-select ic-input choices-select" required>
                            <option value="">Select Complainant</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_pk }}" data-mobile="{{ $employee->mobile }}"
                                    {{ old('created_by', $issue->created_by) == $employee->employee_pk ? 'selected' : '' }}>
                                    {{ $employee->employee_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">Type to search when editing on behalf of others.</div>
                        @error('created_by')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="mobile_number" class="ic-field-label">Mobile Number</label>
                        <input type="text" class="form-control ic-input" placeholder="Auto-filled" readonly
                               id="mobile_number" name="mobile_number" aria-readonly="true"
                               value="{{ $issue->creator->mobile ?? '' }}">
                    </div>

                    <div>
                        <label for="nodal_employee" class="ic-field-label">Nodal Employee (Level I)<span class="ic-req">*</span></label>
                        <select name="nodal_employee_id" id="nodal_employee" class="form-select ic-input choices-select" required>
                            <option value="">Select Category first</option>
                            @if($issue->employee_master_pk)
                                <option value="{{ $issue->employee_master_pk }}" selected>
                                    {{ $issue->nodal_officer->name ?? 'Current employee' }}
                                </option>
                            @endif
                        </select>
                        <div class="form-text text-muted">Auto-selected from the escalation matrix.</div>
                        @error('nodal_employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="description" class="ic-field-label">Description<span class="ic-req">*</span></label>
                        <textarea name="description" id="description" class="form-control ic-input" rows="3"
                                  maxlength="1000"
                                  placeholder="Describe the problem — what is wrong, where, and since when"
                                  required>{{ old('description', $issue->description) }}</textarea>
                        <div class="form-text text-muted"><span id="char-count">0</span>/1000 characters</div>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="ic-field-label">Location<span class="ic-req">*</span></label>
                        <div class="ic-radio-row">
                            @foreach(['H' => 'Hostel', 'O' => 'Other', 'R' => 'Residential'] as $value => $label)
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="location"
                                           id="loc_{{ strtolower($label) }}" value="{{ $value }}" required
                                           {{ old('location', $issue->location) === $value ? 'checked' : '' }}>
                                    <label class="form-check-label" for="loc_{{ strtolower($label) }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('location')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Populated by the escalation-matrix lookup; hidden until a category is chosen. --}}
                    <div id="escalation_levels_display" class="ic-form-grid--full d-none">
                        <label class="ic-field-label">Escalation Hierarchy (read-only)</label>
                        <div class="ic-field-card">
                            <div class="mb-1 small"><strong>Level 2:</strong> <span id="level2_display">—</span></div>
                            <div class="small"><strong>Level 3:</strong> <span id="level3_display">—</span></div>
                        </div>
                    </div>

                    {{-- Revealed by the Location radios. Field names are the ones update() reads. --}}
                    <div id="building_section" class="ic-form-grid--full {{ $issue->location ? '' : 'd-none' }}">
                        <div class="ic-field-card">
                            <label class="ic-field-label">Building details</label>
                            <div class="ic-form-grid">
                                <div>
                                    <label for="building_select" class="ic-field-label">Building / Hostel</label>
                                    <select name="building_select" id="building_select" class="form-select ic-input choices-select">
                                        <option value="">— Select —</option>
                                        @if($currentBuilding)
                                            <option value="{{ $currentBuilding }}" selected>Current building</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label for="floor_select" class="ic-field-label">Floor</label>
                                    <select id="floor_select" class="form-select ic-input choices-select" name="floor_select">
                                        <option value="">— Select floor —</option>
                                        @if($currentFloor)
                                            <option value="{{ $currentFloor }}" selected>{{ $currentFloor }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label for="room_select" class="ic-field-label">Room / House no.</label>
                                    <select name="room_select" id="room_select" class="form-select ic-input choices-select">
                                        <option value="">— Select room —</option>
                                        @if($currentRoom)
                                            <option value="{{ $currentRoom }}" selected>{{ $currentRoom }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="sub_category_name" id="sub_category_name"
                       value="{{ old('sub_category_name', $issue->subCategoryMappings->first()->sub_category_name ?? '') }}">

                @if((int) $issue->issue_status === 0)
                    <div class="ic-form-footer">
                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="btn ic-btn-cancel">Cancel</a>
                        <button type="submit" class="btn ic-btn-submit" id="btn_update_issue">Update Issue</button>
                    </div>
                @else
                    <div class="alert alert-info mt-4 mb-0">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        This issue cannot be edited as its status is not “Open”.
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<style>
    /* Let dropdowns escape card overflow */
    .issue-log-choices .card.overflow-hidden { overflow: visible; }
    /* Choices.js aligned with Bootstrap 5 form-select / focus ring */
    .issue-log-choices .choices { margin-bottom: 0; font-size: 1rem; max-width: 100%; }
    .issue-log-choices .choices .choices__inner {
        display: inline-block;
        width: 100%; min-height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        font-size: 1rem; font-weight: 400; line-height: 1.5;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    .issue-log-choices .choices.is-focused .choices__inner,
    .issue-log-choices .choices.is-open .choices__inner {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }
    .issue-log-choices .choices[data-type*="select-one"] .choices__inner { padding-bottom: 0.375rem; }
    .issue-log-choices .choices__list--single { padding: 0; }
    .issue-log-choices .choices__list--single .choices__item { padding: 0; }
    .issue-log-choices .choices[data-type*="select-one"] .choices__input {
        padding: 0.375rem 0.75rem; background-color: var(--bs-body-bg);
    }
    .issue-log-choices .choices__list--dropdown .choices__item,
    .issue-log-choices .choices__list[aria-expanded] .choices__item { padding: 0.375rem 0.75rem; }
    .issue-log-choices .choices__list--dropdown .choices__item--selectable.is-highlighted,
    .issue-log-choices .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
        background-color: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
    }
    .issue-log-choices .choices__list--dropdown,
    .issue-log-choices .choices__list[aria-expanded] {
        border-color: var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        box-shadow: var(--bs-box-shadow);
        z-index: 1060;
    }
</style>
@endpush

@section('scripts')
@include('components.jquery-3-6')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
/* jQuery(function ($) { … }) pins $ to the instance that binds the handlers.
   The page ends up with two jQuery copies (the layout's and the one included
   above), and a bare `$` inside an ajax callback can resolve to the other one
   by the time the callback runs — a .trigger('change') from there then reaches
   nobody. Hence also: the prefill chain below calls its loaders directly
   instead of firing change events at them. */
jQuery(function ($) {
    'use strict';

    // The issue as it stands. Consumed once, by the prefill chain: after the
    // user touches a control, their choice wins over the stored value.
    var CURRENT = {
        subCategory: @json((string) $currentSubCategory),
        nodal: @json((string) ($issue->employee_master_pk ?? '')),
        building: @json((string) ($currentBuilding ?? '')),
        floor: @json((string) ($currentFloor ?? '')),
        room: @json((string) ($currentRoom ?? ''))
    };

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
            // true, not false: labels arrive as DOM option innerHTML, which the
            // browser has already escaped — escaping again turns "A & B" into
            // "A &amp; B" on screen. Every option here is built with
            // new Option(), so nothing reaches this as raw markup.
            allowHTML: true,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: placeholder || '— Search / Select —',
            searchPlaceholderValue: 'Search…',
            position: 'bottom'
        });
    }

    // Refill a select from ajax rows and re-wrap it in Choices, keeping `selected`.
    function refill($el, placeholder, emptyLabel, rows, build) {
        destroyIssueChoices($el);
        $el.html('<option value="">' + emptyLabel + '</option>');
        $.each(rows || [], function(i, row) {
            var opt = build(row);
            if (opt) { $el.append(opt); }
        });
        initIssueChoices($el, placeholder);
    }

    // Endpoints return either {status, data} or a bare array — accept both.
    function rowsOf(response) {
        if (Array.isArray(response)) return response;
        return (response && response.data) || [];
    }

    /* Some issues point at a building / floor / room the current masters no
       longer offer (renamed blocks, rows never migrated). Without this the
       field would quietly fall back to the placeholder and saving the issue
       would wipe a location nobody meant to change — so the stored value is
       re-added as its own option, labelled so it is clearly not a live choice. */
    function keepUnmatched($el, stored, label) {
        if (stored === '' || $el.val()) { return; }
        var el = $el[0];
        var choices = el._issueChoices;
        if (choices) {
            choices.setChoices([{ value: String(stored), label: label, selected: true }], 'value', 'label', false);
            choices.setChoiceByValue(String(stored));
        } else {
            $el.append(new Option(label, stored, true, true));
        }
    }

    function locationType() {
        return $('input[name="location"]:checked').val() || '';
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
        $('#char-count').text($('#description').val().length);
    }
    $('#description').on('input keyup', updateCharCount);
    updateCharCount();

    /* ── Category → sub-categories + nodal employees ─────────────────────────
       `prefill` re-selects the issue's stored values; a user-driven change
       passes false and starts from the endpoint's own default. ── */
    function loadCategoryDependants(categoryId, prefill) {
        if (!categoryId) {
            refill($('#sub_categories'), '— Select sub-category —', '— Select sub-category —', [], function() {});
            refill($('#nodal_employee'), '— Select category first —', '— Select category first —', [], function() {});
            $('#escalation_levels_display').addClass('d-none');
            return;
        }

        $.ajax({
            url: '/admin/issue-management/sub-categories/' + categoryId,
            type: 'GET',
            success: function(response) {
                refill($('#sub_categories'), '— Select sub-category —', 'Select Sub-Category', rowsOf(response), function(row) {
                    var keep = prefill && row.pk == CURRENT.subCategory;
                    return new Option(row.issue_sub_category, row.pk, keep, keep);
                });
                var selected = $('#sub_categories option:selected').text();
                $('#sub_category_name').val(selected || '');
            }
        });

        $.ajax({
            url: '/admin/issue-management/nodal-employees/' + categoryId,
            type: 'GET',
            success: function(response) {
                var level1 = response.level1 || response.data || [];
                var autoSelect = response.level1_auto_select;

                refill($('#nodal_employee'), '— Select —', 'Select Nodal Employee', level1, function(emp) {
                    var pk = emp.employee_pk || emp.pk;
                    var name = emp.employee_name ||
                        ((emp.first_name || '') + ' ' + (emp.middle_name ? emp.middle_name + ' ' : '') + (emp.last_name || ''));
                    // Keep the issue's own nodal officer; fall back to the matrix default.
                    var keep = (prefill && CURRENT.nodal) ? pk == CURRENT.nodal : (autoSelect && pk == autoSelect);
                    return new Option(name, pk, keep, keep);
                });

                if (level1.length) {
                    $('#level2_display').text(response.level2
                        ? response.level2.employee_name + ' (' + response.level2.days_notify + ' days)' : '—');
                    $('#level3_display').text(response.level3
                        ? response.level3.employee_name + ' (' + response.level3.days_notify + ' days)' : '—');
                    $('#escalation_levels_display').removeClass('d-none');
                } else {
                    $('#escalation_levels_display').addClass('d-none');
                }
            },
            error: function() {
                refill($('#nodal_employee'), '— Select —', 'Error loading employees', [], function() {});
                $('#escalation_levels_display').addClass('d-none');
            }
        });
    }

    /* ── Location → building → floor → room ─────────────────────────────────
       Each step calls the next one itself once the prefilled value is in
       place, so the whole stored location survives a page load. ── */
    function loadBuildings(type, prefill) {
        refill($('#floor_select'), '— Select floor —', '— Select floor —', [], function() {});
        refill($('#room_select'), '— Select room —', '— Select room —', [], function() {});
        $('#building_section').removeClass('d-none');

        if (!type) { return; }

        $.ajax({
            url: '/admin/issue-management/buildings',
            type: 'GET',
            data: { type: type },
            success: function(response) {
                refill($('#building_select'), '— Select —', '— Select —', rowsOf(response), function(row) {
                    var name = row.building_name || row.hostel_building_name || row.block_name;
                    var keep = prefill && CURRENT.building && row.pk == CURRENT.building;
                    return new Option(name, row.pk, keep, keep);
                });

                if (prefill) {
                    keepUnmatched($('#building_select'), CURRENT.building, 'Current building (not in the list)');
                }
                var chosen = $('#building_select').val();
                if (prefill && chosen) { loadFloors(chosen, type, true); }
            }
        });
    }

    function loadFloors(buildingId, type, prefill) {
        refill($('#room_select'), '— Select room —', '— Select room —', [], function() {});

        if (!buildingId || !type) {
            refill($('#floor_select'), '— Select floor —', '— Select floor —', [], function() {});
            return;
        }

        $.ajax({
            url: '/admin/issue-management/floors',
            type: 'GET',
            data: { building_id: buildingId, type: type },
            success: function(response) {
                refill($('#floor_select'), '— Select floor —', '— Select floor —', rowsOf(response), function(row) {
                    // ?? so a 0 id survives (|| would treat it as missing). Residential
                    // floors carry their id as estate_unit_sub_type_master_pk.
                    var floorId = row.floor_id ?? row.pk ?? row.estate_unit_sub_type_master_pk ?? '';
                    var floorName = row.floor_name ?? row.floor ?? row.unit_sub_type ?? '';
                    var keep = prefill && CURRENT.floor !== '' && String(floorId) === String(CURRENT.floor);
                    return new Option(floorName, floorId, keep, keep);
                });

                if (prefill) {
                    keepUnmatched($('#floor_select'), CURRENT.floor, 'Current floor (not in the list)');
                }
                var chosen = $('#floor_select').val();
                if (prefill && chosen) { loadRooms(chosen, buildingId, type, true); }
            }
        });
    }

    function loadRooms(floorId, buildingId, type, prefill) {
        if (!floorId || !buildingId || !type) {
            refill($('#room_select'), '— Select room —', '— Select room —', [], function() {});
            return;
        }

        $.ajax({
            url: '/admin/issue-management/rooms',
            type: 'GET',
            data: { floor_id: floorId, building_id: buildingId, type: type },
            success: function(response) {
                refill($('#room_select'), '— Select room —', '— Select room —', rowsOf(response), function(row) {
                    // update() stores the room NAME, so that is the option value.
                    var roomName = row.room_name ?? row.room_no ?? row.house_no ?? '';
                    if (!roomName) { return null; }
                    var keep = prefill && CURRENT.room !== '' && String(roomName) === String(CURRENT.room);
                    return new Option(roomName, roomName, keep, keep);
                });

                if (prefill) {
                    keepUnmatched($('#room_select'), CURRENT.room, CURRENT.room + ' (not in the list)');
                }
            }
        });
    }

    /* ── Handlers: a user change always starts fresh (prefill = false) ─────── */
    $('#issue_category').on('change', function() {
        loadCategoryDependants($(this).val(), false);
    });

    // update() stores the sub-category label alongside its id.
    $('#sub_categories').on('change', function() {
        $('#sub_category_name').val($(this).find('option:selected').text());
    });

    $('input[name="location"]').on('change', function() {
        loadBuildings($(this).val(), false);
    });

    $('#building_select').on('change', function() {
        loadFloors($(this).val(), locationType(), false);
    });

    $('#floor_select').on('change', function() {
        loadRooms($(this).val(), $('#building_select').val(), locationType(), false);
    });

    /* ── Complainant → mobile ──────────────────────────────────────────────── */
    function getNormalizedMobile(mobile) {
        if (mobile === null || mobile === undefined) return '';
        return String(mobile).trim();
    }

    function syncMobile() {
        var selected = $('#complainant').val();
        var normalized = getNormalizedMobile(complainantMobileMap[selected]);
        $('#mobile_number').val(!selected ? '' : (normalized ? normalized : 'Mobile number is not available'));
    }
    $('#complainant').on('change', syncMobile);
    if ($('#complainant').val()) { syncMobile(); }

    /* ── First paint: replay both cascades with the stored values ─────────── */
    if ($('#issue_category').val()) {
        loadCategoryDependants($('#issue_category').val(), true);
    }
    if (locationType()) {
        loadBuildings(locationType(), true);
    }
});
</script>
@endsection
