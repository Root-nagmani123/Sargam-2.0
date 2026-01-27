@extends('admin.layouts.master')

@section('title', 'Kitchen Issues - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Kitchen Issues" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Kitchen Issues Management</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('admin.mess.kitchen-issues.create') }}"
                                        class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New Kitchen Issue
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Store/Mess</label>
                            <select name="store_id" class="form-select">
                                <option value="">All Stores</option>
                                @foreach($stores as $store)
                                <option value="{{ $store->pk }}" {{ request('store_id') == $store->pk ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Processing</option>
                                <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Approved</option>
                                <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Rejected</option>
                                <option value="4" {{ request('status') === '4' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Approval Status</label>
                            <select name="approve_status" class="form-select">
                                <option value="">All</option>
                                <option value="0" {{ request('approve_status') === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="1" {{ request('approve_status') === '1' ? 'selected' : '' }}>Approved</option>
                                <option value="2" {{ request('approve_status') === '2' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table w-100 text-nowrap">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Bill No.</th>
                                    <th>Store/Mess</th>
                                    <th>Item</th>
                                    <th>Client</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Approval</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kitchenIssues as $key => $issue)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $kitchenIssues->firstItem() + $key }}</td>
                                    <td>{{ $issue->bill_no ?? 'N/A' }}</td>
                                    <td>{{ $issue->storeMaster->store_name ?? 'N/A' }}</td>
                                    <td>{{ $issue->itemMaster->item_name ?? 'N/A' }}</td>
                                    <td>{{ $issue->client_full_name }}</td>
                                    <td>{{ $issue->quantity }}</td>
                                    <td>₹{{ number_format($issue->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $issue->payment_type_label }}</span>
                                    </td>
                                    <td>
                                        @if($issue->status == 0)
                                        <span class="badge bg-warning">Pending</span>
                                        @elseif($issue->status == 1)
                                        <span class="badge bg-primary">Processing</span>
                                        @elseif($issue->status == 2)
                                        <span class="badge bg-success">Approved</span>
                                        @elseif($issue->status == 3)
                                        <span class="badge bg-danger">Rejected</span>
                                        @else
                                        <span class="badge bg-success">Completed</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($issue->approve_status == 0)
                                        <span class="badge bg-warning">Pending</span>
                                        @elseif($issue->approve_status == 1)
                                        <span class="badge bg-success">Approved</span>
                                        @else
                                        <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ $issue->request_date->format('d-m-Y') }}</td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-2" role="group">
                                            <a href="{{ route('admin.mess.kitchen-issues.show', $issue->pk) }}"
                                                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:eye-bold" width="18"></iconify-icon>
                                                <span class="d-none d-md-inline">View</span>
                                            </a>

                                            @if($issue->approve_status == 0)
                                            <a href="{{ route('admin.mess.kitchen-issues.edit', $issue->pk) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:pen-bold" width="18"></iconify-icon>
                                                <span class="d-none d-md-inline">Edit</span>
                                            </a>

                                            @if($issue->send_for_approval == 0)
                                            <form action="{{ route('admin.mess.kitchen-issues.send-for-approval', $issue->pk) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-success d-flex align-items-center gap-1"
                                                    onclick="return confirm('Send this issue for approval?');">
                                                    <iconify-icon icon="solar:check-circle-bold" width="18"></iconify-icon>
                                                    <span class="d-none d-md-inline">Send for Approval</span>
                                                </button>
                                            </form>
                                            @endif

                                            <form action="{{ route('admin.mess.kitchen-issues.destroy', $issue->pk) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this kitchen issue?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                                                    <iconify-icon icon="solar:trash-bin-trash-bold" width="18"></iconify-icon>
                                                    <span class="d-none d-md-inline">Delete</span>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center">No kitchen issues found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                            <div class="text-muted small mb-2">
                                Showing {{ $kitchenIssues->firstItem() ?? 0 }}
                                to {{ $kitchenIssues->lastItem() ?? 0 }}
                                of {{ $kitchenIssues->total() }} items
                            </div>
                            <div>
                                {{ $kitchenIssues->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
