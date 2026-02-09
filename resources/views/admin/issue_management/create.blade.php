@extends('admin.layouts.master')

@section('title', 'Log New Issue - Sargam | Lal Bahadur')

@section('css')
<style>
.form-control, .form-select {
    background-color: #fff !important;
    color: #212529 !important;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
.form-control:focus, .form-select:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
}
.complaint-card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.08);
    overflow: hidden;
}
.complaint-card .card-body {
    padding: 1.75rem 2rem;
}
.complaint-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.complaint-header .back-link {
    color: #004a93;
    text-decoration: none;
    font-size: 1.25rem;
    line-height: 1;
    padding: 0.25rem;
    border-radius: 0.375rem;
    transition: background-color 0.2s, color 0.2s;
}
.complaint-header .back-link:hover {
    color: #003366;
    background-color: rgba(0, 74, 147, 0.08);
}
.complaint-header .page-title {
    font-weight: 600;
    color: #1a365d;
    font-size: 1.35rem;
    margin: 0;
}
.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
}
.form-select, .form-control {
    padding: 0.5rem 0.75rem;
}
.btn-submit-complaint {
    background-color: #004a93;
    border-color: #004a93;
    color: #fff;
    padding: 0.5rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
}
.btn-submit-complaint:hover {
    background-color: #003366;
    border-color: #003366;
    color: #fff;
}
.char-counter {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 0.25rem;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid py-3">
    <x-breadcrum title="Log New Issue" />
    <div class="datatables">
        <div class="card complaint-card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <form action="{{ route('admin.issue-management.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Complaint Category <span class="text-danger">*</span></label>
                                <select name="issue_category_id" id="issue_category" class="form-select" required>
                                    <option value="">- select -</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                                    @endforeach
                                </select>
                                @error('issue_category_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Complaint Sub-Category <span class="text-danger">*</span></label>
                                <select name="issue_sub_category_id" id="sub_categories" class="form-select" required>
                                    <option value="">-- Select --</option>
                                </select>
                                @error('issue_sub_category_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select name="issue_priority_id" id="issue_priority" class="form-select" required>
                                    <option value="">- Select -</option>
                                    @foreach($priorities as $priority)
                                        <option value="{{ $priority->pk }}">{{ $priority->priority }}</option>
                                    @endforeach
                                </select>
                                @error('issue_priority_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Complainant <span class="text-danger">*</span></label>
                                <select name="created_by" id="complainant" class="form-select" required>
                                    <option value="">- Select -</option>
                                    @if(isset($employees))
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->employee_pk }}" data-mobile="{{ $employee->mobile }}" {{ (isset($currentUserEmployeeId) && $currentUserEmployeeId == $employee->employee_pk) ? 'selected' : '' }}>{{ $employee->employee_name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('created_by')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mobile Number</label>
                                <input type="text" class="form-control" placeholder="Auto-filled" readonly id="mobile_number" name="mobile_number">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nodal Employee (Level 1) <span class="text-danger">*</span></label>
                                <select name="nodal_employee_id" id="nodal_employee" class="form-select" required>
                                    <option value="">- Select Category First -</option>
                                </select>
                                <small class="text-muted">Auto-selected from escalation matrix</small>
                                @error('nodal_employee_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Sub Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="sub_category_name" id="sub_category_name" class="form-control" placeholder="Sub category name will auto-fill" readonly required>
                                @error('sub_category_name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div id="escalation_levels_display" class="mb-3 d-none">
                        <label class="form-label">Escalation Hierarchy (Display Only)</label>
                        <div class="card bg-light">
                            <div class="card-body py-2 px-3 small">
                                <div><strong>Level 2:</strong> <span id="level2_display">—</span></div>
                                <div class="mt-1"><strong>Level 3:</strong> <span id="level3_display">—</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Detail Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="5" maxlength="1000" placeholder="Enter Detailed Description" required>{{ old('description') }}</textarea>
                        <div class="char-counter"><span id="char-count">0</span>/1000 Character</div>
                        @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="location" id="loc_hostel" value="H" required {{ old('location') == 'H' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_hostel">Hostel</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="location" id="loc_other" value="O" {{ old('location') == 'O' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_other">Others</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="location" id="loc_residential" value="R" {{ old('location') == 'R' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_residential">Residential</label>
                            </div>
                        </div>
                        @error('location')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="building_section" class="d-none">
                        <h6 class="mt-3 mb-3">Building Details</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Building/Hostel <span class="text-danger">*</span></label>
                                    <select name="building_master_pk" id="building_select" class="form-select">
                                        <option value="">-- Select --</option>
                                        @foreach($buildings as $building)
                                            <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Floor Name/Type :</label>
                                    <select id="floor_select" class="form-select" name="floor_id">
                                        <option value="">Select Floor Name</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Room No/House No. :</label>
                                    <select name="room_name" id="room_select" class="form-select">
                                        <option value="">Select Room</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Attach Image (Optional)</label>
                                <input type="file" name="complaint_img_url" class="form-control" accept=".jpg,.jpeg,.png" multiple>
                                <small class="text-muted">Max size: 5MB per file. Allowed: JPG, PNG</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="{{ route('admin.issue-management.index') }}" class="btn btn-secondary rounded">Cancel</a>
                        <button type="submit" class="btn btn-submit-complaint">Log Issue</button>
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
        
        if(categoryId) {
            // Load sub-categories
            $.ajax({
                url: '/admin/issue-management/sub-categories/' + categoryId,
                type: 'GET',
                success: function(data) {
                    $('#sub_categories').html('<option value="">-- Select --</option>');
                    $.each(data, function(key, value) {
                        $('#sub_categories').append('<option value="'+ value.pk +'">'+ value.issue_sub_category +'</option>');
                    });
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
                        $('#escalation_levels_display').addClass('d-none');
                    }
                },
                error: function() {
                    console.log('Error loading nodal employees');
                    $('#nodal_employee').html('<option value="">Error loading employees</option>');
                    $('#escalation_levels_display').addClass('d-none');
                }
            });
        } else {
            $('#sub_categories').html('<option value="">-- Select --</option>');
            $('#nodal_employee').html('<option value="">- Select Category First -</option>');
            $('#escalation_levels_display').addClass('d-none');
        }
    });

    // Auto-fill sub_category_name when sub-category is selected
    $('#sub_categories').change(function() {
        var selectedText = $(this).find('option:selected').text();
        $('#sub_category_name').val(selectedText);
    });

    // Auto-fill mobile number when complainant is selected
    $('#complainant').change(function() {
        var mobile = $(this).find('option:selected').data('mobile');
        $('#mobile_number').val(mobile || '');
    });
    // Auto-fill mobile on page load if complainant is pre-selected (logged-in user)
    if ($('#complainant').val()) {
        var mobile = $('#complainant').find('option:selected').data('mobile');
        $('#mobile_number').val(mobile || '');
    }

    // Show/hide location sections based on location type
    $('input[name="location"]').change(function() {
        var type = $(this).val();
        $('#building_section').addClass('d-none');
        
        // Reset building details
        $('#building_select').val('').html('<option value="">-- Select --</option>');
        $('#floor_select').val('').html('<option value="">Select Floor Name</option>');
        $('#room_select').val('').html('<option value="">Select Room</option>');
        
        if(type == 'H' || type == 'R' || type == 'O') {
            $('#building_section').removeClass('d-none');
            
            // Load buildings based on location type
            $.ajax({
                url: '/admin/issue-management/buildings',
                type: 'GET',
                data: { type: type },
                success: function(response) {
                    if(response.status) {
                        $('#building_select').html('<option value="">-- Select --</option>');
                        $.each(response.data, function(key, value) {
                            $('#building_select').append('<option value="'+ value.pk +'">'+ value.building_name +'</option>');
                        });
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
                        $('#floor_select').html('<option value="">Select Floor Name</option>');
                        $.each(response.data, function(key, value) {
                            // Handle different field names for different location types
                            var floorId = value.floor_id || value.pk || value.estate_unit_sub_type_master_pk;
                            var floorName = value.floor_name || value.floor || value.unit_sub_type;
                            $('#floor_select').append('<option value="'+ floorId +'">'+ floorName +'</option>');
                        });
                    }
                },
                error: function() {
                    console.log('Error loading floors');
                }
            });
        } else {
            $('#floor_select').html('<option value="">Select Floor Name</option>');
            $('#room_select').html('<option value="">Select Room</option>');
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
                        $('#room_select').html('<option value="">Select Room</option>');
                        $.each(response.data, function(key, value) {
                            // Handle different field names for different location types
                            var roomId = value.pk;
                            var roomName = value.room_name || value.house_no || value.floor;
                            $('#room_select').append('<option value="'+ roomName +'">'+ roomName +'</option>');
                        });
                    }
                },
                error: function() {
                    console.log('Error loading rooms');
                }
            });
        } else {
            $('#room_select').html('<option value="">Select Room</option>');
        }
    });

});
</script>
@endsection
