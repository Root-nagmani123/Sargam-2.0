@extends('admin.layouts.master')

@section('title', 'Client Types - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Client Types" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
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
                <table class="table w-100 text-nowrap">
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
                                <a href="{{ route('admin.mess.client-types.edit', $type->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No client types found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-3">{{ $clientTypes->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
