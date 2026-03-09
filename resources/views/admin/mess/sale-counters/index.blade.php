@extends('admin.layouts.master')

@section('title', 'Sale Counters - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Sale Counters" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row">
                    <div class="col-6"><h4>Sale Counters</h4></div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.mess.sale-counters.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Sale Counter
                            </a>
                        </div>
                    </div>
                </div>
                <hr>
                <table class="table w-100 text-nowrap">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Counter Name</th>
                            <th>Counter Code</th>
                            <th>Store</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($counters as $key => $counter)
                        <tr>
                            <td>{{ $counters->firstItem() + $key }}</td>
                            <td>{{ $counter->counter_name }}</td>
                            <td>{{ $counter->counter_code }}</td>
                            <td>{{ $counter->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $counter->location ?? 'N/A' }}</td>
                            <td><span class="badge {{ $counter->is_active ? 'bg-success' : 'bg-danger' }}">{{ $counter->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <a href="{{ route('admin.mess.sale-counters.show', $counter->id) }}" class="btn btn-sm btn-info">View</a>
                                <a href="{{ route('admin.mess.sale-counters.edit', $counter->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No sale counters found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-3">{{ $counters->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
