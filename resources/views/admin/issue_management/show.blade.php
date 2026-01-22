@extends('admin.layouts.master')

@section('title', 'Issue Details - Sargam | Lal Bahadur')

@section('css')
<style>
.table {
    background-color: #fff !important;
}
.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
}
</style>
@endsection

@section('setup_content')
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
                        <a href="{{ route('admin.issue-management.edit', $issue->pk) }}" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Update Status
                        </a>
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
                                    <th>Priority</th>
                                    <td>
                                        <span class="badge bg-{{ $issue->priority->priority == 'High' ? 'danger' : ($issue->priority->priority == 'Medium' ? 'warning' : 'info') }}">
                                            {{ $issue->priority->priority ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Reproducibility</th>
                                    <td>{{ $issue->reproducibility->reproducibility_name ?? 'N/A' }}</td>
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
                                    <th>Behalf</th>
                                    <td>
                                        <span class="badge bg-{{ $issue->behalf == 0 ? 'primary' : 'secondary' }}">
                                            {{ $issue->behalf_label }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Created Date</th>
                                    <td>{{ $issue->created_date->format('d-m-Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Created By</th>
                                    <td>{{ $issue->creator->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Issue Logger</th>
                                    <td>{{ $issue->logger->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Assigned To</th>
                                    <td>{{ $issue->assigned_to ?? 'Not Assigned' }}</td>
                                </tr>
                                <tr>
                                    <th>Contact</th>
                                    <td>{{ $issue->assigned_to_contact ?? 'N/A' }}</td>
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

                    @if($issue->buildingMapping || $issue->hostelMapping)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Location Details</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    @if($issue->buildingMapping)
                                        <strong>Building:</strong> {{ $issue->buildingMapping->building->building_name ?? 'N/A' }}<br>
                                        <strong>Floor:</strong> {{ $issue->buildingMapping->floor_name ?? 'N/A' }}<br>
                                        <strong>Room:</strong> {{ $issue->buildingMapping->room_name ?? 'N/A' }}
                                    @elseif($issue->hostelMapping)
                                        <strong>Hostel:</strong> {{ $issue->hostelMapping->hostelBuilding->hostel_name ?? 'N/A' }}<br>
                                        <strong>Floor:</strong> {{ $issue->hostelMapping->floor_name ?? 'N/A' }}<br>
                                        <strong>Room:</strong> {{ $issue->hostelMapping->room_name ?? 'N/A' }}
                                    @endif
                                    @if($issue->location)
                                        <br><strong>Additional Location:</strong> {{ $issue->location }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

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

                    @if($issue->document)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Attached Document</h5>
                            <a href="{{ Storage::url($issue->document) }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="material-icons">download</i> Download Document
                            </a>
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
                                            <td>{{ $history->creator->name ?? 'System' }}</td>
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
@endsection
