@extends('admin.layouts.master')
@section('title', 'Add Mess Vendor')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Add Vendor</h4>

            <form method="POST" action="{{ route('admin.mess.vendors.store') }}" enctype="multipart/form-data" id="createVendorPageForm">
                @csrf

                @include('mess.vendors._form', ['vendor' => null])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success" id="createVendorPageSubmitBtn">Save</button>
                    <a href="{{ route('admin.mess.vendors.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('createVendorPageForm');
    var btn = document.getElementById('createVendorPageSubmitBtn');
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
