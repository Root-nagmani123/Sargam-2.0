@extends('admin.layouts.master')

@section('title', 'Update Issue Status - Sargam | Lal Bahadur')

@section('css')
<style>
.form-control, .form-select {
    background-color: #fff !important;
    color: #212529 !important;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Update Issue Status" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <h4 class="mb-3">Update Issue #{{ $issue->pk }} Status</h4>
                <hr>
                    <form action="{{ route('admin.issue-management.update', $issue->pk) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Status <span class="text-danger">*</span></label>
                                    <select name="issue_status" class="form-select" required>
                                        <option value="0" {{ $issue->issue_status == 0 ? 'selected' : '' }}>Reported</option>
                                        <option value="1" {{ $issue->issue_status == 1 ? 'selected' : '' }}>In Progress</option>
                                        <option value="2" {{ $issue->issue_status == 2 ? 'selected' : '' }}>Completed</option>
                                        <option value="3" {{ $issue->issue_status == 3 ? 'selected' : '' }}>Pending</option>
                                        <option value="6" {{ $issue->issue_status == 6 ? 'selected' : '' }}>Reopened</option>
                                    </select>
                                    @error('issue_status')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Assigned To</label>
                                    <input type="text" name="assigned_to" class="form-control" value="{{ $issue->assigned_to }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="assigned_to_contact" class="form-control" value="{{ $issue->assigned_to_contact }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remark" class="form-control" rows="4">{{ $issue->remark }}</textarea>
                            @error('remark')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="alert alert-info">
                            <h6>Issue Details</h6>
                            <strong>Category:</strong> {{ $issue->category->issue_category ?? 'N/A' }}<br>
                            <strong>Priority:</strong> {{ $issue->priority->priority ?? 'N/A' }}<br>
                            <strong>Description:</strong> {{ Str::limit($issue->description, 150) }}
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Issue</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
