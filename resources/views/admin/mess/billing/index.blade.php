@extends('layouts.master')

@section('title', 'Sales & Billing Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sales & Billing Management</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.mess.billing.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create New Bill
                        </a>
                        <a href="{{ route('admin.mess.billing.dueReport') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-file-invoice-dollar"></i> Due Report
                        </a>
                    </div>
                </div>
                
                <!-- Filter Form -->
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.mess.billing.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" 
                                           value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Buyer Type</label>
                                    <select name="buyer_type" class="form-control">
                                        <option value="">All</option>
                                        <option value="2" {{ request('buyer_type') == '2' ? 'selected' : '' }}>OT</option>
                                        <option value="3" {{ request('buyer_type') == '3' ? 'selected' : '' }}>Section</option>
                                        <option value="4" {{ request('buyer_type') == '4' ? 'selected' : '' }}>Guest</option>
                                        <option value="5" {{ request('buyer_type') == '5' ? 'selected' : '' }}>Employee</option>
                                        <option value="6" {{ request('buyer_type') == '6' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Payment Status</label>
                                    <select name="payment_status" class="form-control">
                                        <option value="">All</option>
                                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-info btn-block">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by bill number or buyer name..."
                                           value="{{ request('search') }}">
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-3">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $sales->total() }}</h3>
                                    <p>Total Bills</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>₹{{ number_format($sales->where('due_amount', 0)->sum('total_amount'), 2) }}</h3>
                                    <p>Paid Amount</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>₹{{ number_format($sales->sum('due_amount'), 2) }}</h3>
                                    <p>Due Amount</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>₹{{ number_format($sales->sum('total_amount'), 2) }}</h3>
                                    <p>Total Revenue</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bills Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Bill Number</th>
                                    <th>Date</th>
                                    <th>Store</th>
                                    <th>Buyer</th>
                                    <th>Buyer Type</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Due Amount</th>
                                    <th>Payment Mode</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                <tr>
                                    <td><strong>{{ $sale->bill_number }}</strong></td>
                                    <td>{{ $sale->sale_date->format('d-m-Y') }}</td>
                                    <td>{{ $sale->store->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($sale->buyer_type == 6)
                                            {{ $sale->buyer_name }}
                                        @else
                                            {{ $sale->buyer->name ?? 'N/A' }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ $sale->buyer_type_name }}</span>
                                    </td>
                                    <td>₹{{ number_format($sale->total_amount, 2) }}</td>
                                    <td>₹{{ number_format($sale->paid_amount, 2) }}</td>
                                    <td>₹{{ number_format($sale->due_amount, 2) }}</td>
                                    <td>{{ ucfirst($sale->payment_mode) }}</td>
                                    <td>
                                        @if($sale->payment_status == 'Paid')
                                            <span class="badge badge-success">Paid</span>
                                        @elseif($sale->payment_status == 'Partial')
                                            <span class="badge badge-warning">Partial</span>
                                        @else
                                            <span class="badge badge-danger">Unpaid</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.mess.billing.show', $sale->id) }}" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($sale->due_amount > 0)
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    data-toggle="modal" 
                                                    data-target="#paymentModal{{ $sale->id }}"
                                                    title="Make Payment">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                
                                <!-- Payment Modal -->
                                <div class="modal fade" id="paymentModal{{ $sale->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.mess.billing.makePayment', $sale->id) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Make Payment - {{ $sale->bill_number }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>Due Amount</label>
                                                        <input type="text" class="form-control" 
                                                               value="₹{{ number_format($sale->due_amount, 2) }}" 
                                                               readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Payment Amount <span class="text-danger">*</span></label>
                                                        <input type="number" step="0.01" name="payment_amount" 
                                                               class="form-control" 
                                                               max="{{ $sale->due_amount }}" 
                                                               required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Payment Date <span class="text-danger">*</span></label>
                                                        <input type="date" name="payment_date" 
                                                               class="form-control" 
                                                               value="{{ date('Y-m-d') }}" 
                                                               required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Payment Mode <span class="text-danger">*</span></label>
                                                        <select name="payment_mode" class="form-control" required>
                                                            <option value="cash">Cash</option>
                                                            <option value="cheque">Cheque</option>
                                                            <option value="online">Online</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group cheque-field" style="display:none;">
                                                        <label>Cheque Number</label>
                                                        <input type="text" name="cheque_number" class="form-control">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Reference Number</label>
                                                        <input type="text" name="reference_number" class="form-control">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Remarks</label>
                                                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Record Payment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center">No bills found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $sales->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide cheque number field based on payment mode
    $('select[name="payment_mode"]').on('change', function() {
        if ($(this).val() === 'cheque') {
            $(this).closest('.modal-body').find('.cheque-field').show();
        } else {
            $(this).closest('.modal-body').find('.cheque-field').hide();
        }
    });
});
</script>
@endpush
@endsection
