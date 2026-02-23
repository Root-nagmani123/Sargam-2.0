@extends('admin.layouts.master')
@section('title', 'Create Material Request')
@section('setup_content')
<div class="container-fluid">
    <h4>Create Material Request</h4>
    <form method="POST" action="{{ route('admin.mess.materialrequests.store') }}" id="materialRequestForm">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Request Number *</label>
                <input type="text" name="request_number" class="form-control" value="{{ $request_number }}" readonly required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Request Date *</label>
                <input type="date" name="request_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Store</label>
                <select name="store_id" class="form-select select2">
                    <option value="">Select Store</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label>Purpose</label>
            <textarea name="purpose" class="form-control" rows="2"></textarea>
        </div>
        
        <h5>Items</h5>
        <div id="itemsContainer">
            <div class="row mb-2 item-row">
                <div class="col-md-5">
                    <select name="items[0][inventory_id]" class="form-select select2" required>
                        <option value="">Select Item</option>
                        @foreach($inventories as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="items[0][requested_quantity]" class="form-control" placeholder="Quantity" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="items[0][unit]" class="form-control" placeholder="Unit">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-item" disabled>Remove</button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-secondary btn-sm mb-3" id="addItem">Add Item</button>
        
        <div>
            <button type="submit" class="btn btn-success">Submit Request</button>
            <a href="{{ route('admin.mess.materialrequests.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
let itemIndex = 1;
document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.querySelector('.item-row').cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(el => {
        el.name = el.name.replace('[0]', '[' + itemIndex + ']');
        el.value = '';
    });
    newRow.querySelector('.remove-item').disabled = false;
    container.appendChild(newRow);
    itemIndex++;
});

document.getElementById('itemsContainer').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        e.target.closest('.item-row').remove();
    }
});
</script>
@endsection
