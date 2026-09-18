<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-id { width: 5%; }
        .col-date { width: 11%; }
        .col-category { width: 12%; }
        .col-desc { width: 26%; }
        .col-complainant { width: 14%; }
        .col-nodal { width: 14%; }
        .col-priority { width: 8%; }
        .col-status { width: 10%; }
    </style>
</head>
<body onload="window.print();">
    @include('admin.issue_management.partials.export_print_header', [
        'title'      => $title,
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total'      => $total,
    ])

    @if ($truncated)
        <div class="ic-print-filters">
            <strong>Note:</strong> only the first {{ number_format($limit) }} of
            {{ number_format($total) }} matching rows are printed. Narrow the filters for the rest.
        </div>
    @endif

    {{-- Columns come from IssueManagementController::exportColumnDefs(), already
         filtered to whatever is ticked in the grid's Columns modal. Rows are
         keyed by column, never by position. --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                @foreach ($columns as $key => $col)
                    <th class="{{ $col['class'] }}">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $key => $col)
                        <td class="{{ $col['class'] }}">{{ $row[$key] !== '' ? $row[$key] : '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="ic-print-empty">No rows to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="ic-print-foot">Sargam 2.0 · Centcom · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
