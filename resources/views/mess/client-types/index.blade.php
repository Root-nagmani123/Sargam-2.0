@extends('admin.layouts.master')
@section('title', 'Client Types Master')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Client Types Master</h4>
                <a href="{{ route('admin.mess.client-types.create') }}" class="btn btn-primary">Add Client Type</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff;">#</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Client Types</th>
                            <th style="background-color: #af2910; color: #fff;">Client Name</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff;">Status</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientTypes as $clientType)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ \App\Models\Mess\ClientType::clientTypes()[$clientType->client_type] ?? $clientType->client_type }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $clientType->client_name }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $clientType->status_badge_class }}">
                                        {{ $clientType->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.mess.client-types.edit', $clientType->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="{{ route('admin.mess.client-types.destroy', $clientType->id) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this client type?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No client types found.</td>
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
