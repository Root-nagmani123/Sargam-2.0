@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:bill-list-bold" class="me-2"></iconify-icon>
            Monthly Bills
        </h5>
        <div>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#generateBillModal">
                <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
                Generate Bills
            </button>
            <a href="{{ route('admin.mess.monthly-bills.create') }}" class="btn btn-primary btn-sm">
                <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
                Add Bill
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.mess.monthly-bills.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-2">
                    <select name="month" class="form-select form-select-sm">
                        <option value="">All Months</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search by bill number or user name..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <iconify-icon icon="solar:magnifer-bold"></iconify-icon> Filter
                    </button>
                </div>
            </div>
        </form>

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
                        <th>Bill Number</th>
                        <th>User</th>
                        <th>Period</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $bill)
                        <tr>
                            <td>{{ $bill->bill_number }}</td>
                            <td>{{ $bill->user->name ?? 'N/A' }}</td>
                            <td>{{ date('F Y', mktime(0, 0, 0, $bill->month, 1, $bill->year)) }}</td>
                            <td>₹{{ number_format($bill->total_amount, 2) }}</td>
                            <td>₹{{ number_format($bill->paid_amount, 2) }}</td>
                            <td>₹{{ number_format($bill->balance, 2) }}</td>
                            <td>
                                @if($bill->status == 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($bill->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($bill->status == 'partial')
                                    <span class="badge bg-info">Partial</span>
                                @else
                                    <span class="badge bg-danger">Overdue</span>
                                @endif
                            </td>
                            <td>{{ $bill->due_date ? date('d-M-Y', strtotime($bill->due_date)) : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.mess.monthly-bills.show', $bill->id) }}" 
                                   class="btn btn-sm btn-info" title="View">
                                    <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                </a>
                                <a href="{{ route('admin.mess.monthly-bills.edit', $bill->id) }}" 
                                   class="btn btn-sm btn-warning" title="Edit">
                                    <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                </a>
                                <form action="{{ route('admin.mess.monthly-bills.destroy', $bill->id) }}" 
                                      method="POST" class="d-inline" 
                                      onsubmit="return confirm('Are you sure you want to delete this bill?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <iconify-icon icon="solar:bill-list-bold" style="font-size: 48px;"></iconify-icon>
                                <p class="mt-2">No monthly bills found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3">
            {{ $bills->links() }}
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
