@extends('admin.layouts.master')
@section('title', 'Invoice Management')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Invoice Management</h4>
                <div>
                    <a href="{{ route('admin.mess.invoices.create') }}" class="btn btn-primary">
                        Add Invoice
                    </a>
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        
            <!-- Filter Form -->
            <form method="GET" action="{{ route('admin.mess.invoices.index') }}" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All</option>
                            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info w-100">Filter</button>
                    </div>
                </div>
            </form>
        
            <!-- Invoices Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff; border-color: #af2910;">#</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Invoice No</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff; border-color: #af2910;">Date</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Vendor</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Buyer</th>
                            <th style="width: 130px; background-color: #af2910; color: #fff; border-color: #af2910;">Amount</th>
                            <th style="width: 130px; background-color: #af2910; color: #fff; border-color: #af2910;">Paid</th>
                            <th style="width: 130px; background-color: #af2910; color: #fff; border-color: #af2910;">Balance</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff; border-color: #af2910;">Status</th>
                            <th style="width: 200px; background-color: #af2910; color: #fff; border-color: #af2910;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-semibold">{{ $invoice->invoice_no ?? 'INV-' . $invoice->id }}</div>
                                @if($invoice->due_date && $invoice->isOverdue)
                                    <span class="badge bg-danger small">Overdue</span>
                                @endif
                            </td>
                            <td>{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                            <td>{{ $invoice->vendor->name ?? '-' }}</td>
                            <td>{{ $invoice->buyer ? (trim(($invoice->buyer->first_name ?? '') . ' ' . ($invoice->buyer->last_name ?? '')) ?: $invoice->buyer->user_name) : '-' }}</td>
                            <td>₹{{ number_format($invoice->amount, 2) }}</td>
                            <td>₹{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            <td>₹{{ number_format($invoice->balance ?? $invoice->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($invoice->payment_status ?? 'unpaid') }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('admin.mess.invoices.show', $invoice->id) }}" 
                                       class="btn btn-sm btn-info" title="View">View</a>
                                    <a href="{{ route('admin.mess.invoices.edit', $invoice->id) }}" 
                                       class="btn btn-sm btn-warning" title="Edit">Edit</a>
                                    <form method="POST" action="{{ route('admin.mess.invoices.destroy', $invoice->id) }}" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this invoice?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">No invoices found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
