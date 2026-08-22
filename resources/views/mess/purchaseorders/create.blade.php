@extends('admin.layouts.master')
@section('title', 'Create Purchase Order')
@push('styles')
<style>
    /* The Mess form control: the same 44px pill the Add Store / Add Vendor
       modals use. This page had shipped Bootstrap's -sm variant, which put
       32px controls at 12.25px next to a module that is 44px at 15px. */
    .po-create-page .form-control,
    .po-create-page .form-select {
        min-height: 44px;
        border-radius: 8px;
        border: 1px solid var(--ds-line, #d0d5dd);
        font-size: .9375rem;
        color: var(--ds-ink, #1f2937);
        padding: .5rem .875rem;
    }

    .po-create-page textarea.form-control { min-height: 76px; }

    .po-create-page .form-control::placeholder { color: #98a2b3; }

    .po-create-page .form-control:focus,
    .po-create-page .form-select:focus {
        border-color: var(--ds-primary, #004384);
        box-shadow: 0 0 0 3px rgba(0, 67, 132, .12);
    }

    .po-create-page .form-label {
        font-size: .875rem;
        font-weight: 500;
        color: var(--ds-ink, #1f2937);
        margin-bottom: .35rem;
    }

    /* Row actions match the control height so the line item reads as one row. */
    .po-create-page .item-row .remove-item { min-height: 44px; border-radius: 8px; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid po-create-page mess-select2">
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
                                <input type="text" name="po_number" class="form-control bg-light" value="{{ $po_number }}" readonly required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">PO Date <span class="text-danger">*</span></label>
                                <input type="date" name="po_date" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" class="form-select" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Store</label>
                                <select name="store_id" class="form-select">
                                    <option value="">Select Store</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks about this order"></textarea>
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
                        <input type="file" name="bill_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
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
                <div class="row g-2 align-items-end mb-2 item-row">
                    <div class="col-md-4">
                        <label class="form-label">Item</label>
                        <select name="items[0][item_subcategory_id]" class="form-select" required>
                            <option value="">Select Item</option>
                            @foreach($itemSubcategories as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="items[0][quantity]" class="form-control" placeholder="Quantity" step="0.01" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit</label>
                        <input type="text" name="items[0][unit]" class="form-control" placeholder="Unit">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit Price</label>
                        <input type="number" name="items[0][unit_price]" class="form-control" placeholder="Unit Price" step="0.01" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-item w-100" disabled>Remove</button>
                    </div>
                </div>
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
let itemIndex = 1;
const itemSubcategories = @json($itemSubcategories);

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    // Same markup as the first row above — same column widths, same control
    // classes, same gutter and bottom alignment. Only the labels are dropped,
    // because align-items-end lines these controls up under the first row's.
    const template = `
        <div class="row g-2 align-items-end mb-2 item-row">
            <div class="col-md-4">
                <select name="items[${itemIndex}][item_subcategory_id]" class="form-select" required>
                    <option value="">Select Item</option>
                    ${itemSubcategories.map(inv => `<option value="${inv.id}">${inv.item_name}</option>`).join('')}
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
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger remove-item w-100">Remove</button>
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
@include('mess.partials.select2-search')
@endsection
