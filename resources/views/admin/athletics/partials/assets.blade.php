{{-- Module stylesheet + shared script, linked once per Athletics screen. --}}
@push('styles')
{{-- Filters and modal selects are data-searchable (Select2); without these the chip renders as bare text. --}}
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/athletics-admin.css') }}?v={{ @filemtime(public_path('css/athletics-admin.css')) ?: time() }}">
@endpush
@push('scripts')
<script src="{{ asset('js/athletics-admin.js') }}?v={{ @filemtime(public_path('js/athletics-admin.js')) ?: time() }}"></script>
@endpush
