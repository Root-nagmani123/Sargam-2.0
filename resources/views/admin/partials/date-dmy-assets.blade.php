{{-- DD-MM-YYYY date pickers: any <input data-date-dmy> on the page shows and
     accepts DD-MM-YYYY and still posts YYYY-MM-DD. See public/js/date-dmy.js.
     flatpickr is the same pinned version the feedback and memo pages load. --}}
@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
        <script src="{{ asset('js/date-dmy.js') }}?v={{ @filemtime(public_path('js/date-dmy.js')) ?: time() }}"></script>
    @endpush
@endonce
