<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Priorities — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-sno { width: 8%; }
        .col-priority { width: 22%; }
        .col-desc { width: 54%; }
        .col-status { width: 16%; }
    </style>
</head>
<body onload="window.print();">
    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Manage Priorities',
        'exportDate' => $exportDate,
        'filterLine' => filled($search) ? '<strong>Search:</strong> ' . e($search) : null,
        'total'      => count($rows),
    ])

    {{-- Columns come from IssuePriorityController::exportColumnDefs(), already
         filtered to whatever is ticked in the grid's Columns modal. Keyed by
         column, never by position. --}}
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
                    <td colspan="{{ count($columns) }}" class="ic-print-empty">No priorities to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="ic-print-foot">Sargam 2.0 · Centcom · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
