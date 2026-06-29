@extends('admin.layouts.master')

@section('title', 'Client Types - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Client Types" />
    <div class="datatables">
        <div class="card" >
            <div class="card-body">
                <div class="row">
                    <div class="col-6"><h4>Client Types</h4></div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.mess.client-types.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Client Type
                            </a>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                <table id="adminClientTypesTable" class="table w-100 text-nowrap" data-mess-column-manager data-mess-column-skip="4" data-mess-column-title="Client type columns">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Type Name</th>
                            <th>Type Code</th>
                            <th>Default Credit Limit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientTypes as $key => $type)
                        <tr>
                            <td>{{ $clientTypes->firstItem() + $key }}</td>
                            <td>{{ $type->type_name }}</td>
                            <td>{{ $type->type_code }}</td>
                            <td>₹{{ number_format($type->default_credit_limit, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.mess.client-types.edit', encrypt($type->id)) }}" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No client types found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <div class="d-flex justify-content-center mt-3">{{ $clientTypes->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
