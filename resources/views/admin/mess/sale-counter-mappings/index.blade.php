@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:layers-minimalistic-bold" class="me-2"></iconify-icon>
            Sale Counter Mappings
        </h5>
        <a href="{{ route('admin.mess.sale-counter-mappings.create') }}" class="btn btn-primary btn-sm">
            <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
            Add New Mapping
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($mappings->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Sale Counter</th>
                            <th>Item</th>
                            <th>Available Quantity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mappings as $mapping)
                        <tr>
                            <td>{{ $mapping->id }}</td>
                            <td>
                                <strong>{{ $mapping->saleCounter->counter_name ?? 'N/A' }}</strong><br>
                                <small class="text-muted">{{ $mapping->saleCounter->counter_code ?? '' }}</small>
                            </td>
                            <td>
                                {{ $mapping->inventory->item_name ?? 'N/A' }}<br>
                                <small class="text-muted">{{ $mapping->inventory->item_code ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $mapping->available_quantity }}</span>
                            </td>
                            <td>
                                @if($mapping->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.mess.sale-counter-mappings.edit', $mapping->id) }}" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                    </a>
                                    <form action="{{ route('admin.mess.sale-counter-mappings.destroy', $mapping->id) }}" 
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this mapping?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $mappings->links() }}
            </div>
        @else
            <div class="alert alert-info">
                <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                No sale counter mappings found. Click "Add New Mapping" to create one.
            </div>
        @endif
    </div>
</div>
@endsection
