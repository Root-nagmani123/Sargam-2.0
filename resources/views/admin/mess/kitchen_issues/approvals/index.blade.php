@extends('admin.layouts.master')

@section('title', 'Pending Approvals - Kitchen Issues - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Kitchen Issue Approvals" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-12">
                            <h4>Pending Kitchen Issue Approvals</h4>
                        </div>
                    </div>
                    <hr>

                    <div class="table-responsive">
                        <table class="table w-100 text-nowrap">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Bill No.</th>
                                    <th>Store/Mess</th>
                                    <th>Item</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Request Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingApprovals as $key => $issue)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $pendingApprovals->firstItem() + $key }}</td>
                                    <td>{{ $issue->bill_no ?? 'N/A' }}</td>
                                    <td>{{ $issue->storeMaster->store_name ?? 'N/A' }}</td>
                                    <td>{{ $issue->itemMaster->item_name ?? 'N/A' }}</td>
                                    <td>{{ $issue->client_full_name }}</td>
                                    <td>₹{{ number_format($issue->total_amount, 2) }}</td>
                                    <td>{{ $issue->request_date->format('d-m-Y') }}</td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-2" role="group">
                                            <a href="{{ route('admin.mess.kitchen-issue-approvals.show', $issue->pk) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:eye-bold" width="18"></iconify-icon>
                                                <span class="d-none d-md-inline">Review</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No pending approvals</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                            <div class="text-muted small mb-2">
                                Showing {{ $pendingApprovals->firstItem() ?? 0 }}
                                to {{ $pendingApprovals->lastItem() ?? 0 }}
                                of {{ $pendingApprovals->total() }} items
                            </div>
                            <div>
                                {{ $pendingApprovals->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
