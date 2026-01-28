@extends('admin.layouts.master')
@section('title', 'Item Category Master')
@section('setup_content')
@php
    $categoryTypes = \App\Models\Mess\ItemCategory::categoryTypes();
@endphp
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Item Category Master</h4>
                <a href="{{ route('admin.mess.itemcategories.create') }}" class="btn btn-primary">Add Item Category</a>
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
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Category Type</th>
                            <th style="background-color: #af2910; color: #fff;">Item Category Description</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff;">Status</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itemcategories as $itemcategory)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $itemcategory->category_name }}</div>
                                </td>
                                <td>
                                    @php
                                        $categoryType = $itemcategory->category_type ?? 'raw_material';
                                        $types = \App\Models\Mess\ItemCategory::categoryTypes();
                                    @endphp
                                    {{ $types[$categoryType] ?? ucfirst(str_replace('_', ' ', $categoryType)) }}
                                </td>
                                <td>{{ $itemcategory->description ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $itemcategory->status_badge_class }}">
                                        {{ $itemcategory->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.mess.itemcategories.edit', $itemcategory->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="{{ route('admin.mess.itemcategories.destroy', $itemcategory->id) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this item category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No item categories found.</td>
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
