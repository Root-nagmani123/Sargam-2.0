@extends('admin.layouts.master')
@section('title', 'Sub Store Master')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Sub Store Master</h4>
                <a href="{{ route('admin.mess.sub-stores.create') }}" class="btn btn-primary">Add Sub Store</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff;">#</th>
                            <th style="background-color: #af2910; color: #fff;">Parent Store</th>
                            <th style="background-color: #af2910; color: #fff;">Sub Store Name</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff;">Status</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subStores as $subStore)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $subStore->parentStore->store_name ?? '-' }}</div>
                                    @if($subStore->parentStore)
                                        <div class="text-muted small">Code: {{ $subStore->parentStore->store_code }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $subStore->sub_store_name }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $subStore->status_badge_class }}">
                                        {{ $subStore->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.mess.sub-stores.edit', $subStore->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="{{ route('admin.mess.sub-stores.destroy', $subStore->id) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this sub store?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No sub stores found.</td>
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
