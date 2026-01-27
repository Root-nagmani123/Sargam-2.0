@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:wallet-money-bold" class="me-2"></iconify-icon>
            Finance Bookings
        </h5>
        <a href="{{ route('admin.mess.finance-bookings.create') }}" class="btn btn-primary btn-sm">
            <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
            Add Booking
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Booking Number</th>
                        <th>Booking Date</th>
                        <th>Transaction</th>
                        <th>Account Head</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_number ?? 'N/A' }}</td>
                            <td>{{ $booking->booking_date ? date('d-M-Y', strtotime($booking->booking_date)) : 'N/A' }}</td>
                            <td>{{ $booking->inboundTransaction->grn_number ?? 'N/A' }}</td>
                            <td>{{ $booking->account_head ?? 'N/A' }}</td>
                            <td>₹{{ number_format($booking->amount ?? 0, 2) }}</td>
                            <td>
                                @if($booking->status == 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($booking->status == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                            </td>
                            <td>{{ $booking->approver->name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.mess.finance-bookings.show', $booking->id) }}" 
                                   class="btn btn-sm btn-info" title="View">
                                    <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                </a>
                                @if($booking->status == 'pending')
                                    <a href="{{ route('admin.mess.finance-bookings.edit', $booking->id) }}" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                    </a>
                                    <form action="{{ route('admin.mess.finance-bookings.approve', $booking->id) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve"
                                                onclick="return confirm('Approve this booking?');">
                                            <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.mess.finance-bookings.reject', $booking->id) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="Reject"
                                                onclick="return confirm('Reject this booking?');">
                                            <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <iconify-icon icon="solar:wallet-money-bold" style="font-size: 48px;"></iconify-icon>
                                <p class="mt-2">No finance bookings found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3">
            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection
