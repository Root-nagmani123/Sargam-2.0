@extends('admin.layouts.master')

@section('title', 'Menu Rate List - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Menu Rate List" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row">
                    <div class="col-6"><h4>Menu Rate List</h4></div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.mess.menu-rate-lists.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Menu Rate
                            </a>
                        </div>
                    </div>
                </div>
                <hr>
                <table class="table w-100 text-nowrap">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Menu Item</th>
                            <th>Rate</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $key => $menu)
                        <tr>
                            <td>{{ $menus->firstItem() + $key }}</td>
                            <td>{{ $menu->menu_item_name }}</td>
                            <td>₹{{ number_format($menu->rate, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($menu->effective_from)->format('d M Y') }}</td>
                            <td>{{ $menu->effective_to ? \Carbon\Carbon::parse($menu->effective_to)->format('d M Y') : 'Ongoing' }}</td>
                            <td><span class="badge {{ $menu->is_active ? 'bg-success' : 'bg-danger' }}">{{ $menu->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <a href="{{ route('admin.mess.menu-rate-lists.edit', $menu->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No menu rates found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-3">{{ $menus->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
