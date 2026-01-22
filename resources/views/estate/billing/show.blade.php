@extends('admin.layouts.master')

@section('title', 'Bill Details')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Bill Details" />
    
    <!-- Bill Information -->
    <div class="card mb-3" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6"><h4>Bill Details</h4></div>
                <div class="col-6 text-end">
                    <span class="badge bg-{{ $billing->payment_status == 'Paid' ? 'success' : ($billing->payment_status == 'Partial' ? 'warning' : 'danger') }}">
                        {{ $billing->payment_status }}
                    </span>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Employee:</th>
                            <td>{{ $billing->possession->employee->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Unit:</th>
                            <td>{{ $billing->possession->unit->unit_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Campus:</th>
                            <td>{{ $billing->possession->unit->campus->campus_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Area:</th>
                            <td>{{ $billing->possession->unit->area->area_name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Bill Month:</th>
                            <td>{{ date('F', mktime(0, 0, 0, $billing->bill_month, 1)) }}</td>
                        </tr>
                        <tr>
                            <th>Bill Year:</th>
                            <td>{{ $billing->bill_year }}</td>
                        </tr>
                        <tr>
                            <th>Bill Date:</th>
                            <td>{{ $billing->created_at->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <th>Remarks:</th>
                            <td>{{ $billing->remarks ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Breakdown -->
    <div class="card mb-3" style="border-left: 4px solid #28a745;">
        <div class="card-body">
            <h4 class="mb-3">Bill Breakdown</h4>
            <table class="table table-bordered">
                <tr>
                    <th>License Fee</th>
                    <td class="text-end">₹{{ number_format($billing->licence_fee, 2) }}</td>
                </tr>
                <tr>
                    <th>Water Charge</th>
                    <td class="text-end">₹{{ number_format($billing->water_charge, 2) }}</td>
                </tr>
                <tr>
                    <th>Electric Charge</th>
                    <td class="text-end">₹{{ number_format($billing->electric_charge, 2) }}</td>
                </tr>
                <tr>
                    <th>Other Charges</th>
                    <td class="text-end">₹{{ number_format($billing->other_charges, 2) }}</td>
                </tr>
                <tr class="table-primary">
                    <th>Total Amount</th>
                    <th class="text-end">₹{{ number_format($billing->total_amount, 2) }}</th>
                </tr>
                <tr class="table-success">
                    <th>Paid Amount</th>
                    <td class="text-end">₹{{ number_format($billing->paid_amount, 2) }}</td>
                </tr>
                <tr class="table-danger">
                    <th>Balance Amount</th>
                    <th class="text-end">₹{{ number_format($billing->balance_amount, 2) }}</th>
                </tr>
            </table>
        </div>
    </div>

    <!-- Payment History -->
    <div class="card mb-3" style="border-left: 4px solid #ffc107;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6"><h4>Payment History</h4></div>
                <div class="col-6 text-end">
                    @if($billing->balance_amount > 0)
                        <a href="{{ route('estate.billing.payment', $billing->pk) }}" class="btn btn-success btn-sm">
                            <i class="ti ti-cash"></i> Add Payment
                        </a>
                    @endif
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Payment Date</th>
                            <th>Amount</th>
                            <th>Payment Mode</th>
                            <th>Transaction Reference</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($billing->payments as $index => $payment)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                            <td>₹{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_mode }}</td>
                            <td>{{ $payment->transaction_reference ?? '-' }}</td>
                            <td>{{ $payment->createdBy->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No payments recorded yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <a href="{{ route('estate.billing.index') }}" class="btn btn-secondary"><i class="ti ti-arrow-back"></i> Back to List</a>
        <button onclick="window.print()" class="btn btn-info"><i class="ti ti-printer"></i> Print Bill</button>
    </div>
</div>
@endsection
