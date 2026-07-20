@extends('admin.layouts.master')

@section('title', 'Issue Details')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* =====================================================================
   Issue detail — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   ===================================================================== */
.issue-section-title {
    font-size: 1rem; font-weight: 600; color: var(--ds-ink, #1f2937);
    margin: 0 0 var(--ds-space-3, 1rem); padding-bottom: var(--ds-space-2, 0.5rem);
    border-bottom: 1px solid var(--ds-line, #dee2e6);
    display: flex; align-items: center; gap: 0.5rem;
}
/* Read-only values render as label + value, not as a bordered th/td grid. */
.issue-field { padding: 0.7rem 0; border-bottom: 1px solid var(--ds-line, #eef2f6); }
.issue-label {
    display: block; font-size: 0.75rem; font-weight: 500;
    color: var(--ds-ink-muted, #667085); margin-bottom: 0.2rem;
}
.issue-value { display: block; font-size: 0.9375rem; color: var(--ds-ink, #1f2937); line-height: 1.4; }

/* Summary strip */
.issue-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--ds-space-3, 1rem); }
@media (max-width: 767.98px) { .issue-stats { grid-template-columns: 1fr; } }
.issue-stat {
    background: #fff; border: 1px solid var(--ds-line, #dee2e6);
    border-radius: var(--ds-radius-2, 8px); padding: 0.85rem 1.1rem;
}
.issue-stat-label { font-size: 0.75rem; font-weight: 500; color: var(--ds-ink-muted, #667085); margin-bottom: 0.35rem; }
.issue-stat-value { font-size: 0.9375rem; font-weight: 600; color: var(--ds-ink, #1f2937); line-height: 1.3; }

/* Long-form blocks (description / location / remarks / feedback) */
.issue-block {
    background: var(--ds-surface-2, #f8f9fa); border: 1px solid var(--ds-line, #dee2e6);
    border-radius: var(--ds-radius-2, 8px); padding: 1rem 1.15rem;
    font-size: 0.9375rem; color: var(--ds-ink, #1f2937); line-height: 1.55; white-space: pre-line;
}
.issue-block--feedback { background: #eaf7ef; border-color: #b7e2c6; }
.issue-attachment img {
    max-height: 120px; max-width: 180px; object-fit: cover;
    border: 1px solid var(--ds-line, #dee2e6); border-radius: var(--ds-radius-2, 8px);
}
</style>
@endpush

@section('content')
@php
    $isNodalOrAssigned = $issue->employee_master_pk == Auth::user()->user_id || $issue->assigned_to == Auth::user()->user_id;
    $isComplainant = $issue->created_by == Auth::user()->user_id;
    $isLogger = $issue->issue_logger == Auth::user()->user_id;
    $isCompleted = (int) $issue->issue_status === 2;
    $canUpdateStatus = $isNodalOrAssigned || ($isComplainant && $isCompleted) || ($isLogger && $isCompleted);
    $showReopenOnly = ($isComplainant || $isLogger) && $isCompleted;
    $canEdit = ($isComplainant || $isLogger) && !$isCompleted;

    $statusClass = match ((int) $issue->issue_status) {
        2 => 'success',
        1 => 'info',
        6 => 'warning',
        default => 'secondary',
    };

    // Assigned-to may hold an employee pk or a free-text name.
    $assignedLabel = 'Not Assigned';
    if ($issue->assigned_to) {
        if (is_numeric($issue->assigned_to)) {
            $assignedEmp = \DB::table('employee_master')->where('pk', $issue->assigned_to)->first();
            $assignedLabel = $assignedEmp
                ? trim($assignedEmp->first_name . ' ' . ($assignedEmp->middle_name ?? '') . ' ' . $assignedEmp->last_name)
                : 'N/A';
        } else {
            $assignedLabel = $issue->assigned_to;
        }
    }

    // Attachments may be a JSON array or a single path, across two columns.
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

<div class="container-fluid issue-show-page py-3">
    <x-breadcrum title="Issue Details">
        <div class="d-flex flex-wrap gap-2">
            @if($canEdit)
                <a href="{{ route('admin.issue-management.edit', $issue->pk) }}"
                   class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-4 rounded-1">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                    <span>Edit Issue</span>
                </a>
            @endif
            @if($canUpdateStatus)
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                    <i class="bi {{ $showReopenOnly ? 'bi-arrow-repeat' : 'bi-arrow-up-circle' }}" aria-hidden="true"></i>
                    <span>{{ $showReopenOnly ? 'Reopen Issue' : 'Update Status' }}</span>
                </button>
            @endif
        </div>
    </x-breadcrum>
    <x-session_message />

    <div class="ds-card">
        <div class="ds-card-body">

            {{-- Header: issue identity --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge rounded-1 bg-{{ $statusClass }}">{{ $issue->status_label }}</span>
                <span class="text-muted small">Issue ID: <code>{{ $issue->pk }}</code></span>
            </div>

            {{-- Summary strip --}}
            <div class="issue-stats mb-4">
                <div class="issue-stat">
                    <div class="issue-stat-label">Status</div>
                    <div class="issue-stat-value">
                        <span class="badge rounded-1 bg-{{ $statusClass }}">{{ $issue->status_label }}</span>
                    </div>
                </div>
                <div class="issue-stat">
                    <div class="issue-stat-label">Created Date</div>
                    <div class="issue-stat-value">{{ $issue->created_date ? $issue->created_date->format('d-m-Y H:i') : '--' }}</div>
                </div>
                <div class="issue-stat">
                    <div class="issue-stat-label">Resolved On</div>
                    <div class="issue-stat-value">{{ $issue->clear_date ? $issue->clear_date->format('d-m-Y H:i') : 'Not resolved' }}</div>
                </div>
            </div>

            {{-- ============ Issue Details ============ --}}
            <h6 class="issue-section-title">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">description</i>
                Issue Details
            </h6>
            <div class="row g-0 mb-4">
                <div class="col-md-6 pe-md-4">
                    <div class="issue-field">
                        <span class="issue-label">Category</span>
                        <span class="issue-value">{{ $issue->category->issue_category ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="issue-field">
                        <span class="issue-label">Priority</span>
                        <span class="issue-value">{{ $issue->priority->priority ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="issue-field">
                        <span class="issue-label">Sub-Categories</span>
                        <span class="issue-value">
                            @forelse($issue->subCategoryMappings as $mapping)
                                <span class="badge rounded-1 bg-secondary me-1">{{ $mapping->subCategory->issue_sub_category ?? '' }}</span>
                            @empty
                                --
                            @endforelse
                        </span>
                    </div>
                </div>
            </div>

            {{-- ============ People ============ --}}
            <h6 class="issue-section-title">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">group</i>
                People
            </h6>
            <div class="row g-0 mb-4">
                <div class="col-md-6 pe-md-4">
                    <div class="issue-field">
                        <span class="issue-label">Created By</span>
                        <span class="issue-value">{{ $issue->logger->name ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="issue-field">
                        <span class="issue-label">Issue Logger</span>
                        <span class="issue-value">{{ $issue->creator->name ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 pe-md-4">
                    <div class="issue-field">
                        <span class="issue-label">Assigned To</span>
                        <span class="issue-value">{{ $assignedLabel }}</span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="issue-field">
                        <span class="issue-label">Assigned Person Contact</span>
                        <span class="issue-value">{{ $issue->assigned_to_contact ?: '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 pe-md-4">
                    <div class="issue-field">
                        <span class="issue-label">Nodal Officer</span>
                        <span class="issue-value">{{ $issue->nodal_officer->name ?? '--' }}</span>
                    </div>
                </div>
            </div>

            {{-- ============ Description ============ --}}
            <h6 class="issue-section-title">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">notes</i>
                Description
            </h6>
            <div class="issue-block mb-4">{{ $issue->description ?: '--' }}</div>

            {{-- ============ Location ============ --}}
            <h6 class="issue-section-title">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">location_on</i>
                Location Details
            </h6>
            <div class="row g-0 mb-4">
                @php
                    // Resolve the location from whichever mapping is present, falling
                    // back to the controller-supplied $locationFallback.
                    $locLabel = 'Hostel';
                    $locName = 'N/A';
                    $locFloor = 'N/A';
                    $locRoom = 'N/A';

                    if ($issue->location === 'O' && !empty($locationFallback)) {
                        $locLabel = 'Building';
                        $locName = $locationFallback['name'];
                        $locFloor = $locationFallback['floor'];
                        $locRoom = $locationFallback['room'];
                    } elseif ($issue->buildingMapping) {
                        $locLabel = 'Building';
                        $locName = trim($issue->buildingMapping->building->building_name ?? '') ?: 'N/A';
                        $locFloor = ($issue->buildingMapping->floor_name ?? '') !== '' ? $issue->buildingMapping->floor_name : 'N/A';
                        $locRoom = ($issue->buildingMapping->room_name ?? '') !== '' ? $issue->buildingMapping->room_name : 'N/A';
                    } elseif ($issue->hostelMapping) {
                        $locLabel = 'Hostel';
                        if ($issue->hostelMapping->hostelBuilding) {
                            $locName = trim($issue->hostelMapping->hostelBuilding->hostel_name ?? $issue->hostelMapping->hostelBuilding->building_name ?? '') ?: 'N/A';
                        } else {
                            $hostelRow = \DB::table('hostel_building_master')->where('pk', $issue->hostelMapping->hostel_building_master_pk)->first();
                            $locName = $hostelRow ? (trim($hostelRow->hostel_name ?? $hostelRow->building_name ?? '') ?: 'N/A') : 'N/A';
                        }
                        $locFloor = ($issue->hostelMapping->floor_name ?? '') !== '' ? $issue->hostelMapping->floor_name : 'N/A';
                        $locRoom = ($issue->hostelMapping->room_name ?? '') !== '' ? $issue->hostelMapping->room_name : 'N/A';
                    } elseif (!empty($locationFallback)) {
                        $locLabel = $locationFallback['type'] === 'building'
                            ? 'Building'
                            : ($locationFallback['type'] === 'residential' ? 'Residential' : 'Hostel');
                        $locName = $locationFallback['name'];
                        $locFloor = $locationFallback['floor'];
                        $locRoom = $locationFallback['room'];
                    }
                @endphp
                <div class="col-md-3 pe-md-3">
                    <div class="issue-field">
                        <span class="issue-label">{{ $locLabel }}</span>
                        <span class="issue-value">{{ $locName }}</span>
                    </div>
                </div>
                <div class="col-md-3 px-md-3">
                    <div class="issue-field">
                        <span class="issue-label">Floor</span>
                        <span class="issue-value">{{ $locFloor }}</span>
                    </div>
                </div>
                <div class="col-md-3 px-md-3">
                    <div class="issue-field">
                        <span class="issue-label">Room</span>
                        <span class="issue-value">{{ $locRoom }}</span>
                    </div>
                </div>
                <div class="col-md-3 ps-md-3">
                    <div class="issue-field">
                        <span class="issue-label">Additional Location</span>
                        <span class="issue-value">{{ trim($issue->location ?? '') ?: '--' }}</span>
                    </div>
                </div>
            </div>

            @if($issue->remark)
                <h6 class="issue-section-title">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">comment</i>
                    Remarks
                </h6>
                <div class="issue-block mb-4">{{ $issue->remark }}</div>
            @endif

            @if($issue->feedback)
                <h6 class="issue-section-title">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">thumb_up</i>
                    Feedback
                </h6>
                <div class="issue-block issue-block--feedback mb-4">{{ $issue->feedback }}</div>
            @endif

            @if(count($docPaths) > 0)
                <h6 class="issue-section-title">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">attach_file</i>
                    Attachments
                </h6>
                <div class="d-flex flex-wrap gap-3 align-items-start mb-4">
                    @foreach($docPaths as $path)
                        @php
                            $trimmed = trim($path);
                            $url = (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://'))
                                ? $path
                                : asset('storage/' . ltrim($path, '/'));
                        @endphp
                        <div class="issue-attachment">
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="d-block text-decoration-none">
                                <img src="{{ $url }}" alt="Attachment">
                            </a>
                            <small class="d-block text-muted text-center mt-1">Attachment</small>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($issue->statusHistory->count() > 0)
                <h6 class="issue-section-title">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">history</i>
                    Status History
                </h6>
                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle programme-dt-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date &amp; Time</th>
                                    <th>Status</th>
                                    <th>Updated By</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($issue->statusHistory as $history)
                                    @php
                                        $statusUpdatedBy = \App\Models\EmployeeMaster::findByIdOrPkOld($history->created_by);
                                        $historyClass = match ((int) $history->issue_status) {
                                            2 => 'success',
                                            1 => 'info',
                                            6 => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $history->issue_date ? $history->issue_date->format('d-m-Y H:i') : '--' }}</td>
                                        <td><span class="badge rounded-1 bg-{{ $historyClass }}">{{ $history->status_label }}</span></td>
                                        <td>{{ $statusUpdatedBy?->name ?? 'System' }}</td>
                                        <td class="text-wrap" style="max-width:360px;">{{ $history->remarks ?: '--' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
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
</script>
@endsection
