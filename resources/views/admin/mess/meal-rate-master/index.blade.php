@extends('admin.layouts.master')

@section('title', 'Meal Rate Master - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Meal Rate Master" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="row">
                    <div class="col-6"><h4>Meal Rate Master</h4></div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.mess.meal-rate-master.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Meal Rate
                            </a>
                        </div>
                    </div>
                </div>
                <hr>
                <table class="table w-100 text-nowrap">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Meal Type</th>
                            <th>Category Type</th>
                            <th>Rate (₹)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $key => $rate)
                        <tr>
                            <td>{{ $rates->firstItem() + $key }}</td>
                            <td>{{ $rate->meal_type_label }}</td>
                            <td>{{ $rate->category_type_label }}</td>
                            <td>₹{{ number_format($rate->rate, 2) }}</td>
                            <td><span class="badge {{ $rate->is_active ? 'bg-success' : 'bg-danger' }}">{{ $rate->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <a href="{{ route('admin.mess.meal-rate-master.edit', $rate->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="{{ route('admin.mess.meal-rate-master.toggle-status', $rate->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $rate->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $rate->is_active ? 'Set Inactive' : 'Set Active' }}">
                                        {{ $rate->is_active ? 'Inactive' : 'Active' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.mess.meal-rate-master.destroy', $rate->id) }}" class="d-inline" onsubmit="return confirm('Delete this meal rate?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No meal rates found. <a href="{{ route('admin.mess.meal-rate-master.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-3">{{ $rates->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
