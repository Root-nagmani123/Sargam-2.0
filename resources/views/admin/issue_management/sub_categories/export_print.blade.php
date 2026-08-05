<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Sub-Categories</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 16px; }
        .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #004384; padding-bottom: 8px; }
        .title { color: #004384; font-size: 18px; font-weight: bold; }
        .subtitle { font-size: 11px; color: #666; margin-top: 4px; }
        .timestamp { font-size: 9px; color: #888; font-style: italic; margin-top: 2px; }
        .filters { background: #f8f9fa; border: 1px solid #dee2e6; padding: 6px 10px; margin-bottom: 10px; font-size: 10px; border-radius: 4px; }
        .main-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .main-table th { background: #004384; color: #fff; padding: 6px 5px; text-align: left; border: 1px solid #003a73; }
        .main-table td { padding: 5px; border: 1px solid #dee2e6; vertical-align: top; word-wrap: break-word; }
        .main-table tr:nth-child(even) td { background: #f8f9fa; }
        .col-sno { width: 8%; }
        .col-category { width: 30%; }
        .col-sub { width: 46%; }
        .col-status { width: 16%; }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print();">
    <div class="header">
        <div class="title">Manage Sub-Categories</div>
        <div class="subtitle">Sargam | Lal Bahadur Shastri National Academy of Administration (LBSNAA), Mussoorie</div>
        <div class="timestamp">Generated: {{ $exportDate }}</div>
    </div>

    @if (filled($search))
        <div class="filters"><strong>Search:</strong> {{ $search }}</div>
    @endif

    <table class="main-table">
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
                    <td colspan="4" style="text-align: center; padding: 20px;">No sub-categories to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
