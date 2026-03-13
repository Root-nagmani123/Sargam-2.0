@extends('admin.layouts.master')
@section('title', 'Create Purchase Order')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">Create Purchase Order</h4>
            <p class="mb-0 text-muted small">Capture order details and add items for the mess store.</p>
        </div>
        <a href="{{ route('admin.mess.purchaseorders.index') }}" class="btn btn-outline-secondary btn-sm">
            Back to list
        </a>
    </div>

    <form method="POST" action="{{ route('admin.mess.purchaseorders.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-semibold text-primary">Order Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">PO Number <span class="text-danger">*</span></label>
                                <input type="text" name="po_number" class="form-control form-control-sm bg-light" value="{{ $po_number }}" readonly required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">PO Date <span class="text-danger">*</span></label>
                                <input type="date" name="po_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" class="form-select form-select-sm" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Store</label>
                                <select name="store_id" class="form-select form-select-sm">
                                    <option value="">Select Store</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Optional remarks about this order"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-3 h-100">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-semibold text-primary">Upload Bill (PDF / Image)</h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Bill / Attachment <small class="text-muted">(Optional)</small></label>
                        <input type="file" name="bill_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <small class="text-muted d-block mt-1">PDF, JPG, JPEG, PNG or WEBP. Max 5 MB.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-primary">Items</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">Add Item</button>
            </div>
            <div class="card-body">
        <div id="itemsContainer">
            @if($materialRequest)
                @foreach($materialRequest->items as $index => $mrItem)
                    <div class="row g-2 align-items-end mb-2 item-row">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Item</label>
                            <select name="items[{{ $index }}][inventory_id]" class="form-select form-select-sm" required>
                                <option value="{{ $mrItem->inventory_id }}">{{ $mrItem->inventory->item_name }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Quantity</label>
                            <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm" 
                                   placeholder="Quantity" step="0.01" value="{{ $mrItem->approved_quantity ?? $mrItem->requested_quantity }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Unit</label>
                            <input type="text" name="items[{{ $index }}][unit]" class="form-control form-control-sm" 
                                   placeholder="Unit" value="{{ $mrItem->unit }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Unit Price</label>
                            <input type="number" name="items[{{ $index }}][unit_price]" class="form-control form-control-sm" 
                                   placeholder="Unit Price" step="0.01" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-item w-100">Remove</button>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="row g-2 align-items-end mb-2 item-row">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Item</label>
                        <select name="items[0][inventory_id]" class="form-select form-select-sm" required>
                            <option value="">Select Item</option>
                            @foreach($inventories as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm">Quantity</label>
                        <input type="number" name="items[0][quantity]" class="form-control form-control-sm" placeholder="Quantity" step="0.01" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm">Unit</label>
                        <input type="text" name="items[0][unit]" class="form-control form-control-sm" placeholder="Unit">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm">Unit Price</label>
                        <input type="number" name="items[0][unit_price]" class="form-control form-control-sm" placeholder="Unit Price" step="0.01" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item w-100" disabled>Remove</button>
                    </div>
                </div>
            @endif
        </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-success">
                Create PO
            </button>
            <a href="{{ route('admin.mess.purchaseorders.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
let itemIndex = {{ $materialRequest ? $materialRequest->items->count() : 1 }};
const inventories = @json($inventories);

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const template = `
        <div class="row mb-2 item-row">
            <div class="col-md-4">
                <select name="items[${itemIndex}][inventory_id]" class="form-control" required>
                    <option value="">Select Item</option>
                    ${inventories.map(inv => `<option value="${inv.id}">${inv.item_name}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control" placeholder="Quantity" step="0.01" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="items[${itemIndex}][unit]" class="form-control" placeholder="Unit">
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control" placeholder="Unit Price" step="0.01" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
    itemIndex++;
});

document.getElementById('itemsContainer').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        if (document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
        }
    }
});
</script>
@endsection
