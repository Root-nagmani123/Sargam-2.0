@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/protocol-admin.css') }}?v={{ @filemtime(public_path('css/protocol-admin.css')) ?: time() }}">
@endpush
