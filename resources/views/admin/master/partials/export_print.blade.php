{{-- Shared "Print" view for every Master-module listing.

     Served by ExportsMasterGrid::renderMasterExport(). Print is a server-rendered
     branded page, NOT window.print() on the grid — see docs/new-design-index-page.md §1.

     Props: $reportTitle, $columns (resolved), $rows, $filterLine, $exportDate, $emptyText.

     Column widths / alignment ride on each column definition rather than a
     per-report <style> block, so a new listing costs no new blade. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    @include('admin.master.partials.export_print_styles')
</head>
<body onload="window.print();">

    @include('admin.master.partials.export_print_header', [
        'title'      => $reportTitle,
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total'      => $rows->count(),
    ])

    {{-- Columns are already filtered to whatever is still ticked in the grid's
         Columns modal, so the printout matches the screen and can't drift from
         the CSV, the .xlsx or the PDF. Cells are keyed by column — never by
         position. --}}
    <table class="mst-print-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th style="width: {{ $col['width'] }}; text-align: {{ $col['align'] ?? 'left' }};">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    @foreach ($columns as $col)
                        <td style="text-align: {{ $col['align'] ?? 'left' }};">{{ $col['value']($row, $index) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="mst-print-empty">{{ $emptyText }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mst-print-foot">Sargam 2.0 · Master · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
