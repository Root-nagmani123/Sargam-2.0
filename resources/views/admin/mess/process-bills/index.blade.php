@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Process Mess Bills</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="#">Mess Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Process Mess Bills</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="ti ti-users fs-8 text-primary"></i>
                    </div>
                    <h5 class="card-title">Process Employee Bills</h5>
                    <p class="card-text text-muted">Generate bulk invoices for employee mess bills with salary deduction</p>
                    <a href="{{ route('admin.mess.process-bills.employee-bills') }}" class="btn btn-primary">
                        <i class="ti ti-arrow-right"></i> Open
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="ti ti-user-plus fs-8 text-success"></i>
                    </div>
                    <h5 class="card-title">Generate Guest Invoice</h5>
                    <p class="card-text text-muted">Create invoices for guest and other non-employee transactions</p>
                    <a href="{{ route('admin.mess.process-bills.create-guest') }}" class="btn btn-success">
                        <i class="ti ti-arrow-right"></i> Open
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="ti ti-file-invoice fs-8 text-info"></i>
                    </div>
                    <h5 class="card-title">Guest Invoice List</h5>
                    <p class="card-text text-muted">View and manage all guest and other invoices</p>
                    <a href="{{ route('admin.mess.process-bills.guest-list') }}" class="btn btn-info">
                        <i class="ti ti-arrow-right"></i> Open
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Activity</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No recent activity</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
    }
</style>
@endpush
