{{-- Print export for Sidebar Menus.

     A server-rendered page, not window.print() — the same controller action
     serves this and the CSV off one query and one column list, so the printout
     and the download can't drift apart (docs/new-design-index-page.md §1).

     Chrome comes from the module partials, shared with the Categories and Menu
     Groups exports. Landscape here: 12 columns will not fit A4 portrait. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sidebar Menus — LBSNAA</title>
    @include('SidebarMenu.partials.export_print_styles')
    <style>
        /* 12 columns need the long edge of the page. */
        @page { size: A4 landscape; margin: 10mm 8mm; }

        table.sbm-print-table { font-size: 8.5px; }
        table.sbm-print-table thead th { padding: 5px 4px; }
        table.sbm-print-table td { padding: 4px; }

        /* Column widths are the only per-report part of the table. */
        .sbm-print-sno        { width: 4%;  text-align: center; }
        .sbm-print-category   { width: 9%; }
        .sbm-print-group      { width: 11%; }
        .sbm-print-parent     { width: 11%; }
        .sbm-print-name       { width: 12%; }
        .sbm-print-route      { width: 12%; }
        .sbm-print-attachment { width: 10%; }
        .sbm-print-permission { width: 11%; }
        .sbm-print-icon       { width: 8%; }
        .sbm-print-order      { width: 4%;  text-align: center; }
        .sbm-print-target     { width: 5%;  text-align: center; }
        .sbm-print-created    { width: 7%;  text-align: center; }
        .sbm-print-status     { width: 5%;  text-align: center; }
    </style>
</head>
<body onload="window.print();">

    @include('SidebarMenu.partials.export_print_header', [
        'title' => 'Sidebar Menus',
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total' => $rows->count(),
    ])

    {{-- Columns come from MenuService::exportColumnDefs(), already filtered to
         whatever is still ticked in the grid's Columns modal, so the printout
         matches the screen. Cells are keyed by column, never by position. --}}
    <table class="sbm-print-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th class="{{ $col['class'] }}">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    @foreach ($columns as $col)
                        <td class="{{ $col['class'] }}">{{ $col['value']($row, $index) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($columns), 1) }}" class="sbm-print-empty">No menus to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sbm-print-foot">Sargam 2.0 · Sidebar Menu Builder · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
