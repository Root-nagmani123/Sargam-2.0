@if (session('gtc_success'))
    <div class="alert alert-success alert-dismissible fade show" role="status">
        <i class="bi bi-check-circle me-1"></i>{{ session('gtc_success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('gtc_error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>{{ session('gtc_error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <b>Check the form:</b>
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
