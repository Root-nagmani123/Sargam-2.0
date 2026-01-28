@extends('admin.layouts.master')
@section('title', 'Item Sub Category Master')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Item Sub Category Master</h4>
                <a href="{{ route('admin.mess.itemsubcategories.create') }}" class="btn btn-primary">Add Item Sub Category</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff;">#</th>
                            <th style="background-color: #af2910; color: #fff;">Category Name</th>
                            <th style="background-color: #af2910; color: #fff;">Item Name</th>
                            <th style="width: 140px; background-color: #af2910; color: #fff;">Item Code</th>
                            <th style="width: 140px; background-color: #af2910; color: #fff;">Unit Measurement</th>
                            <th style="width: 140px; background-color: #af2910; color: #fff;">Standard Cost</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff;">Status</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itemsubcategories as $itemsubcategory)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $itemsubcategory->category->category_name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $itemsubcategory->item_name }}</div>
                                </td>
                                <td>{{ $itemsubcategory->item_code ?? '-' }}</td>
                                <td>{{ $itemsubcategory->unit_measurement ?? '-' }}</td>
                                <td>
                                    @if($itemsubcategory->standard_cost)
                                        ₹{{ number_format($itemsubcategory->standard_cost, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $itemsubcategory->status_badge_class }}">
                                        {{ $itemsubcategory->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.mess.itemsubcategories.edit', $itemsubcategory->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="{{ route('admin.mess.itemsubcategories.destroy', $itemsubcategory->id) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this item sub category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No item sub categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
    .table thead th {
        background-color: #af2910 !important;
        color: #fff !important;
    }
</style>
@endsection
