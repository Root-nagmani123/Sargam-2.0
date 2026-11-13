{{-- Branded print chrome for a client-side CoeGrid page. Inert until Print:
     CoeGrid's print fills the title, date, filters and total and wraps the rows on
     screen in it, so the printout matches the masters' server Print
     (admin.master.partials.export_print). --}}
<template id="coePrintBrand">
    @include('admin.master.partials.export_print_styles', ['orientation' => 'landscape'])
    @include('admin.master.partials.export_print_header', ['title' => '', 'exportDate' => ''])
    <div class="mst-print-foot">Sargam 2.0 · COE · Lal Bahadur Shastri National Academy of Administration</div>
</template>
