<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Issue Management Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; margin: 0; padding: 10px; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #004a93; padding-bottom: 8px; }
        .title { color: #004a93; font-size: 16px; font-weight: bold; }
        .subtitle { font-size: 10px; color: #666; margin-top: 4px; }
        .timestamp { font-size: 8px; color: #888; font-style: italic; }
        .filters { background: #f8f9fa; border: 1px solid #dee2e6; padding: 6px 10px; margin-bottom: 10px; font-size: 8px; border-radius: 4px; }
        .filters span { margin-right: 12px; }
        .main-table { width: 100%; border-collapse: collapse; font-size: 8px; }
        .main-table th { background: #004a93; color: white; padding: 5px 4px; text-align: left; border: 1px solid #003a73; }
        .main-table td { padding: 4px; border: 1px solid #dee2e6; vertical-align: top; word-wrap: break-word; }
        .main-table tr:nth-child(even) { background: #f8f9fa; }
        .main-table .col-id { width: 4%; }
        .main-table .col-date { width: 8%; }
        .main-table .col-category { width: 10%; }
        .main-table .col-subcat { width: 10%; }
        .main-table .col-desc { width: 26%; }
        .main-table .col-priority { width: 6%; }
        .main-table .col-status { width: 10%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Issue Management - Export Report</div>
        <div class="subtitle">Sargam | Lal Bahadur Shastri National Academy of Administration (LBSNAA), Mussoorie</div>
        <div class="timestamp">Generated: {{ $export_date }}</div>
    </div>

    @if(count($filters) > 0)
    <div class="filters">
        <strong>Filters applied:</strong>
        @foreach($filters as $key => $value)
            <span>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</span>
        @endforeach
    </div>
    @endif

    <table class="main-table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-date">Date</th>
                <th class="col-category">Category</th>
                <th class="col-subcat">Sub-Category</th>
                <th class="col-desc">Description</th>
                <th class="col-priority">Priority</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($issues as $row)
            <tr>
                <td class="col-id">{{ $row[0] ?? '-' }}</td>
                <td class="col-date">{{ $row[1] ?? '-' }}</td>
                <td class="col-category">{{ $row[2] ?? '-' }}</td>
                <td class="col-subcat">{{ $row[3] ?? '-' }}</td>
                <td class="col-desc">{{ $row[4] ?? '-' }}</td>
                <td class="col-priority">{{ $row[5] ?? '-' }}</td>
                <td class="col-status">{{ $row[6] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">No issues to export</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
