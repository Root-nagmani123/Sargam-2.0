@extends('admin.layouts.master')

@section('title', 'Material Management Details - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Material Management Details" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">Material Management #{{ $kitchenIssue->pk }}</h4>
                <div>
                    @if($kitchenIssue->approve_status == 0)
                    <span class="badge bg-warning">Pending Approval</span>
                    @elseif($kitchenIssue->approve_status == 1)
                    <span class="badge bg-success">Approved</span>
                    @else
                    <span class="badge bg-danger">Rejected</span>
                    @endif
                </div>
            </div>
            <hr>

            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Bill No:</th>
                            <td>{{ $kitchenIssue->bill_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Store/Mess:</th>
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
                            <th width="40%">Payment Type:</th>
                            <td>{{ $kitchenIssue->payment_type_label }}</td>
                        </tr>
                        <tr>
                            <th>Client Name:</th>
                            <td>{{ $kitchenIssue->client_full_name }}</td>
                        </tr>
                        <tr>
                            <th>Issue Date:</th>
                            <td>{{ $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('d-m-Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Request Date:</th>
                            <td>{{ $kitchenIssue->request_date->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>{{ $kitchenIssue->status_label }}</td>
                        </tr>
                        <tr>
                            <th>Paid Status:</th>
                            <td>{{ $kitchenIssue->paid_status_label }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($kitchenIssue->remarks)
            <div class="row">
                <div class="col-12">
                    <h5>Remarks</h5>
                    <p class="border p-3 bg-light rounded">{{ $kitchenIssue->remarks }}</p>
                </div>
            </div>
            @endif

            @if($kitchenIssue->approvals->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <h5>Approval History</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Approver</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kitchenIssue->approvals as $approval)
                            <tr>
                                <td>{{ $approval->approval_level }}</td>
                                <td>{{ $approval->approver->first_name ?? '' }} {{ $approval->approver->last_name ?? '' }}</td>
                                <td>
                                    @if($approval->status == 0)
                                    <span class="badge bg-warning">Pending</span>
                                    @elseif($approval->status == 1)
                                    <span class="badge bg-success">Approved</span>
                                    @else
                                    <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>{{ $approval->remarks ?? 'N/A' }}</td>
                                <td>{{ $approval->approved_date ? $approval->approved_date->format('d-m-Y H:i') : 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <hr>
            <div class="text-end">
                @if($kitchenIssue->approve_status == 0)
                <a href="{{ route('admin.mess.material-management.edit', $kitchenIssue->pk) }}" class="btn btn-primary">
                    <iconify-icon icon="solar:pen-bold" width="18"></iconify-icon> Edit
                </a>
                @endif
                <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>

@endsection
