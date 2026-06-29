@extends('admin.layouts.master')

@section('title', 'Credit Limits - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Credit Limits" />
    <div class="datatables">
        <div class="card" >
            <div class="card-body">
                <div class="row">
                    <div class="col-6"><h4>Credit Limits</h4></div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.mess.credit-limits.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Credit Limit
                            </a>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                <table id="creditLimitsTable" class="table w-100 text-nowrap" data-mess-column-manager data-mess-column-skip="6">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>User</th>
                            <th>Client Type</th>
                            <th>Credit Limit</th>
                            <th>Current Balance</th>
                            <th>Available Credit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditLimits as $key => $limit)
                        <tr>
                            <td>{{ $creditLimits->firstItem() + $key }}</td>
                            <td>{{ $limit->user->name ?? 'N/A' }}</td>
                            <td>{{ $limit->client_type }}</td>
                            <td>₹{{ number_format($limit->credit_limit, 2) }}</td>
                            <td>₹{{ number_format($limit->current_balance, 2) }}</td>
                            <td>₹{{ number_format($limit->credit_limit - $limit->current_balance, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.mess.credit-limits.edit', encrypt($limit->id)) }}" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No credit limits found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <div class="d-flex justify-content-center mt-3">{{ $creditLimits->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
