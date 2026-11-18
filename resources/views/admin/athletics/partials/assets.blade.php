{{-- Module stylesheet + shared script, linked once per Athletics screen. --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/athletics-admin.css') }}?v={{ @filemtime(public_path('css/athletics-admin.css')) ?: time() }}">
@endpush
@push('scripts')
<script src="{{ asset('js/athletics-admin.js') }}?v={{ @filemtime(public_path('js/athletics-admin.js')) ?: time() }}"></script>
@endpush
