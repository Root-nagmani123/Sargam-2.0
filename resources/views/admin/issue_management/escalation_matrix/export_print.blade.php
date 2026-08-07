<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Escalation Matrix — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-sno { width: 7%; }
        .col-category { width: 21%; }
        .col-level { width: 24%; }
    </style>
</head>
<body onload="window.print();">
    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Escalation Matrix',
        'exportDate' => $exportDate,
        'filterLine' => filled($search) ? '<strong>Search:</strong> ' . e($search) : null,
        'total'      => count($rows),
    ])

    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">{{ $header[0] }}</th>
                <th class="col-category">{{ $header[1] }}</th>
                <th class="col-level">{{ $header[2] }}</th>
                <th class="col-level">{{ $header[3] }}</th>
                <th class="col-level">{{ $header[4] }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="col-sno">{{ $row[0] }}</td>
                    <td class="col-category">{{ $row[1] ?: '-' }}</td>
                    <td class="col-level">{{ $row[2] }}</td>
                    <td class="col-level">{{ $row[3] }}</td>
                    <td class="col-level">{{ $row[4] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="ic-print-empty">No mappings to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="ic-print-foot">Sargam 2.0 · Centcom · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
