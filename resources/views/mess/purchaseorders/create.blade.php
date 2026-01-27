@extends('admin.layouts.master')
@section('title', 'Create Purchase Order')
@section('setup_content')
<div class="container-fluid">
    <h4>Create Purchase Order</h4>
    <form method="POST" action="{{ route('admin.mess.purchaseorders.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-3 mb-3">
                <label>PO Number *</label>
                <input type="text" name="po_number" class="form-control" value="{{ $po_number }}" readonly required>
            </div>
            <div class="col-md-3 mb-3">
                <label>PO Date *</label>
                <input type="date" name="po_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Delivery Date</label>
                <input type="date" name="delivery_date" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label>Vendor *</label>
                <select name="vendor_id" class="form-control" required>
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Store</label>
                <select name="store_id" class="form-control">
                    <option value="">Select Store</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
        </div>
        
        <h5>Items</h5>
        <div id="itemsContainer">
            @if($materialRequest)
                @foreach($materialRequest->items as $index => $mrItem)
                    <div class="row mb-2 item-row">
                        <div class="col-md-4">
                            <select name="items[{{ $index }}][inventory_id]" class="form-control" required>
                                <option value="{{ $mrItem->inventory_id }}">{{ $mrItem->inventory->item_name }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="items[{{ $index }}][quantity]" class="form-control" 
                                   placeholder="Quantity" step="0.01" value="{{ $mrItem->approved_quantity ?? $mrItem->requested_quantity }}" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="items[{{ $index }}][unit]" class="form-control" 
                                   placeholder="Unit" value="{{ $mrItem->unit }}">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="items[{{ $index }}][unit_price]" class="form-control" 
                                   placeholder="Unit Price" step="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="row mb-2 item-row">
                    <div class="col-md-4">
                        <select name="items[0][inventory_id]" class="form-control" required>
                            <option value="">Select Item</option>
                            @foreach($inventories as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="items[0][quantity]" class="form-control" placeholder="Quantity" step="0.01" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="items[0][unit]" class="form-control" placeholder="Unit">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="items[0][unit_price]" class="form-control" placeholder="Unit Price" step="0.01" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-item" disabled>Remove</button>
                    </div>
                </div>
            @endif
        </div>
        <button type="button" class="btn btn-secondary btn-sm mb-3" id="addItem">Add Item</button>
        
        <div>
            <button type="submit" class="btn btn-success">Create PO</button>
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
