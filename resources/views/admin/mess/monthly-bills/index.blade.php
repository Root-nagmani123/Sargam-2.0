@extends('admin.layouts.master')
@section('title', 'Monthly Bills')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Monthly Bills</h4>
                <div>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateBillModal">
                        Generate Bills
                    </button>
                    <a href="{{ route('admin.mess.monthly-bills.create') }}" class="btn btn-primary">
                        Add Bill
                    </a>
                </div>
            </div>
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.mess.monthly-bills.index') }}" class="mb-3">
                <div class="row g-2">
                    <div class="col-md-2">
                        <select name="month" class="form-select">
                        <option value="">All Months</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                    <div class="col-md-2">
                        <select name="year" class="form-select">
                            <option value="">All Years</option>
                            @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by bill number or user name..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>

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
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Bill Number</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">User</th>
                            <th style="width: 140px; background-color: #af2910; color: #fff; border-color: #af2910;">Period</th>
                            <th style="width: 130px; background-color: #af2910; color: #fff; border-color: #af2910;">Total Amount</th>
                            <th style="width: 130px; background-color: #af2910; color: #fff; border-color: #af2910;">Balance</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff; border-color: #af2910;">Status</th>
                            <th style="width: 200px; background-color: #af2910; color: #fff; border-color: #af2910;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bills as $bill)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $bill->bill_number }}</div>
                                    @if($bill->due_date && strtotime($bill->due_date) < time() && $bill->status != 'paid')
                                        <span class="badge bg-danger small">Overdue</span>
                                    @endif
                                </td>
                                <td>{{ $bill->user->name ?? '-' }}</td>
                                <td>{{ date('F Y', mktime(0, 0, 0, $bill->month, 1, $bill->year)) }}</td>
                                <td>₹{{ number_format($bill->total_amount, 2) }}</td>
                                <td>₹{{ number_format($bill->balance, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $bill->status == 'paid' ? 'success' : ($bill->status == 'partial' ? 'info' : ($bill->status == 'overdue' ? 'danger' : 'warning')) }}">
                                        {{ ucfirst($bill->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('admin.mess.monthly-bills.show', $bill->id) }}" 
                                           class="btn btn-sm btn-info" title="View">View</a>
                                        <a href="{{ route('admin.mess.monthly-bills.edit', $bill->id) }}" 
                                           class="btn btn-sm btn-warning" title="Edit">Edit</a>
                                        <form action="{{ route('admin.mess.monthly-bills.destroy', $bill->id) }}" 
                                              method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this bill?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No monthly bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Generate Bill Modal -->
<div class="modal fade" id="generateBillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.monthly-bills.generate') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Generate Monthly Bills</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select" required>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select" required>
                            @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bill Amount (₹)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="alert alert-info">
                        <small>This will generate bills for all users for the selected month and year.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Generate Bills</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
