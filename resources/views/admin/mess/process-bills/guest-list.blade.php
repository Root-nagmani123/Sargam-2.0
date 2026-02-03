@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Guest/Others Invoice List</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="#">Mess Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Guest Invoice List</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.process-bills.guest-list') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ request('start_date', date('Y-m-01')) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ request('end_date', date('Y-m-t')) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="payment_mode" class="form-label">Payment Mode</label>
                            <select class="form-select" id="payment_mode" name="payment_mode">
                                <option value="">All</option>
                                <option value="cash" {{ request('payment_mode') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="online" {{ request('payment_mode') == 'online' ? 'selected' : '' }}>Online</option>
                                <option value="card" {{ request('payment_mode') == 'card' ? 'selected' : '' }}>Card</option>
                                <option value="myself" {{ request('payment_mode') == 'myself' ? 'selected' : '' }}>MySelf</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-3">
                            <i class="ti ti-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title mb-3">Guest/Others Invoices</h5>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="invoicesTable">
                    <thead class="table-primary">
                        <tr>
                            <th>S. NO.</th>
                            <th>INVOICE NO.</th>
                            <th>BUYER NAME</th>
                            <th>GUEST NAME</th>
                            <th>INVOICE DATE</th>
                            <th>BILL NO.</th>
                            <th>TOTAL AMOUNT</th>
                            <th>PAYMENT TYPE</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                <i class="ti ti-info-circle"></i> Select date range and click search to view invoices
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <p class="text-muted small">Showing 0 entries</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // You can add DataTables or custom filtering logic here
    // This is a placeholder for the invoice list functionality
</script>
@endpush
