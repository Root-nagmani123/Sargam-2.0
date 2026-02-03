@extends('admin.layouts.master')
@section('title', 'Finance Bookings')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Finance Bookings</h4>
                <a href="{{ route('admin.mess.finance-bookings.create') }}" class="btn btn-primary">
                    Add Booking
                </a>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff; border-color: #af2910;">#</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Booking Number</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff; border-color: #af2910;">Date</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Invoice</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">User</th>
                            <th style="width: 130px; background-color: #af2910; color: #fff; border-color: #af2910;">Amount</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff; border-color: #af2910;">Status</th>
                            <th style="width: 220px; background-color: #af2910; color: #fff; border-color: #af2910;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $booking->booking_number ?? '-' }}</div>
                                </td>
                                <td>{{ $booking->booking_date ? date('d-M-Y', strtotime($booking->booking_date)) : '-' }}</td>
                                <td>{{ $booking->invoice ? ($booking->invoice->invoice_no ?? 'INV-' . $booking->invoice->id) : '-' }}</td>
                                <td>{{ $booking->user ? (trim(($booking->user->first_name ?? '') . ' ' . ($booking->user->last_name ?? '')) ?: $booking->user->user_name) : '-' }}</td>
                                <td>₹{{ number_format($booking->amount ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $booking->status == 'approved' ? 'success' : ($booking->status == 'rejected' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($booking->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('admin.mess.finance-bookings.show', $booking->id) }}" 
                                           class="btn btn-sm btn-info" title="View">View</a>
                                        @if($booking->status == 'pending')
                                            <a href="{{ route('admin.mess.finance-bookings.edit', $booking->id) }}" 
                                               class="btn btn-sm btn-warning" title="Edit">Edit</a>
                                            <form action="{{ route('admin.mess.finance-bookings.approve', $booking->id) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve"
                                                        onclick="return confirm('Approve this booking?');">Approve</button>
                                            </form>
                                            <form action="{{ route('admin.mess.finance-bookings.reject', $booking->id) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject"
                                                        onclick="return confirm('Reject this booking?');">Reject</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No finance bookings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
