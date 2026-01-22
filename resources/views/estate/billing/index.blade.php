@extends('admin.layouts.master')

@section('title', 'Billing Management')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Billing Management" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6"><h4>Billing Management</h4></div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-end mb-3">
                        <a href="{{ route('estate.billing.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                            Generate Bill
                        </a>
                    </div>
                </div>
            </div>
            <hr>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="table-responsive">
                <table id="billingTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Employee</th>
                            <th>Unit</th>
                            <th>Bill Month</th>
                            <th>Bill Year</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#billingTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('estate.billing.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee', name: 'possession.employee.name'},
            {data: 'unit', name: 'possession.unit.unit_name'},
            {data: 'bill_month', name: 'bill_month'},
            {data: 'bill_year', name: 'bill_year'},
            {data: 'total_amount', name: 'total_amount'},
            {data: 'paid_amount', name: 'paid_amount'},
            {data: 'balance_amount', name: 'balance_amount'},
            {data: 'payment_status', name: 'payment_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to delete this billing record?')) {
            $.ajax({
                url: "{{ route('estate.billing.index') }}/" + id,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload();
                        alert(response.message);
                    }
                }
            });
        }
    });
});
</script>
@endpush
