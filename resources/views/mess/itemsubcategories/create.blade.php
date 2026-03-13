@extends('admin.layouts.master')
@section('title', 'Add Subcategory Item')
@section('setup_content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h4 class="mb-0">Add Subcategory Item</h4>
                    <p class="mb-0 text-muted small">Create a new mess subcategory item with code, unit and alert quantity.</p>
                </div>
                <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="btn btn-outline-secondary btn-sm">
                    Back to list
                </a>
            </div>

            <form method="POST" action="{{ route('admin.mess.itemsubcategories.store') }}" id="createItemSubcategoryPageForm">
                @csrf

                @include('mess.itemsubcategories._form', ['itemsubcategory' => null, 'categories' => $categories])

                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-success" id="createItemSubcategoryPageSubmitBtn">Save</button>
                    <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('createItemSubcategoryPageForm');
    var btn = document.getElementById('createItemSubcategoryPageSubmitBtn');
    if (form && btn) {
        form.addEventListener('submit', function() {
            if (form.checkValidity() && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }
        });
    }
});
</script>
@endpush
