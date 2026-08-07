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

    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">{{ $header[0] }}</th>
                <th class="col-category">{{ $header[1] }}</th>
                <th class="col-sub">{{ $header[2] }}</th>
                <th class="col-status">{{ $header[3] }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-category">{{ $row->category_name ?: '-' }}</td>
                    <td class="col-sub">{{ $row->issue_sub_category }}</td>
                    <td class="col-status">{{ (int) $row->status === 1 ? 'Active' : 'Inactive' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="ic-print-empty">No sub-categories to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="ic-print-foot">Sargam 2.0 · Centcom · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
