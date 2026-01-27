@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:wallet-money-bold" class="me-2"></iconify-icon>
            Store Due Report
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Store Name</th>
                        <th>Total Invoices</th>
                        <th>Total Amount Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stores as $store)
                        @php
                            $totalDue = $store->invoices->sum(function($inv) {
                                return ($inv->total_amount ?? 0) - ($inv->paid_amount ?? 0);
                            });
                        @endphp
                        <tr>
                            <td>{{ $store->store_name }}</td>
                            <td>{{ $store->invoices->count() }}</td>
                            <td>₹{{ number_format($totalDue, 2) }}</td>
                            <td>
                                <span class="badge {{ $totalDue > 0 ? 'bg-danger' : 'bg-success' }}">
                                    {{ $totalDue > 0 ? 'Pending' : 'Clear' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No store data found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
