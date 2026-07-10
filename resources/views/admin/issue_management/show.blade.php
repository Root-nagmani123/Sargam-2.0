@extends('admin.layouts.master')

@section('title', 'Issue Details')

@push('styles')
<style>
/* =====================================================================
   Issue Details — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Scoped to .issue-show-page so nothing leaks to other pages.
   ===================================================================== */

/* Card header: title + status pill */
.issue-show-page .im-detail-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--ds-space-3);
    margin-bottom: var(--ds-space-3);
    padding-bottom: var(--ds-space-3);
    border-bottom: 1px solid var(--ds-line);
}
.issue-show-page .im-detail-title { font-size: 1.05rem; font-weight: 700; color: var(--ds-ink); margin: 0; }

/* Section heading inside the detail card */
.issue-show-page .im-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--ds-ink);
    margin: var(--ds-space-5) 0 var(--ds-space-3);
    padding-bottom: var(--ds-space-2);
    border-bottom: 1px solid var(--ds-line);
}
.issue-show-page .im-section-title:first-of-type { margin-top: 0; }

/* Label / value field pairs */
.issue-show-page .im-field { display: flex; flex-direction: column; gap: 0.2rem; }
.issue-show-page .im-field-label { font-size: 0.8125rem; font-weight: 500; color: var(--ds-ink-muted); }
.issue-show-page .im-field-value { font-size: 0.9375rem; font-weight: 500; color: var(--ds-ink); word-break: break-word; }

/* Free-text blocks (description, location, remarks, feedback) */
.issue-show-page .im-text {
    padding: 0.8rem 0.9rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    background: var(--ds-surface-2);
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--ds-ink);
    white-space: pre-wrap;
    word-break: break-word;
}
.issue-show-page .im-text--feedback {
    border-color: #b7e2c8;
    background: #eafaf0;
    color: #0f7b3e;
}
.issue-show-page .im-text strong { color: var(--ds-ink); font-weight: 600; }

/* Soft status / priority pills */
.issue-show-page .im-pill {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
}
.issue-show-page .im-pill--success   { color: #0f7b3e; background: #e3f5ea; }
.issue-show-page .im-pill--info      { color: #0d5bbd; background: #e6f0fd; }
.issue-show-page .im-pill--warning   { color: #9a6a00; background: #fff3d6; }
.issue-show-page .im-pill--secondary { color: #475467; background: #eef1f5; }

/* Sub-category chips */
.issue-show-page .im-chip {
    display: inline-block;
    padding: 0.25rem 0.7rem;
    border-radius: var(--ds-radius-1);
    background: #eef1f5;
    color: #475467;
    font-size: 0.8125rem;
    font-weight: 500;
    margin: 0 0.25rem 0.25rem 0;
}

/* Attachment thumbnails */
.issue-show-page .im-attach {
    display: block;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    overflow: hidden;
    background: #fff;
    transition: box-shadow .15s ease, border-color .15s ease;
}
.issue-show-page .im-attach:hover { border-color: var(--bs-primary); box-shadow: var(--ds-shadow-sm); }
.issue-show-page .im-attach img { display: block; height: 120px; width: 180px; object-fit: cover; }

/* Status-history table (matches the index table look) */
.issue-show-page .im-history-table { width: 100%; margin: 0; }
.issue-show-page .im-history-table thead th {
    background: var(--ds-surface-2);
    border-bottom: 1px solid var(--ds-line);
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 600;
    color: var(--ds-ink-muted);
    padding: 10px 14px;
    white-space: nowrap;
}
.issue-show-page .im-history-table tbody td {
    padding: 10px 14px;
    font-size: 0.9rem;
    color: var(--ds-ink);
    border-bottom: 1px solid var(--ds-line);
    vertical-align: middle;
}
.issue-show-page .im-history-table tbody tr:last-child td { border-bottom: 0; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid issue-show-page">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $isNodalOrAssigned = $issue->employee_master_pk == Auth::user()->user_id || $issue->assigned_to == Auth::user()->user_id;
        $isComplainant = $issue->created_by == Auth::user()->user_id;
        $isLogger = $issue->issue_logger == Auth::user()->user_id;
        $isCompleted = (int) $issue->issue_status === 2;
        $canUpdateStatus = $isNodalOrAssigned || ($isComplainant && $isCompleted) || ($isLogger && $isCompleted);
        $showReopenOnly = ($isComplainant || $isLogger) && $isCompleted;
        $canEdit = ($isComplainant || $isLogger) && !$isCompleted;

        $s = (int) $issue->issue_status;
        $statusClass = $s == 2 ? 'success' : ($s == 1 ? 'info' : ($s == 6 ? 'warning' : 'secondary'));
    @endphp

    {{-- Page header + primary actions --}}
    <x-breadcrum title="Issue Details">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('admin.issue-management.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">arrow_back</i>
                <span>Back to List</span>
            </a>
            @if($canUpdateStatus)
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                @if($showReopenOnly)
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">restart_alt</i>
                    <span>Reopen Issue</span>
                @else
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">published_with_changes</i>
                    <span>Update Status</span>
                @endif
            </button>
            @endif
            @if($canEdit)
                <a href="{{ route('admin.issue-management.edit', $issue->pk) }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                    <span>Edit Issue</span>
                </a>
            @endif
        </div>
    </x-breadcrum>

    <div class="ds-card">
        <div class="ds-card-body">

            <div class="im-detail-head">
                <h1 class="im-detail-title">Issue #{{ $issue->pk }}</h1>
                <span class="im-pill im-pill--{{ $statusClass }}">{{ $issue->status_label }}</span>
            </div>

            {{-- Issue information --}}
            <h2 class="im-section-title">Issue Information</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Issue ID</span>
                        <span class="im-field-value">#{{ $issue->pk }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Category</span>
                        <span class="im-field-value">{{ $issue->category->issue_category ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Sub-Categories</span>
                        <span class="im-field-value">
                            @forelse($issue->subCategoryMappings as $mapping)
                                <span class="im-chip">{{ $mapping->subCategory->issue_sub_category ?? '' }}</span>
                            @empty
                                N/A
                            @endforelse
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Created Date</span>
                        <span class="im-field-value">{{ $issue->created_date->format('d-m-Y H:i:s') }}</span>
                    </div>
                </div>
                @if($issue->clear_date)
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Resolved On</span>
                        <span class="im-field-value">{{ $issue->clear_date->format('d-m-Y H:i:s') }}</span>
                    </div>
                </div>
                @endif
            </div>

            {{-- People --}}
            <h2 class="im-section-title">People &amp; Assignment</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Created By</span>
                        <span class="im-field-value">{{ $issue->logger->name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Issue Logger</span>
                        <span class="im-field-value">{{ $issue->creator->name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Assigned To</span>
                        <span class="im-field-value">
                            @if($issue->assigned_to)
                                @php
                                    if (is_numeric($issue->assigned_to)) {
                                        $assignedEmployee = \DB::table('employee_master')->where('pk', $issue->assigned_to)->first();
                                        echo $assignedEmployee ? e(trim($assignedEmployee->first_name . ' ' . ($assignedEmployee->middle_name ?? '') . ' ' . $assignedEmployee->last_name)) : 'N/A';
                                    } else {
                                        echo e($issue->assigned_to);
                                    }
                                @endphp
                            @else
                                Not Assigned
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Assigned Person Contact Number</span>
                        <span class="im-field-value">{{ $issue->assigned_to_contact ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="im-field">
                        <span class="im-field-label">Nodal Officer</span>
                        <span class="im-field-value">{{ $issue->nodal_officer->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            <h2 class="im-section-title">Description</h2>
            <div class="im-text">{{ $issue->description ?: 'N/A' }}</div>

            {{-- Location --}}
            <h2 class="im-section-title">Location Details</h2>
            <div class="im-text">
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

            @if($issue->remark)
            <h2 class="im-section-title">Remarks</h2>
            <div class="im-text">{{ $issue->remark }}</div>
            @endif

            @if($issue->feedback)
            <h2 class="im-section-title">Feedback</h2>
            <div class="im-text im-text--feedback">{{ $issue->feedback }}</div>
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
            <h2 class="im-section-title">Attachments</h2>
            <div class="d-flex flex-wrap gap-3 align-items-start">
                @foreach($docPaths as $path)
                @php
                    $url = (str_starts_with(trim($path), 'http://') || str_starts_with(trim($path), 'https://'))
                        ? $path
                        : asset('storage/' . ltrim($path, '/'));
                @endphp
                <div class="d-inline-block text-center">
                    <a href="{{ $url }}" target="_blank" class="im-attach">
                        <img src="{{ $url }}" alt="Attachment">
                    </a>
                    <small class="d-block text-body-secondary mt-1">Image</small>
                </div>
                @endforeach
            </div>
            @endif

            @if($issue->statusHistory->count() > 0)
            <h2 class="im-section-title">Status History</h2>
            <div class="table-responsive">
                <table class="im-history-table">
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
                            $hs = (int) $history->issue_status;
                            $hsClass = $hs == 2 ? 'success' : ($hs == 1 ? 'info' : ($hs == 6 ? 'warning' : 'secondary'));
                        @endphp
                        <tr>
                            <td>{{ $history->issue_date->format('d-m-Y H:i:s') }}</td>
                            <td><span class="im-pill im-pill--{{ $hsClass }}">{{ $history->status_label }}</span></td>
                            <td>{{ $statusUpdatedBy?->name ?? 'System' }}</td>
                            <td>{{ $history->remarks ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
