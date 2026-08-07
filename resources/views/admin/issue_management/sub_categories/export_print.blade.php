<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Sub-Categories — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-sno { width: 8%; }
        .col-category { width: 30%; }
        .col-sub { width: 46%; }
        .col-status { width: 16%; }
    </style>
</head>
<body onload="window.print();">
    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Manage Sub-Categories',
        'exportDate' => $exportDate,
        'filterLine' => filled($search) ? '<strong>Search:</strong> ' . e($search) : null,
        'total'      => count($rows),
    ])

    {{-- Columns come from IssueSubCategoryController::exportColumnDefs(), already
         filtered to whatever is ticked in the grid's Columns modal, so the
         printout matches the screen and the CSV/Excel/PDF. Keyed by column, never
         by position. --}}
    <table class="ic-print-table">
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
                    <td colspan="{{ count($columns) }}" class="ic-print-empty">No sub-categories to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="ic-print-foot">Sargam 2.0 · Centcom · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
