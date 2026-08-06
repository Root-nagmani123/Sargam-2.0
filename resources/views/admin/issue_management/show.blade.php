@extends('admin.layouts.master')

@section('title', 'View Issue')

@push('styles')
{{-- Shared Centcom chrome — same file the queues and master grids use. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $isNodalOrAssigned = $issue->employee_master_pk == Auth::user()->user_id || $issue->assigned_to == Auth::user()->user_id;
    $isComplainant = $issue->created_by == Auth::user()->user_id;
    $isLogger = $issue->issue_logger == Auth::user()->user_id;
    $isCompleted = (int) $issue->issue_status === 2;
    $canUpdateStatus = $isNodalOrAssigned || ($isComplainant && $isCompleted) || ($isLogger && $isCompleted);
    $showReopenOnly = ($isComplainant || $isLogger) && $isCompleted;
    $canEdit = ($isComplainant || $isLogger) && !$isCompleted;

    $stateClass = [
        0 => 'ic-state--reported',
        1 => 'ic-state--in-progress',
        2 => 'ic-state--completed',
        3 => 'ic-state--pending',
        6 => 'ic-state--reopened',
    ];

    // Location block — mirrors the original resolution order (explicit mapping
    // first, then the controller's DB fallback) but yields one array to render.
    $locationLabel = 'Hostel Name';
    $locationName = 'N/A';
    $locationFloor = 'N/A';
    $locationRoom = 'N/A';

    if ($issue->location === 'O' && !empty($locationFallback)) {
        $locationLabel = 'Building';
        $locationName = $locationFallback['name'];
        $locationFloor = $locationFallback['floor'];
        $locationRoom = $locationFallback['room'];
    } elseif ($issue->buildingMapping) {
        $locationLabel = 'Building';
        $locationName = trim($issue->buildingMapping->building->building_name ?? '') ?: 'N/A';
        $locationFloor = filled($issue->buildingMapping->floor_name) ? $issue->buildingMapping->floor_name : 'N/A';
        $locationRoom = filled($issue->buildingMapping->room_name) ? $issue->buildingMapping->room_name : 'N/A';
    } elseif ($issue->hostelMapping) {
        $locationLabel = 'Hostel Name';
        if ($issue->hostelMapping->hostelBuilding) {
            $locationName = trim($issue->hostelMapping->hostelBuilding->hostel_name ?? $issue->hostelMapping->hostelBuilding->building_name ?? '') ?: 'N/A';
        } else {
            $hostelRow = \DB::table('hostel_building_master')->where('pk', $issue->hostelMapping->hostel_building_master_pk)->first();
            $locationName = $hostelRow ? (trim($hostelRow->hostel_name ?? $hostelRow->building_name ?? '') ?: 'N/A') : 'N/A';
        }
        $locationFloor = filled($issue->hostelMapping->floor_name) ? $issue->hostelMapping->floor_name : 'N/A';
        $locationRoom = filled($issue->hostelMapping->room_name) ? $issue->hostelMapping->room_name : 'N/A';
    } elseif (!empty($locationFallback)) {
        $locationLabel = $locationFallback['type'] === 'building'
            ? 'Building'
            : ($locationFallback['type'] === 'residential' ? 'Residential' : 'Hostel Name');
        $locationName = $locationFallback['name'];
        $locationFloor = $locationFallback['floor'];
        $locationRoom = $locationFallback['room'];
    }

    // Attachments — `document` may be a JSON array or a single path; `complaint_img` is JSON.
    $docPaths = [];
    $d = $issue->document ?? '';
    $cimg = $issue->complaint_img ?? '';
    if (!empty($d)) {
        $docPaths = str_starts_with(trim($d), '[') ? (json_decode($d, true) ?: []) : [$d];
    }
    if (!empty($cimg)) {
        $decoded = is_string($cimg) ? json_decode($cimg, true) : $cimg;
        if (is_array($decoded)) {
            $docPaths = array_merge($docPaths, $decoded);
        }
    }
    $docPaths = array_values(array_filter($docPaths));
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="View Issue">
        <div class="d-flex flex-wrap gap-2">
            @if($canUpdateStatus)
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                    @if($showReopenOnly)
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i><span>Reopen Issue</span>
                    @else
                        <i class="bi bi-arrow-up-circle" aria-hidden="true"></i><span>Update Status</span>
                    @endif
                </button>
                {{-- Deep link from the grid's "Update Status" action. Inside the
                     $canUpdateStatus gate on purpose: a user who may not update the
                     issue must not get the modal just by editing the URL. --}}
                @if(request('action') === 'update-status')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var el = document.getElementById('updateStatusModal');
                        if (el && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(el).show();
                        }
                    });
                </script>
                @endif
            @endif
            @if($canEdit)
                <a href="{{ route('admin.issue-management.edit', $issue->pk) }}"
                   class="btn programme-dt-btn-columns border-0 text-primary">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit Issue</span>
                </a>
            @endif
        </div>
    </x-breadcrum>

    <x-session_message />

    {{-- Hero: ticket number + headline facts + current state --}}
    <div class="ic-hero d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h2 class="ic-hero__id">Issue #{{ $issue->pk }}</h2>
            <div class="ic-facts ic-facts--hero">
                <div>
                    <span class="ic-fact__label">Category</span>
                    <span class="ic-fact__value">{{ $issue->category->issue_category ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="ic-fact__label">Created on</span>
                    <span class="ic-fact__value">{{ optional($issue->created_date)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        <span class="ic-state {{ $stateClass[(int) $issue->issue_status] ?? 'ic-state--reported' }}">
            {{ $issue->status_label }}
        </span>
    </div>

    {{-- Facts --}}
    <div class="ic-card">
        <div class="ic-card__body">
            <div class="ic-facts">
                <div>
                    <span class="ic-fact__label">Issue ID</span>
                    <span class="ic-fact__value">{{ $issue->pk }}</span>
                </div>
                <div>
                    <span class="ic-fact__label">Category</span>
                    <span class="ic-fact__value">{{ $issue->category->issue_category ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="ic-fact__label">Sub-Categories</span>
                    <span class="ic-fact__value">
                        @forelse($issue->subCategoryMappings as $mapping)
                            {{ $mapping->subCategory->issue_sub_category ?? '' }}@if(!$loop->last), @endif
                        @empty
                            NA
                        @endforelse
                    </span>
                </div>
                <div>
                    <span class="ic-fact__label">Created on</span>
                    <span class="ic-fact__value">{{ optional($issue->created_date)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                </div>
                {{-- created_by is the complainant, issue_logger is whoever filed it.
                     These two labels used to be mapped to each other's relation. --}}
                <div>
                    <span class="ic-fact__label">Created By</span>
                    <span class="ic-fact__value">{{ $issue->creator->name ?? 'NA' }}</span>
                </div>
                <div>
                    <span class="ic-fact__label">Issue Logger</span>
                    <span class="ic-fact__value">{{ $issue->logger->name ?? 'NA' }}</span>
                </div>
                <div>
                    <span class="ic-fact__label">Assigned To</span>
                    <span class="ic-fact__value">
                        @if($issue->assigned_to)
                            @php
                                if (is_numeric($issue->assigned_to)) {
                                    $assignedEmployee = \DB::table('employee_master')->where('pk', $issue->assigned_to)->first();
                                    echo $assignedEmployee
                                        ? e(trim($assignedEmployee->first_name . ' ' . ($assignedEmployee->middle_name ?? '') . ' ' . $assignedEmployee->last_name))
                                        : 'NA';
                                } else {
                                    echo e($issue->assigned_to);
                                }
                            @endphp
                        @else
                            Not Assigned
                        @endif
                    </span>
                </div>
                <div>
                    <span class="ic-fact__label">Assignee Contact</span>
                    <span class="ic-fact__value">{{ $issue->assigned_to_contact ?? 'NA' }}</span>
                </div>
                <div>
                    <span class="ic-fact__label">Nodal Officer</span>
                    <span class="ic-fact__value">{{ $issue->nodal_officer->name ?? 'NA' }}</span>
                </div>
                @if($issue->clear_date)
                <div>
                    <span class="ic-fact__label">Resolved On</span>
                    <span class="ic-fact__value">{{ $issue->clear_date->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Description + Location, side by side --}}
    <div class="row g-3 mt-0">
        <div class="col-12 col-lg-6">
            <div class="ic-card h-100" style="margin-top:0;">
                <div class="ic-card__body">
                    <h3 class="ic-card__title">Description</h3>
                    <p class="mb-0" style="font-size:0.875rem; color:#475467;">{{ $issue->description ?: 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="ic-card h-100" style="margin-top:0;">
                <div class="ic-card__body">
                    <h3 class="ic-card__title">Location Details</h3>
                    <div class="ic-facts" style="grid-template-columns:repeat(2,minmax(0,1fr));">
                        <div>
                            <span class="ic-fact__label">{{ $locationLabel }}</span>
                            <span class="ic-fact__value">{{ $locationName }}</span>
                        </div>
                        <div>
                            <span class="ic-fact__label">Floor</span>
                            <span class="ic-fact__value">{{ $locationFloor }}</span>
                        </div>
                        <div>
                            <span class="ic-fact__label">Room</span>
                            <span class="ic-fact__value">{{ $locationRoom }}</span>
                        </div>
                        <div>
                            <span class="ic-fact__label">Additional Location</span>
                            <span class="ic-fact__value">{{ trim($issue->location ?? '') ?: 'NA' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($issue->remark)
    <div class="ic-card">
        <div class="ic-card__body">
            <h3 class="ic-card__title">Remarks</h3>
            <p class="mb-0" style="font-size:0.875rem; color:#475467;">{{ $issue->remark }}</p>
        </div>
    </div>
    @endif

    @if($issue->feedback)
    <div class="ic-card">
        <div class="ic-card__body">
            <h3 class="ic-card__title">Feedback</h3>
            <p class="mb-0" style="font-size:0.875rem; color:#475467;">{{ $issue->feedback }}</p>
        </div>
    </div>
    @endif

    @if(count($docPaths) > 0)
    <div class="ic-card">
        <div class="ic-card__body">
            <h3 class="ic-card__title">Attachments</h3>
            <div class="d-flex flex-wrap gap-3 align-items-start">
                @foreach($docPaths as $path)
                    @php
                        $url = (str_starts_with(trim($path), 'http://') || str_starts_with(trim($path), 'https://'))
                            ? $path
                            : asset('storage/' . ltrim($path, '/'));
                    @endphp
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="d-block text-decoration-none">
                        <img src="{{ $url }}" alt="Attachment" class="rounded-3 border"
                             style="max-height: 120px; max-width: 180px; object-fit: cover;">
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($issue->statusHistory->count() > 0)
    <div class="ic-card" style="margin-top: 1rem;">
        <div class="ic-card__body">
            <h3 class="ic-card__title">Status History</h3>
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Sorted in the browser: the whole history is already on the page
                         (a handful of rows), so there is nothing to fetch. --}}
                    <table id="icHistoryTable" data-sargam-dt-ui="false"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table ic-history-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <button type="button" class="ic-sort ic-sort-btn" data-sort-index="0" data-sort-type="date">
                                        Date &amp; Time <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th scope="col" class="ic-th-center">Status</th>
                                <th scope="col">
                                    <button type="button" class="ic-sort ic-sort-btn" data-sort-index="2" data-sort-type="text">
                                        Updated By <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th scope="col">
                                    <button type="button" class="ic-sort ic-sort-btn" data-sort-index="3" data-sort-type="text">
                                        Remarks <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issue->statusHistory as $history)
                                @php
                                    $statusUpdatedBy = \App\Models\EmployeeMaster::findByIdOrPkOld($history->created_by);
                                @endphp
                                <tr>
                                    <td data-sort-value="{{ optional($history->issue_date)->format('Y-m-d H:i:s') }}">
                                        {{ optional($history->issue_date)->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="ic-td-center">
                                        <span class="ic-state {{ $stateClass[(int) $history->issue_status] ?? 'ic-state--reported' }}">
                                            {{ $history->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $statusUpdatedBy?->name ?? 'System' }}</td>
                                    <td class="ic-col-wrap">{{ $history->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
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
                        $canReassign = $isNodalOfficer && !$isCompleted; // Re-assign not allowed for closed (Completed) issues
                        $canOnlyReopen = $isComplainant && $isCompleted;

                        // Determine latest status from history (most recent first),
                        // fall back to main issue_status if no history exists.
                        $latestStatus = (int) ($issue->statusHistory->first()->issue_status ?? $issue->issue_status);
                    @endphp

                    @if(($issue->created_by == Auth::user()->user_id || $issue->issue_logger == Auth::user()->user_id) && $issue->issue_status === 2)
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Reopen:</strong> As the complainant, you can reopen this completed issue. Add a remark (optional) and submit.
                    </div>
                    <input type="hidden" name="issue_status" value="6">
                    @else
                    <!-- Assignment Locked: non-nodal (assigned person) or closed issue (re-assign restricted) -->
                    @if($isAssigned && !$canReassign)
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        @if($isCompleted)
                        <strong>Re-assign restricted:</strong> Assignment cannot be changed for closed (Completed) issues.
                        @else
                        <strong>Assignment Locked:</strong> This issue has been assigned. You can only update the status and remarks.
                        @endif
                    </div>
                    @endif
                    @if($isAssigned && $canReassign)
                    <div class="alert alert-secondary mb-3">
                        <i class="bi bi-person-gear"></i>
                        <strong>Re-assign:</strong> As nodal officer, you can change the assigned person below if needed.
                    </div>
                    @endif

                    @php
                        // After Reopen (6), all status options stay enabled so user can set any status again.
                        // Use the latest status from history so this works even if the main column lags.
                        // Additionally, allow Nodal Officer to always change to any status.
                        $disableStatusOptions = !$isNodalOfficer && $latestStatus !== 6;
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
                            <input type="tel" name="other_phone" class="form-control" id="other_phone" placeholder="Enter 10 digit mobile number (cannot start with 6)" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" title="Enter 10 digit mobile number. Cannot start with 6." value="{{ $isAssigned && !is_numeric($issue->assigned_to) ? ($issue->assigned_to_contact ?? '') : '' }}">
                            
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

    // Allow only digits in Other phone number
    $('#other_phone').on('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 10) this.value = this.value.slice(0, 10);
    });

    // Before form submit: when "other" selected, validate and set hidden; when empty and re-assign, keep current (controller will keep existing)
    $('#updateStatusModal form').submit(function(e) {
        var assignToType = $('#assign_to_type').val();
        if (assignToType === 'other') {
            var otherName = $('#other_name').val().trim();
            var otherPhone = $('#other_phone').val().trim();
            if (otherName === '') {
                e.preventDefault();
                alert('Please enter member name.');
                return false;
            }
            if (otherPhone === '') {
                e.preventDefault();
                alert('Please enter phone number.');
                return false;
            }
            if (!/^[0-9]{10}$/.test(otherPhone)) {
                e.preventDefault();
                alert('Phone number must be exactly 10 digits (numbers only).');
                return false;
            }
            if (otherPhone.charAt(0) === '6') {
                e.preventDefault();
                alert('Mobile number cannot start with 6.');
                return false;
            }
            $('#assigned_to_hidden').val('');
            $('#assigned_to_contact_hidden').val(otherPhone);
        }
    });
});

/* ── Status History: client-side sort ──
   The whole history is already rendered, so sorting is a DOM reorder. Dates use
   the ISO value in data-sort-value; text falls back to the cell's own text. */
$(function () {
    var $table = $('#icHistoryTable');
    if (!$table.length) { return; }

    $table.on('click', '.ic-sort-btn', function () {
        var $btn = $(this);
        var idx = parseInt($btn.data('sort-index'), 10);
        // Flip only when THIS column is already sorted ascending; a fresh column
        // starts ascending. (Reading a class that is cleared further down would
        // make every click sort ascending again.)
        var asc = !$btn.hasClass('is-asc');

        var rows = $table.find('tbody tr').get();
        rows.sort(function (a, b) {
            var av = $(a).children().eq(idx);
            var bv = $(b).children().eq(idx);
            var x = (av.attr('data-sort-value') || av.text()).trim().toLowerCase();
            var y = (bv.attr('data-sort-value') || bv.text()).trim().toLowerCase();
            if (x === y) { return 0; }
            return (x < y ? -1 : 1) * (asc ? 1 : -1);
        });
        $table.children('tbody').append(rows);

        $table.find('.ic-sort-btn').removeClass('is-active is-asc')
              .find('i').attr('class', 'bi bi-arrow-down-up');
        $btn.addClass('is-active').toggleClass('is-asc', asc)
            .find('i').attr('class', asc ? 'bi bi-caret-up-fill' : 'bi bi-caret-down-fill');
    });
});
</script>
@endsection
