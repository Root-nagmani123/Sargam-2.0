@extends('admin.layouts.master')

@section('title', 'Log New Issue - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Log New Issue" />
    <div class="datatables">
        <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3 overflow-hidden">
            <div class="card-body p-4 p-md-5">
                <form action="{{ route('admin.issue-management.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3 g-md-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body-secondary">Complaint Category <span class="text-danger">*</span></label>
                            <select name="issue_category_id" id="issue_category" class="form-select" required>
                                <option value="">— Select category —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                                @endforeach
                            </select>
                            @error('issue_category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body-secondary">Complaint Sub-Category <span class="text-danger">*</span></label>
                            <select name="issue_sub_category_id" id="sub_categories" class="form-select" required>
                                <option value="">— Select sub-category —</option>
                            </select>
                            @error('issue_sub_category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 g-md-4 mt-0">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body-secondary">Priority <span class="text-danger">*</span></label>
                            <select name="issue_priority_id" id="issue_priority" class="form-select" required>
                                <option value="">— Select priority —</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->pk }}">{{ $priority->priority }}</option>
                                @endforeach
                            </select>
                            @error('issue_priority_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3 g-md-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body-secondary">Complainant <span class="text-danger">*</span></label>
                            <select name="created_by" id="complainant" class="form-select" required>
                                <option value="">Search complainant by name...</option>
                                @if(isset($employees))
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_pk }}" data-mobile="{{ $employee->mobile }}" {{ (isset($currentUserEmployeeId) && $currentUserEmployeeId == $employee->employee_pk) ? 'selected' : '' }}>{{ $employee->employee_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-text text-muted">Type to search when creating issue on behalf of others.</div>
                            @error('created_by')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body-secondary">Mobile Number</label>
                            <input type="text" class="form-control bg-light" placeholder="Auto-filled" readonly id="mobile_number" name="mobile_number" aria-readonly="true">
                        </div>
                    </div>

                    <div class="row g-3 g-md-4 mt-0">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body-secondary">Nodal Employee (Level 1) <span class="text-danger">*</span></label>
                            <select name="nodal_employee_id" id="nodal_employee" class="form-select" required>
                                <option value="">— Select category first —</option>
                            </select>
                            <div class="form-text text-muted">Auto-selected from escalation matrix.</div>
                            @error('nodal_employee_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <input type="hidden" name="sub_category_name" id="sub_category_name" required value="">
                    </div>

                    <div id="escalation_levels_display" class="mb-0 d-none">
                        <label class="form-label fw-semibold text-body-secondary">Escalation Hierarchy (read-only)</label>
                        <div class="card bg-body-secondary border-0 rounded-3">
                            <div class="card-body py-3 px-4 small">
                                <div class="mb-1"><strong>Level 2:</strong> <span id="level2_display">—</span></div>
                                <div><strong>Level 3:</strong> <span id="level3_display">—</span></div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body-secondary">Detail Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="5" maxlength="1000" placeholder="Enter detailed description of the issue…" required>{{ old('description') }}</textarea>
                        <div class="form-text text-muted"><span id="char-count">0</span>/1000 characters</div>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body-secondary d-block">Location <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3 pt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="location" id="loc_hostel" value="H" required {{ old('location') == 'H' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_hostel">Hostel</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="location" id="loc_other" value="O" {{ old('location') == 'O' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_other">Others</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="location" id="loc_residential" value="R" {{ old('location') == 'R' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_residential">Residential</label>
                            </div>
                        </div>
                        @error('location')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="building_section" class="d-none rounded-3 bg-body-secondary border p-4 mb-3">
                        <h6 class="fw-semibold text-body-secondary mb-3">Building details</h6>
                        <div class="row g-3 g-md-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-body-secondary">Building / Hostel <span class="text-danger">*</span></label>
                                <select name="building_master_pk" id="building_select" class="form-select">
                                    <option value="">— Select —</option>
                                    @foreach($buildings as $building)
                                        <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-body-secondary">Floor</label>
                                <select id="floor_select" class="form-select" name="floor_id">
                                    <option value="">— Select floor —</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-body-secondary">Room / House no.</label>
                                <select name="room_name" id="room_select" class="form-select">
                                    <option value="">— Select room —</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3 g-md-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body-secondary">Attach image (optional)</label>
                            <input type="file" name="complaint_img_url[]" id="complaint_img_url" class="form-control {{ $errors->has('complaint_img_url') ? 'is-invalid' : '' }}" accept=".jpg,.jpeg,.png" multiple>
                            <div class="form-text text-muted">Max 5MB per file. JPG and PNG only.</div>
                            <div id="attachment_validation_error" class="text-danger small mt-1 d-none"></div>
                            @error('complaint_img_url')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 pt-3 mt-2">
                        <a href="{{ route('admin.issue-management.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4" id="btn_log_issue">Log Issue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Reusable Select2 with search and focus-on-open for all dropdowns
    function initSelect2($el, placeholder) {
        if (!$el.length || !$.fn.select2) return;
        if ($el.data('select2')) $el.select2('destroy');
        $el.select2({
            placeholder: placeholder || '— Search / Select —',
            allowClear: true,
            width: '100%'
        });
        $el.off('select2:open').on('select2:open', function() {
            setTimeout(function() { $('.select2-container--open .select2-search__field').focus(); }, 0);
        });
    }

    // Apply searchable Select2 to all dropdowns
    initSelect2($('#issue_category'), '— Select category —');
    initSelect2($('#sub_categories'), '— Select sub-category —');
    initSelect2($('#issue_priority'), '— Select priority —');
    initSelect2($('#complainant'), 'Search complainant by name...');
    initSelect2($('#nodal_employee'), '— Select category first —');
    initSelect2($('#building_select'), '— Select —');
    initSelect2($('#floor_select'), '— Select floor —');
    initSelect2($('#room_select'), '— Select room —');

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
        $('#nodal_employee').html('<option value="">- Select -</option>');
        initSelect2($('#nodal_employee'), '— Select category first —');
        
        if(categoryId) {
            // Load sub-categories
            $.ajax({
                url: '/admin/issue-management/sub-categories/' + categoryId,
                type: 'GET',
                success: function(data) {
                    $('#sub_categories').html('<option value="">— Select sub-category —</option>');
                    $.each(data, function(key, value) {
                        $('#sub_categories').append('<option value="'+ value.pk +'">'+ value.issue_sub_category +'</option>');
                    });
                    initSelect2($('#sub_categories'), '— Select sub-category —');
                }
            });
            
            // Load nodal employees (Level 1 only) - auto-select first; Level 2 & 3 for display only
            $.ajax({
                url: '/admin/issue-management/nodal-employees/' + categoryId,
                type: 'GET',
                success: function(response) {
                    if(response.success && response.level1 && response.level1.length > 0) {
                        $('#nodal_employee').html('<option value="">- Select -</option>');
                        var autoSelect = response.level1_auto_select;
                        $.each(response.level1, function(key, employee) {
                            var fullName = employee.employee_name || (employee.first_name + ' ' + (employee.middle_name ? employee.middle_name + ' ' : '') + employee.last_name);
                            var selected = (autoSelect && employee.employee_pk == autoSelect) ? 'selected' : '';
                            $('#nodal_employee').append('<option value="'+ employee.employee_pk +'" '+ selected +'>'+ fullName +'</option>');
                        });
                        initSelect2($('#nodal_employee'), '— Select —');
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
                        $('#nodal_employee').html('<option value="">No Level 1 employees - configure Escalation Matrix</option>');
                        initSelect2($('#nodal_employee'), '— Select —');
                        $('#escalation_levels_display').addClass('d-none');
                    }
                },
                error: function() {
                    console.log('Error loading nodal employees');
                    $('#nodal_employee').html('<option value="">Error loading employees</option>');
                    initSelect2($('#nodal_employee'), '— Select —');
                    $('#escalation_levels_display').addClass('d-none');
                }
            });
        } else {
            $('#sub_categories').html('<option value="">— Select sub-category —</option>');
            $('#nodal_employee').html('<option value="">— Select category first —</option>');
            initSelect2($('#sub_categories'), '— Select sub-category —');
            initSelect2($('#nodal_employee'), '— Select category first —');
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
        var mobile = $(this).find('option:selected').data('mobile');
        var normalized = getNormalizedMobile(mobile);
        $('#mobile_number').val(!selected ? '' : (normalized ? normalized : 'Mobile number is not available'));
    });

    // Auto-fill mobile on page load if complainant is pre-selected (logged-in user)
    if ($('#complainant').val()) {
        var mobile = $('#complainant').find('option:selected').data('mobile');
        var normalized = getNormalizedMobile(mobile);
        $('#mobile_number').val(normalized ? normalized : 'Mobile number is not available');
    }

    // Show/hide location sections based on location type
    $('input[name="location"]').change(function() {
        var type = $(this).val();
        $('#building_section').addClass('d-none');
        
        // Reset building details
        $('#building_select').html('<option value="">— Select —</option>');
        $('#floor_select').html('<option value="">— Select floor —</option>');
        $('#room_select').html('<option value="">— Select room —</option>');
        initSelect2($('#building_select'), '— Select —');
        initSelect2($('#floor_select'), '— Select floor —');
        initSelect2($('#room_select'), '— Select room —');
        
        if(type == 'H' || type == 'R' || type == 'O') {
            $('#building_section').removeClass('d-none');
            
            // Load buildings based on location type
            $.ajax({
                url: '/admin/issue-management/buildings',
                type: 'GET',
                data: { type: type },
                success: function(response) {
                    if(response.status) {
                        $('#building_select').html('<option value="">— Select —</option>');
                        $.each(response.data, function(key, value) {
                            $('#building_select').append('<option value="'+ value.pk +'">'+ value.building_name +'</option>');
                        });
                        initSelect2($('#building_select'), '— Select —');
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
                        $('#floor_select').html('<option value="">— Select floor —</option>');
                        $.each(response.data, function(key, value) {
                            // Use ?? so 0 is preserved (|| would treat 0 as falsy and show undefined)
                            var floorId = value.floor_id ?? value.pk ?? value.estate_unit_sub_type_master_pk ?? '';
                            var floorName = value.floor_name ?? value.floor ?? value.unit_sub_type ?? '';
                            $('#floor_select').append('<option value="'+ floorId +'">'+ floorName +'</option>');
                        });
                        initSelect2($('#floor_select'), '— Select floor —');
                    }
                },
                error: function() {
                    console.log('Error loading floors');
                }
            });
        } else {
            $('#floor_select').html('<option value="">— Select floor —</option>');
            $('#room_select').html('<option value="">— Select room —</option>');
            initSelect2($('#floor_select'), '— Select floor —');
            initSelect2($('#room_select'), '— Select room —');
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
                        $('#room_select').html('<option value="">— Select room —</option>');
                        $.each(response.data, function(key, value) {
                            // Use ?? so 0 is preserved (|| would treat 0 as falsy and show undefined)
                            var roomId = value.pk;
                            var roomName = value.room_name ?? value.house_no ?? value.floor ?? '';
                            $('#room_select').append('<option value="'+ roomName +'">'+ roomName +'</option>');
                        });
                        initSelect2($('#room_select'), '— Select room —');
                    }
                },
                error: function() {
                    console.log('Error loading rooms');
                }
            });
        } else {
            $('#room_select').html('<option value="">— Select room —</option>');
            initSelect2($('#room_select'), '— Select room —');
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

});
</script>
@endsection
