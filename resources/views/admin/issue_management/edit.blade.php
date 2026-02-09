@extends('admin.layouts.master')

@section('title', 'Edit Issue - Sargam | Lal Bahadur')

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
.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
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
    <x-breadcrum title="Edit Issue" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <h4 class="mb-3">Edit Issue #{{ $issue->pk }}</h4>
                <hr>
                
                @php
                    // Check if issue is assigned - if statusHistory has any record with assign_to, it's assigned
                    $isAssigned = $issue->statusHistory && $issue->statusHistory->whereNotNull('assign_to')->count() > 0;
                @endphp

                @if($isAssigned)
                <input type="hidden" name="issue_category_id" value="{{ $issue->issue_category_master_pk }}">
                <input type="hidden" name="issue_sub_category_id" value="{{ $issue->subCategoryMappings->first()->issue_sub_category_master_pk ?? '' }}">
                <input type="hidden" name="issue_priority_id" value="{{ $issue->issue_priority_master_pk ?? '' }}">
                <div class="assignment-notice">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div>
                        <strong>Issue Assigned:</strong> This issue has been assigned. You can only update the status and add remarks. Other details are locked for editing.
                    </div>
                </div>
                @endif

                <form action="{{ route('admin.issue-management.update', $issue->pk) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Complaint Category <span class="text-danger">*</span></label>
                                <select name="issue_category_id" id="issue_category" class="form-select" {{ $isAssigned ? 'disabled' : '' }} required>
                                    <option value="">- select -</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->pk }}" {{ $issue->issue_category_master_pk == $category->pk ? 'selected' : '' }}>
                                            {{ $category->issue_category }}
                                        </option>
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
                                    @if($issue->subCategoryMappings->isNotEmpty())
                                        @foreach($issue->subCategoryMappings as $mapping)
                                            <option value="{{ $mapping->issue_sub_category_master_pk }}" selected>
                                                {{ $mapping->subCategory->issue_sub_category ?? '' }}
                                            </option>
                                        @endforeach
                                    @endif
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
                                <select name="issue_priority_id" id="issue_priority" class="form-select" {{ $isAssigned ? 'disabled' : '' }} required>
                                    <option value="">- Select -</option>
                                    @foreach($priorities as $priority)
                                        <option value="{{ $priority->pk }}" {{ $issue->issue_priority_master_pk == $priority->pk ? 'selected' : '' }}>
                                            {{ $priority->priority }}
                                        </option>
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
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_pk }}" 
                                            data-mobile="{{ $employee->mobile }}"
                                            {{ $issue->created_by == $employee->employee_pk ? 'selected' : '' }}>
                                            {{ $employee->employee_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('created_by')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mobile Number</label>
                                <input type="text" class="form-control" placeholder="Auto-filled" readonly 
                                    id="mobile_number" name="mobile_number" 
                                    value="{{ $issue->creator->mobile ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nodal Employee <span class="text-danger">*</span></label>
                                <select name="nodal_employee_id" id="nodal_employee" class="form-select" required>
                                    <option value="">- Select -</option>

                                    @if($issue->employee_master_pk)
                                        <option value="{{ $issue->employee_master_pk }}" selected>Current Employee</option>
                                    @endif
                                </select>
                                @error('nodal_employee_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <input type="hidden" name="sub_category_name" id="sub_category_name" 
                                    class="form-control" placeholder="Sub category name will auto-fill" 
                                    readonly required 
                                    value="{{ $issue->subCategoryMappings->first()->sub_category_name ?? '' }}">

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Detail Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="5" 
                            maxlength="1000" placeholder="Enter Detailed Description" required>{{ $issue->description }}</textarea>
                        <div class="char-counter"><span id="char-count">{{ strlen($issue->description) }}</span>/1000 Character</div>
                        @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="location" id="loc_hostel" 
                                    value="H" required {{ $issue->location == 'H' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_hostel">Hostel</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="location" id="loc_other" 
                                    value="O" {{ $issue->location == 'O' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_other">Others</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="location" id="loc_residential" 
                                    value="R" {{ $issue->location == 'R' ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_residential">Residential</label>
                            </div>
                        </div>
                        @error('location')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="building_section" class="{{ $issue->location ? '' : 'd-none' }}">
                        <h6 class="mt-3 mb-3">Building Details</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Building/Hostel <span class="text-danger">*</span></label>
                                    <select name="building_select" id="building_select" class="form-select">
                                        <option value="">-- Select --</option>
                                        @php
                                            $currentBuilding = null;
                                            if($issue->location == 'H' && $issue->hostelMapping) {
                                                $currentBuilding = $issue->hostelMapping->hostel_building_master_pk;
                                            } elseif($issue->location == 'R' && $issue->hostelMapping) {
                                                $currentBuilding = $issue->hostelMapping->hostel_building_master_pk;
                                            } elseif($issue->location == 'O' && $issue->buildingMapping) {
                                                $currentBuilding = $issue->buildingMapping->building_master_pk;
                                            }
                                        @endphp
                                        @if($currentBuilding)
                                            <option value="{{ $currentBuilding }}" selected>Current Building</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Floor Name/Type:</label>
                                    <select id="floor_select" class="form-select" name="floor_select">
                                        <option value="">Select Floor Name</option>
                                        @php
                                            $currentFloor = null;
                                            if($issue->hostelMapping) {
                                                $currentFloor = $issue->hostelMapping->floor_name;
                                            } elseif($issue->buildingMapping) {
                                                $currentFloor = $issue->buildingMapping->floor_name;
                                            }
                                        @endphp
                                        @if($currentFloor)
                                            <option value="{{ $currentFloor }}" selected>{{ $currentFloor }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Room No/House No.:</label>
                                    <select name="room_select" id="room_select" class="form-select">
                                        <option value="">Select Room</option>
                                        @php
                                            $currentRoom = null;
                                            if($issue->hostelMapping) {
                                                $currentRoom = $issue->hostelMapping->room_name;
                                            } elseif($issue->buildingMapping) {
                                                $currentRoom = $issue->buildingMapping->room_name;
                                            }
                                        @endphp
                                        @if($currentRoom)
                                            <option value="{{ $currentRoom }}" selected>{{ $currentRoom }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    @if($issue->issue_status == 0)
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="btn btn-secondary rounded">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Issue</button>
                    </div>
                    @else
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle"></i> This issue cannot be edited as its status is not "Open".
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
    // Character counter
    function updateCharCount() {
        var len = $('#description').val().length;
        $('#char-count').text(len);
    }
    $('#description').on('input keyup', updateCharCount);

    // Load sub-categories and nodal employees when category changes
    $('#issue_category').change(function() {
        var categoryId = $(this).val();
        var currentSubCategoryId = '{{ $issue->subCategoryMappings->first()->issue_sub_category_master_pk ?? "" }}';
        var currentNodalEmpPk = '{{ $issue->employee_master_pk }}';
        $('#nodal_employee').html('<option value="">- Select -</option>');
        
        if(categoryId) {
            $.ajax({
                url: '/admin/issue-management/sub-categories/' + categoryId,
                type: 'GET',
                success: function(response) {
                    $('#sub_categories').html('<option value="">-- Select --</option>');
                    $.each(response, function(key, value) {
                        var selected = (currentSubCategoryId && value.pk == currentSubCategoryId) ? 'selected' : '';
                        $('#sub_categories').append('<option value="'+ value.pk +'" '+ selected +'>'+ value.issue_sub_category +'</option>');
                    });
                }
            });

            $.ajax({
                url: '/admin/issue-management/nodal-employees/' + categoryId,
                type: 'GET',
                success: function(response) {
                    $('#nodal_employee').html('<option value="">- Select -</option>');
                    var employees = response.level1 || response.data || [];
                    var autoSelect = response.level1_auto_select;
                    $.each(employees, function(key, value) {
                        var empPk = value.employee_pk || value.pk;
                        var empName = value.employee_name || (value.first_name + ' ' + (value.middle_name ? value.middle_name + ' ' : '') + value.last_name);
                        var selected = (currentNodalEmpPk && empPk == currentNodalEmpPk) ? 'selected' : (!currentNodalEmpPk && autoSelect && empPk == autoSelect) ? 'selected' : '';
                        $('#nodal_employee').append('<option value="'+ empPk +'" '+ selected +'>'+ empName +'</option>');
                    });
                }
            });
        }
    });

    // Auto-fill sub_category_name
    $('#sub_categories').change(function() {
        var selectedText = $(this).find('option:selected').text();
        $('#sub_category_name').val(selectedText);
    });

    // Auto-fill mobile number
    $('#complainant').change(function() {
        var mobile = $(this).find('option:selected').data('mobile');
        $('#mobile_number').val(mobile || '');
    });

    // Location change - load buildings
    $('input[name="location"]').change(function() {
        var type = $(this).val();
        var currentBuildingId = '{{ $currentBuilding ?? "" }}';
        var currentFloor = '{{ $currentFloor ?? "" }}';
        var currentRoom = '{{ $currentRoom ?? "" }}';
        
        // Convert to integer for proper comparison
        var buildingIdToMatch = currentBuildingId ? parseInt(currentBuildingId) : null;
        console.log("Loading buildings for type:", type, "with current building ID:", buildingIdToMatch);
        $('#building_section').removeClass('d-none');
        
        $.ajax({
            url: '/admin/issue-management/buildings',
            type: 'GET',
            data: { type: type },
            success: function(response) {
                $('#building_select').html('<option value="">-- Select --</option>');
                
                // Handle both wrapped and direct array responses
                var buildings = Array.isArray(response) ? response : (response.data || []);
                
                $.each(buildings, function(key, value) {
                    var displayName = value.building_name || value.hostel_building_name || value.block_name;
                    $('#building_select').append('<option value="'+ value.pk +'">'+ displayName +'</option>');
                });
                
                // Set the selected value if currentBuildingId exists
                if(buildingIdToMatch) {
                    $('#building_select').val(buildingIdToMatch);
                    // Trigger change to load floors
                    setTimeout(function() {
                        $('#building_select').trigger('change');
                    }, 300);
                }
            }
        });
    });

    // Building change - load floors
    $('#building_select').change(function() {
        var buildingId = $(this).val();
        var locationType = $('input[name="location"]:checked').val();
        var currentFloor = '{{ $currentFloor ?? "" }}';
        var currentRoom = '{{ $currentRoom ?? "" }}';
        
        if(buildingId && locationType) {
            $.ajax({
                url: '/admin/issue-management/floors',
                type: 'GET',
                data: { building_id: buildingId, type: locationType },
                success: function(response) {
                    $('#floor_select').html('<option value="">Select Floor Name</option>');
                    
                    // Handle both wrapped and direct array responses
                    var floors = Array.isArray(response) ? response : (response.data || []);
                    
                    $.each(floors, function(key, value) {
                       
                         var floorId = value.floor_id || value.pk || value.estate_unit_sub_type_master_pk;
                            var floorName = value.floor_name || value.floor || value.unit_sub_type;
                        var selected = (currentFloor && floorId == currentFloor) ? 'selected' : '';

                            $('#floor_select').append('<option value="'+ floorId +'" '+ selected +'>'+ floorName +'</option>');

                    });
                    
                    // If floor is selected, trigger its change to load rooms
                    if(currentFloor && $('#floor_select').val() == currentFloor) {
                        setTimeout(function() {
                            $('#floor_select').trigger('change');
                        }, 300);
                    }
                }
            });
        }
    });

    // Floor change - load rooms
    $('#floor_select').change(function() {
        var floorId = $(this).val();
        var buildingId = $('#building_select').val();
        var locationType = $('input[name="location"]:checked').val();
        var currentRoom = '{{ $currentRoom ?? "" }}';
        
        if(floorId && buildingId && locationType) {
            $.ajax({
                url: '/admin/issue-management/rooms',
                type: 'GET',
                data: { floor_id: floorId, building_id: buildingId, type: locationType },
                success: function(response) {
                    $('#room_select').html('<option value="">Select Room</option>');
                    
                    // Handle both wrapped and direct array responses
                    var rooms = Array.isArray(response) ? response : (response.data || []);
                    
                    $.each(rooms, function(key, value) {
                        var roomId = value.pk || value.room_no;
                        var roomName = value.room_name || value.room_no || value.house_no;
                        var selected = (currentRoom && roomId == currentRoom) ? 'selected' : '';

                        if(roomName) {
                            $('#room_select').append('<option value="'+ roomName +'" '+ selected +'>'+ roomName +'</option>');
                        }
                    });
                    
                    // Set the selected value if currentRoom exists
                    if(currentRoom) {
                        $('#room_select').val(currentRoom);
                    }
                }
            });
        }
    });

    // Trigger load on page load to populate sub-categories and nodal employees
    if($('#issue_category').val()) {
        $('#issue_category').trigger('change');
        
        // Also trigger sub-category change to fill sub_category_name
        setTimeout(function() {
            $('#sub_categories').trigger('change');
        }, 500);
    }
    
    // Trigger location change to load buildings, floors, rooms
    if($('input[name="location"]:checked').length > 0) {
        var currentLocation = $('input[name="location"]:checked').val();
        $('input[name="location"]:checked').trigger('change');
        
        // Wait for buildings to load, then trigger building select if there's a current building
        setTimeout(function() {
            var buildingId = $('#building_select').val();
            if(buildingId) {
                $('#building_select').trigger('change');
            }
        }, 500);
    }

    // Auto-fill mobile number on page load
    var initialMobile = $('#complainant').find('option:selected').data('mobile');
    if(initialMobile) {
        $('#mobile_number').val(initialMobile);
    }
});
</script>
@endsection
