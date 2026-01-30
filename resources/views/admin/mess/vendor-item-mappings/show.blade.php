@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:tag-price-bold" class="me-2"></iconify-icon>
            Vendor Mapping Details
        </h5>
        <a href="{{ route('admin.mess.vendor-item-mappings.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Vendor Name</dt>
            <dd class="col-sm-9">{{ $mapping->vendor->vendor_name ?? $mapping->vendor->name ?? 'N/A' }}</dd>

            <dt class="col-sm-3">Mapping Type</dt>
            <dd class="col-sm-9">
                {{ $mapping->mapping_type === \App\Models\Mess\VendorItemMapping::MAPPING_TYPE_ITEM_CATEGORY ? 'Item Category' : 'Item Sub Category' }}
            </dd>

            @if($mapping->itemCategory)
            <dt class="col-sm-3">Item Category</dt>
            <dd class="col-sm-9">{{ $mapping->itemCategory->category_name }}</dd>
            @endif

            @if($mapping->itemSubcategory)
            <dt class="col-sm-3">Item Sub Category</dt>
            <dd class="col-sm-9">{{ $mapping->itemSubcategory->item_name ?? $mapping->itemSubcategory->subcategory_name ?? 'N/A' }}</dd>
            @endif
        </dl>
        <hr>
        <a href="{{ route('admin.mess.vendor-item-mappings.edit', $mapping->id) }}" class="btn btn-primary btn-sm">Edit</a>
    </div>
</div>
@endsection
