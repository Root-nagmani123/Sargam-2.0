{{-- The document actions (View / Download) fail by redirecting back with an
     'error' flash when the stored file is missing. Nothing in the admin layout
     renders that flash, so those buttons looked completely dead — the page just
     reloaded. Render it here so the failure states what went wrong. --}}
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
    <div>{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-check-circle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
