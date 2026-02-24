@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:bill-list-bold" class="me-2"></iconify-icon>
            Mess Bill Report
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <select name="month" class="form-select form-select-sm select2">
                        <option value="">All Months</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="year" class="form-select form-select-sm select2">
                        <option value="">All Years</option>
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

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
                                <span class="badge {{ $bill->status == 'paid' ? 'bg-success' : 'bg-warning' }}">
                                    {{ ucfirst($bill->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No bills found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $bills->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
