@extends('admin.layouts.master')

@section('title', 'Approve Material Management - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Material Management Approval" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Review Material Management #{{ $kitchenIssue->pk }}</h4>
            <hr>

            <!-- Issue Details -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Store/Mess:</th>
                            <td>{{ $kitchenIssue->storeMaster->store_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Item:</th>
                            <td>{{ $kitchenIssue->itemMaster->item_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Quantity:</th>
                            <td>{{ $kitchenIssue->quantity }}</td>
                        </tr>
                        <tr>
                            <th>Unit Price:</th>
                            <td>₹{{ number_format($kitchenIssue->unit_price, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Total Amount:</th>
                            <td><strong>₹{{ number_format($kitchenIssue->total_amount, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Client Name:</th>
                            <td>{{ $kitchenIssue->client_full_name }}</td>
                        </tr>
                        <tr>
                            <th>Payment Type:</th>
                            <td>{{ $kitchenIssue->payment_type_label }}</td>
                        </tr>
                        <tr>
                            <th>Issue Date:</th>
                            <td>{{ $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('d-m-Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Request Date:</th>
                            <td>{{ $kitchenIssue->request_date->format('d-m-Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($kitchenIssue->remarks)
            <div class="row mb-4">
                <div class="col-12">
                    <h5>Remarks</h5>
                    <p class="border p-3 bg-light rounded">{{ $kitchenIssue->remarks }}</p>
                </div>
            </div>
            @endif

            <hr>

            <!-- Approval Actions -->
            <div class="row">
                <div class="col-md-6">
                    <form action="{{ route('admin.mess.material-management-approvals.approve', $kitchenIssue->pk) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="approve_remarks" class="form-label">Approval Remarks (Optional)</label>
                            <textarea class="form-control" id="approve_remarks" name="remarks" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this record?');">
                            <iconify-icon icon="solar:check-circle-bold" width="20"></iconify-icon> Approve
                        </button>
                    </form>
                </div>

                <div class="col-md-6">
                    <form action="{{ route('admin.mess.material-management-approvals.reject', $kitchenIssue->pk) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="reject_remarks" class="form-label">Rejection Remarks <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reject_remarks" name="remarks" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this record?');">
                            <iconify-icon icon="solar:close-circle-bold" width="20"></iconify-icon> Reject
                        </button>
                    </form>
                </div>
            </div>

            <hr>
            <div class="text-end">
                <a href="{{ route('admin.mess.material-management-approvals.index') }}" class="btn btn-secondary">Back to Approvals</a>
            </div>
        </div>
    </div>
</div>

@endsection
