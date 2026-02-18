@extends('admin.layouts.master')

@section('title', 'Issue Details - Sargam | Lal Bahadur')

@section('css')
<style>
.table {
    background-color: #cc8989 !important;
}
.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
}
</style>
@endsection


@section('setup_content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        </div>
@endif
<div class="container-fluid">
    <x-breadcrum title="Issue Details" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">Issue #{{ $issue->pk }} Details</h4>
                    </div>
                    <div class="col-6 text-end">
                        <a href="{{ route('admin.issue-management.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                        @php
                            $isNodalOrAssigned = $issue->employee_master_pk == Auth::user()->user_id || $issue->assigned_to == Auth::user()->user_id;
                            $isComplainant = $issue->created_by == Auth::user()->user_id;
                            $isLogger = $issue->issue_logger == Auth::user()->user_id;
                            $isCompleted = (int) $issue->issue_status === 2;
                            $canUpdateStatus = $isNodalOrAssigned || ($isComplainant && $isCompleted) || ($isLogger && $isCompleted);
                            $showReopenOnly = ($isComplainant || $isLogger) && $isCompleted;
                            $canEdit = ($isComplainant || $isLogger) && !$isCompleted;
                        @endphp
                        @once
                        @if($canUpdateStatus)
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                            @if($showReopenOnly)
                                <i class="bi bi-arrow-repeat"></i> Reopen Issue
                            @else
                                <i class="bi bi-arrow-up-circle"></i> Update Status
                            @endif
                        </button>
                        @endif
                        @endonce
                        @if($canEdit)
                            <a href="{{ route('admin.issue-management.edit', $issue->pk) }}" class="btn btn-info">
                                <i class="bi bi-pencil"></i> Edit Issue
                            </a>
                        @endif
                    </div>
                </div>
                <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Issue ID</th>
                                    <td>{{ $issue->pk }}</td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td>{{ $issue->category->issue_category ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Sub-Categories</th>
                                    <td>
                                        @forelse($issue->subCategoryMappings as $mapping)
                                            <span class="badge bg-secondary">{{ $mapping->subCategory->issue_sub_category ?? '' }}</span>
                                        @empty
                                            N/A
                                        @endforelse
                                    </td>
                                </tr>
                               
                              
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-{{ $issue->issue_status == 2 ? 'success' : ($issue->issue_status == 1 ? 'info' : 'warning') }}">
                                            {{ $issue->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                 <tr>
                                    <th width="40%">Created Date</th>
                                    <td>{{ $issue->created_date->format('d-m-Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <table class="table table-bordered">
                               
                                <tr>
                                    <th>Created By</th>
                                    <td>{{ $issue->logger->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Issue Logger</th>
                                    <td>{{ $issue->creator->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Assigned To</th>
                                    <td>
                                        @if($issue->assigned_to)
                                            @php
                                                if (is_numeric($issue->assigned_to)) {
                                                    $assignedEmployee = \DB::table('employee_master')->where('pk', $issue->assigned_to)->first();
                                                    echo $assignedEmployee ? trim($assignedEmployee->first_name . ' ' . ($assignedEmployee->middle_name ?? '') . ' ' . $assignedEmployee->last_name) : 'N/A';
                                                } else {
                                                    echo e($issue->assigned_to);
                                                }
                                            @endphp
                                        @else
                                            Not Assigned
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Assigned Person Contact Number</th>
                                    <td>{{ $issue->assigned_to_contact ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Nodal Officer</th>
                                    <td>{{ $issue->nodal_officer->name ?? 'N/A' }}</td>
                                </tr>
                                @if($issue->clear_date)
                                <tr>
                                    <th>Resolved On</th>
                                    <td>{{ $issue->clear_date->format('d-m-Y H:i:s') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Description</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    {{ $issue->description }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Location Details</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    @if($issue->location === 'O' && !empty($locationFallback))
                                        <strong>Building:</strong> {{ $locationFallback['name'] }}<br>
                                        <strong>Floor:</strong> {{ $locationFallback['floor'] }}<br>
                                        <strong>Room:</strong> {{ $locationFallback['room'] }}<br>
                                    @elseif($issue->buildingMapping)
                                        @php
                                            $bldgName = $issue->buildingMapping->building->building_name ?? '';
                                            $bldgFloor = $issue->buildingMapping->floor_name ?? '';
                                            $bldgRoom = $issue->buildingMapping->room_name ?? '';
                                        @endphp
                                        <strong>Building:</strong> {{ trim($bldgName) ?: 'N/A' }}<br>
                                        <strong>Floor:</strong> {{ ($bldgFloor !== null && $bldgFloor !== '') ? $bldgFloor : 'N/A' }}<br>
                                        <strong>Room:</strong> {{ ($bldgRoom !== null && $bldgRoom !== '') ? $bldgRoom : 'N/A' }}<br>
                                    @elseif($issue->hostelMapping)
                                        @php
                                            $hostelName = 'N/A';
                                            if ($issue->hostelMapping->hostelBuilding) {
                                                $hostelName = trim($issue->hostelMapping->hostelBuilding->hostel_name ?? $issue->hostelMapping->hostelBuilding->building_name ?? '') ?: 'N/A';
                                            } else {
                                                $hostelRow = \DB::table('hostel_building_master')->where('pk', $issue->hostelMapping->hostel_building_master_pk)->first();
                                                $hostelName = $hostelRow ? (trim($hostelRow->hostel_name ?? $hostelRow->building_name ?? '') ?: 'N/A') : 'N/A';
                                            }
                                            $hostelFloor = ($issue->hostelMapping->floor_name !== null && $issue->hostelMapping->floor_name !== '') ? $issue->hostelMapping->floor_name : 'N/A';
                                            $hostelRoom = ($issue->hostelMapping->room_name !== null && $issue->hostelMapping->room_name !== '') ? $issue->hostelMapping->room_name : 'N/A';
                                        @endphp
                                        <strong>Hostel:</strong> {{ $hostelName }}<br>
                                        <strong>Floor:</strong> {{ $hostelFloor }}<br>
                                        <strong>Room:</strong> {{ $hostelRoom }}<br>
                                    @elseif(!empty($locationFallback))
                                        <strong>{{ $locationFallback['type'] === 'building' ? 'Building' : ($locationFallback['type'] === 'residential' ? 'Residential' : 'Hostel') }}:</strong> {{ $locationFallback['name'] }}<br>
                                        <strong>Floor:</strong> {{ $locationFallback['floor'] }}<br>
                                        <strong>Room:</strong> {{ $locationFallback['room'] }}<br>
                                    @else
                                        <strong>Hostel:</strong> N/A<br>
                                        <strong>Floor:</strong> N/A<br>
                                        <strong>Room:</strong> N/A<br>
                                    @endif
                                    <strong>Additional Location:</strong> {{ trim($issue->location ?? '') ?: 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($issue->remark)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Remarks</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    {{ $issue->remark }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($issue->feedback)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Feedback</h5>
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    {{ $issue->feedback }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @php
                        $docPaths = [];
                        $d = $issue->document ?? '';
                        $cimg = $issue->complaint_img ?? '';
                        if (!empty($d)) {
                            if (str_starts_with(trim($d), '[')) {
                                $docPaths = json_decode($d, true) ?: [];
                            } else {
                                $docPaths = [$d];
                            }
                        }
                        if (!empty($cimg)) {
                            $decoded = is_string($cimg) ? json_decode($cimg, true) : $cimg;
                            if (is_array($decoded)) {
                                $docPaths = array_merge($docPaths, $decoded);
                            }
                        }
                        $docPaths = array_values(array_filter($docPaths));
                    @endphp
                    @if(count($docPaths) > 0)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Attachments</h5>
                            <div class="d-flex flex-wrap gap-3 align-items-start">
                                @foreach($docPaths as $path)
                                @php
                                    $url = (str_starts_with(trim($path), 'http://') || str_starts_with(trim($path), 'https://')) 
                                        ? $path 
                                        : asset('storage/' . ltrim($path, '/'));
                                @endphp
                                <div class="d-inline-block">
                                    <a href="{{ $url }}" target="_blank" class="d-block text-decoration-none">
                                        <img src="{{ $url }}" alt="Attachment" class="img-thumbnail" style="max-height: 120px; max-width: 180px; object-fit: cover;">
                                    </a>
                                    <small class="d-block text-muted text-center mt-1">Image</small>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($issue->statusHistory->count() > 0)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Status History</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>Updated By</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($issue->statusHistory as $history)
                                        <tr>
                                            <td>{{ $history->issue_date->format('d-m-Y H:i:s') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $history->issue_status == 2 ? 'success' : ($history->issue_status == 1 ? 'info' : 'warning') }}">
                                                    {{ $history->status_label }}
                                                </span>
                                            </td>
                                            <td>{{ $history->creator->first_name ?? 'System' }}</td>
                                            <td>{{ $history->remarks ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('admin.issue-management.index') }}" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.issue-management.status_update', $issue->pk) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Issue Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php
                        $usedStatuses = $issue->statusHistory->pluck('issue_status')->toArray();
                        $isAssigned = !empty($issue->assigned_to);
                        $isNodalOfficer = ($issue->employee_master_pk == Auth::user()->user_id);
                        $canReassign = $isNodalOfficer; // Nodal can change assignee even after first assignment
                        $canOnlyReopen = $isComplainant && $isCompleted;
                    @endphp

                    @if(($issue->created_by == Auth::user()->user_id || $issue->issue_logger == Auth::user()->user_id) && $issue->issue_status === 2)
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Reopen:</strong> As the complainant, you can reopen this completed issue. Add a remark (optional) and submit.
                    </div>
                    <input type="hidden" name="issue_status" value="6">
                    @else
                    <!-- Assignment Locked: only for non-nodal (assigned person) -->
                    @if($isAssigned && !$canReassign)
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Assignment Locked:</strong> This issue has been assigned. You can only update the status and remarks.
                    </div>
                    @endif
                    @if($isAssigned && $canReassign)
                    <div class="alert alert-secondary mb-3">
                        <i class="bi bi-person-gear"></i>
                        <strong>Re-assign:</strong> As nodal officer, you can change the assigned person below if needed.
                    </div>
                    @endif

                    @php
                        // After Reopen (6), all status options stay enabled so user can set any status again
                        $disableStatusOptions = (int)$issue->issue_status !== 6;
                    @endphp
                    <div class="mb-3">
                        <label for="issue_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="issue_status" id="issue_status" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <option value="0" {{ $issue->issue_status == 0 ? 'selected' : '' }} {{ $disableStatusOptions && in_array(0, $usedStatuses) && $issue->issue_status != 0 ? 'disabled' : '' }}>Reported</option>
                            <option value="1" {{ $issue->issue_status == 1 ? 'selected' : '' }} {{ $disableStatusOptions && in_array(1, $usedStatuses) && $issue->issue_status != 1 ? 'disabled' : '' }}>In Progress</option>
                            <option value="2" {{ $issue->issue_status == 2 ? 'selected' : '' }} {{ $disableStatusOptions && in_array(2, $usedStatuses) && $issue->issue_status != 2 ? 'disabled' : '' }}>Completed</option>
                            <option value="3" {{ $issue->issue_status == 3 ? 'selected' : '' }} {{ $disableStatusOptions && in_array(3, $usedStatuses) && $issue->issue_status != 3 ? 'disabled' : '' }}>Pending</option>
                            <option value="6" {{ $issue->issue_status == 6 ? 'selected' : '' }} {{ $disableStatusOptions && in_array(6, $usedStatuses) && $issue->issue_status != 6 ? 'disabled' : '' }}>Reopened</option>
                        </select>
                    </div>

                    <!-- Assignment: read-only when assigned and user is not nodal; else show dropdown (first assign or re-assign by nodal) -->
                    @if($isAssigned && !$canReassign)
                    <!-- Show current assignment as read-only (assigned person cannot change) -->
                    <div class="mb-3">
                        <label for="current_assignment" class="form-label">Currently Assigned To</label>
                        <input type="text" class="form-control" id="current_assignment" readonly style="background-color: #e9ecef;">
                        <input type="hidden" name="assigned_to" id="assigned_to_hidden">
                        <input type="hidden" name="assigned_to_contact" id="assigned_to_contact_hidden">
                    </div>
                    @else
                    <!-- Assign / Re-assign dropdown (required when not assigned; optional when nodal re-assigning) -->
                    <div class="mb-3">
                        <label for="assign_to_type" class="form-label">Assign To @if(!$isAssigned)<span class="text-danger">*</span>@endif</label>
                        <select name="assign_to_type" id="assign_to_type" class="form-select" @if(!$isAssigned) required @endif>
                            <option value="">-- Select @if($isAssigned)(keep current)@else--@endif</option>
                            <option value="other">Other Employee</option>
                            @if(isset($employees) && count($employees) > 0)
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->employee_pk }}" 
                                        data-name="{{ $employee->employee_name }}"
                                        data-mobile="{{ $employee->mobile ?? '' }}"
                                        @if($isAssigned && (string)$issue->assigned_to === (string)$employee->employee_pk) selected @endif>
                                        {{ $employee->employee_name }}
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>No employees found</option>
                            @endif
                        </select>
                    </div>

                    <div class="mb-3" id="phoneNumberSection" style="display: none;">
                        <label for="display_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="display_phone" readonly style="background-color: #e9ecef;">
                    </div>

                    <input type="hidden" name="assigned_to" id="assigned_to_hidden">
                    <input type="hidden" name="assigned_to_contact" id="assigned_to_contact_hidden">

                    <div id="otherFieldsSection" style="display: none;">
                        <div class="mb-3">
                            <label for="other_name" class="form-label">Member Name <span class="text-danger">*</span></label>
                            <input type="text" name="other_name" class="form-control" id="other_name" placeholder="Enter member name" value="{{ $isAssigned && !is_numeric($issue->assigned_to) ? $issue->assigned_to : '' }}">
                        </div>
                        <div class="mb-3">
                            <label for="other_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="other_phone" class="form-control" id="other_phone" placeholder="Enter phone number" maxlength="10" value="{{ $isAssigned && !is_numeric($issue->assigned_to) ? ($issue->assigned_to_contact ?? '') : '' }}">
                        </div>
                    </div>
                    @endif
                    @endif

                    <div class="mb-3">
                        <label for="remark" class="form-label">Remarks</label>
                        <textarea name="remark" id="remark" class="form-control" rows="3" placeholder="Add remarks (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $canOnlyReopen ? 'Reopen Issue' : 'Update Status' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    @if($issue->assigned_to)
    var currentAssignment = '{{ $issue->assigned_to ?? "" }}';
    var currentContact = '{{ $issue->assigned_to_contact ?? "" }}';
    $('#assigned_to_hidden').val(currentAssignment);
    $('#assigned_to_contact_hidden').val(currentContact);
    @php
        if (is_numeric($issue->assigned_to)) {
            $assignedEmployee = DB::table('employee_master')->where('pk', $issue->assigned_to)->first();
            $assignmentText = $assignedEmployee
                ? trim($assignedEmployee->first_name . ' ' . ($assignedEmployee->middle_name ?? '') . ' ' . $assignedEmployee->last_name) . ' (' . ($issue->assigned_to_contact ?? 'N/A') . ')'
                : 'Unknown (' . ($issue->assigned_to_contact ?? 'N/A') . ')';
        } else {
            $assignmentText = $issue->assigned_to . ' (' . ($issue->assigned_to_contact ?? 'N/A') . ')';
        }
    @endphp
    $('#current_assignment').val({!! json_encode($assignmentText) !!});
    @endif

    @if($canReassign && $isAssigned)
    @if(is_numeric($issue->assigned_to))
    $('#assigned_to_hidden').val('{{ $issue->assigned_to }}');
    $('#assigned_to_contact_hidden').val('{{ $issue->assigned_to_contact ?? "" }}');
    $('#display_phone').val('{{ $issue->assigned_to_contact ?? "N/A" }}');
    $('#phoneNumberSection').show();
    @else
    $('#assign_to_type').val('other');
    $('#otherFieldsSection').show();
    $('#phoneNumberSection').hide();
    @endif
    @endif

    // Handle assign_to_type change
    $('#assign_to_type').change(function() {
        var selectedValue = $(this).val();
        
        if (selectedValue === 'other') {
            // Show other fields section, hide phone display
            $('#otherFieldsSection').show();
            $('#phoneNumberSection').hide();
            $('#display_phone').val('');
            // Clear hidden fields
            $('#assigned_to_hidden').val('');
            $('#assigned_to_contact_hidden').val('');
        } else if (selectedValue !== '') {
            // Hide other fields section, show phone display
            $('#otherFieldsSection').hide();
            $('#phoneNumberSection').show();
            
            // Get data from selected option
            var selectedOption = $(this).find('option:selected');
            var name = selectedOption.data('name');
            var mobile = selectedOption.data('mobile');
            
            // Extract employee pk from value
            var employeePk = selectedValue;
            
            // Display phone number
            $('#display_phone').val(mobile || 'N/A');
            
            // Set hidden fields
            $('#assigned_to_hidden').val(employeePk || '');
            $('#assigned_to_contact_hidden').val(mobile || '');
        } else {
            // Hide both sections
            $('#otherFieldsSection').hide();
            $('#phoneNumberSection').hide();
            $('#display_phone').val('');
            // Clear hidden fields
            $('#assigned_to_hidden').val('');
            $('#assigned_to_contact_hidden').val('');
        }
    });

    // Before form submit: when "other" selected, validate and set hidden; when empty and re-assign, keep current (controller will keep existing)
    $('#updateStatusModal form').submit(function(e) {
        var assignToType = $('#assign_to_type').val();
        if (assignToType === 'other') {
            var otherName = $('#other_name').val().trim();
            var otherPhone = $('#other_phone').val().trim();
            if (otherName === '' || otherPhone === '') {
                e.preventDefault();
                alert('Please enter both member name and phone number.');
                return false;
            }
            $('#assigned_to_hidden').val('');
            $('#assigned_to_contact_hidden').val(otherPhone);
        }
    });
});
</script>
@endsection
