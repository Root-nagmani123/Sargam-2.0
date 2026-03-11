@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:users-group-rounded-bold" class="me-2"></iconify-icon>
            Client Invoice Report
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
            This report will show invoices for students and employees. Implementation pending based on client billing requirements.
        </div>
        
        <div class="text-center py-5">
            <iconify-icon icon="solar:document-text-bold" style="font-size: 64px; color: #ccc;"></iconify-icon>
            <p class="text-muted mt-3">Client invoice report coming soon...</p>
        </div>
    </div>
</div>
@endsection
