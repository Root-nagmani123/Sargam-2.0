{{-- Header images for EstateReportGrid.printGrid() (public/js/estate-report-grid.js) —
     the same branded LBSNAA header the module's report PDFs use. --}}
<script>
    window.EstateReportPrintAssets = {
        logoLeft: @json(asset('admin_assets/images/logos/logo_new.png')),
        logoRight: @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
            ? asset('admin_assets/images/logos/constitution-75.png')
            : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
        titleHindi: @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'))
    };
</script>
<script src="{{ asset('js/estate-report-grid.js') }}?v={{ @filemtime(public_path('js/estate-report-grid.js')) ?: time() }}"></script>
