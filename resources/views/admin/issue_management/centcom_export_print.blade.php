<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Centcom Assign' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 14px; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #004384; padding-bottom: 8px; }
        .title { color: #004384; font-size: 17px; font-weight: bold; }
        .subtitle { font-size: 10px; color: #666; margin-top: 4px; }
        .timestamp { font-size: 8px; color: #888; font-style: italic; margin-top: 2px; }
        .filters { background: #f8f9fa; border: 1px solid #dee2e6; padding: 6px 10px; margin-bottom: 10px; font-size: 9px; border-radius: 4px; }
        .main-table { width: 100%; border-collapse: collapse; font-size: 8px; }
        .main-table th { background: #004384; color: #fff; padding: 5px 4px; text-align: left; border: 1px solid #003a73; }
        .main-table td { padding: 4px; border: 1px solid #dee2e6; vertical-align: top; word-wrap: break-word; }
        .main-table tr:nth-child(even) td { background: #f8f9fa; }
        .col-id { width: 5%; }
        .col-date { width: 11%; }
        .col-category { width: 13%; }
        .col-desc { width: 24%; }
        .col-complainant { width: 13%; }
        .col-nodal { width: 13%; }
        .col-priority { width: 8%; }
        .col-status { width: 10%; }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print();">
    <div class="header">
        <div class="title">{{ $title ?? 'Centcom Assign' }}</div>
        <div class="subtitle">Sargam | Lal Bahadur Shastri National Academy of Administration (LBSNAA), Mussoorie</div>
        <div class="timestamp">Generated: {{ $exportDate }}</div>
    </div>

    @if (filled($search))
        <div class="filters"><strong>Search:</strong> {{ $search }}</div>
    @endif

    <table class="main-table">
        <thead>
            <tr>
                <th class="col-id">{{ $header[0] }}</th>
                <th class="col-date">{{ $header[1] }}</th>
                <th class="col-category">{{ $header[2] }}</th>
                <th class="col-desc">{{ $header[3] }}</th>
                <th class="col-complainant">{{ $header[4] }}</th>
                <th class="col-nodal">{{ $header[5] }}</th>
                <th class="col-priority">{{ $header[6] }}</th>
                <th class="col-status">{{ $header[7] }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="col-id">{{ $row[0] }}</td>
                    <td class="col-date">{{ $row[1] ?: '-' }}</td>
                    <td class="col-category">{{ $row[2] ?: '-' }}</td>
                    <td class="col-desc">{{ $row[3] ?: '-' }}</td>
                    <td class="col-complainant">{{ $row[4] ?: '-' }}</td>
                    <td class="col-nodal">{{ $row[5] ?: '-' }}</td>
                    <td class="col-priority">{{ $row[6] ?: '-' }}</td>
                    <td class="col-status">{{ $row[7] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">No issues to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
