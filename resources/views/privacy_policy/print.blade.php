{{-- "Print": a server-rendered branded page that auto-prints, with the same header and chrome as
     every other module's printout (admin.master.partials.export_print_*). The PDF twin is pdf.blade.php. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Privacy Policy – SARGAM 2.0 — LBSNAA</title>
    @include('admin.master.partials.export_print_styles')
    @include('privacy_policy._export_styles')
</head>
<body onload="window.print();">

    @include('admin.master.partials.export_print_header', [
        'title' => 'Privacy Policy – SARGAM 2.0',
        'exportDate' => $exportDate,
        'filterLine' => 'Last Updated: '.$lastUpdated,
    ])

    @include('privacy_policy._content')

    <div class="mst-print-foot">Sargam 2.0 · Privacy Policy · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
