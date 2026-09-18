{{-- Print export for Sidebar Categories.

     A server-rendered page, not window.print() — the same controller action
     serves this and the CSV off one query and one column list, so the printout
     and the download can't drift apart (docs/new-design-index-page.md §1).

     Chrome comes from the module partials, shared with the Menu Groups export. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sidebar Categories — LBSNAA</title>
    @include('SidebarMenu.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .sbm-print-sno     { width: 7%;  text-align: center; }
        .sbm-print-name    { width: 24%; }
        .sbm-print-slug    { width: 22%; }
        .sbm-print-icon    { width: 17%; }
        .sbm-print-order   { width: 9%;  text-align: center; }
        .sbm-print-created { width: 12%; text-align: center; }
        .sbm-print-status  { width: 9%;  text-align: center; }
    </style>
</head>
<body onload="window.print();">

    @include('SidebarMenu.partials.export_print_header', [
        'title' => 'Sidebar Categories',
        'exportDate' => $exportDate,
        'filterLine' => filled($search) ? '<strong>Search:</strong> ' . e($search) : null,
        'total' => $rows->count(),
    ])

    {{-- Columns come from SidebarCategoryService::exportColumnDefs(), already
         filtered to whatever is still ticked in the grid's Columns modal, so the
         printout matches the screen. Cells are keyed by column, never by
         position. --}}
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
                    <td colspan="{{ max(count($columns), 1) }}" class="sbm-print-empty">No categories to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sbm-print-foot">Sargam 2.0 · Sidebar Menu Builder · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
