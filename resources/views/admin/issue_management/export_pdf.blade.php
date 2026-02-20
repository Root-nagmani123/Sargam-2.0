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
        .main-table th, .main-table td { font-size: 7px; }
        .main-table .col-sno { width: 3%; }
        .main-table .col-section { width: 8%; }
        .main-table .col-callid { width: 5%; }
        .main-table .col-name { width: 12%; }
        .main-table .col-desc { width: 18%; }
        .main-table .col-attended { width: 10%; }
        .main-table .col-date { width: 6%; }
        .main-table .col-time { width: 5%; }
        .main-table .col-cleared-date { width: 6%; }
        .main-table .col-cleared-time { width: 5%; }
        .main-table .col-taken { width: 6%; }
        .main-table .col-location { width: 6%; }
        .main-table .col-status { width: 6%; }
        .main-table .col-remarks { width: 10%; }
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

    @if(!empty($truncated))
    <div class="filters" style="background: #fff3cd; border-color: #ffc107;">
        <strong>Note:</strong> Showing first {{ $limit ?? 5000 }} of {{ $total_count ?? 0 }} records. Use <strong>Excel export</strong> for the full dataset.
    </div>
    @endif

    <table class="main-table">
        <thead>
            <tr>
                @if(isset($header) && is_array($header))
                    <th class="col-sno">{{ $header[0] ?? 'S.No.' }}</th>
                    <th class="col-section">{{ $header[1] ?? 'Section' }}</th>
                    <th class="col-callid">{{ $header[2] ?? 'Call ID' }}</th>
                    <th class="col-name">{{ $header[3] ?? 'Name' }}</th>
                    <th class="col-desc">{{ $header[4] ?? 'Description' }}</th>
                    <th class="col-attended">{{ $header[5] ?? 'Attended By' }}</th>
                    <th class="col-date">{{ $header[6] ?? 'Call Date' }}</th>
                    <th class="col-time">{{ $header[7] ?? 'Call Time' }}</th>
                    <th class="col-cleared-date">{{ $header[8] ?? 'Cleared Date' }}</th>
                    <th class="col-cleared-time">{{ $header[9] ?? 'Cleared Time' }}</th>
                    <th class="col-taken">{{ $header[10] ?? 'Time Taken In Hours' }}</th>
                    <th class="col-location">{{ $header[11] ?? 'location' }}</th>
                    <th class="col-status">{{ $header[12] ?? 'Status' }}</th>
                    <th class="col-remarks">{{ $header[13] ?? 'Remarks' }}</th>
                @else
                    <th class="col-sno">S.No.</th>
                    <th class="col-section">Section</th>
                    <th class="col-callid">Call ID</th>
                    <th class="col-name">Name</th>
                    <th class="col-desc">Description</th>
                    <th class="col-attended">Attended By</th>
                    <th class="col-date">Call Date</th>
                    <th class="col-time">Call Time</th>
                    <th class="col-cleared-date">Cleared Date</th>
                    <th class="col-cleared-time">Cleared Time</th>
                    <th class="col-taken">Time Taken In Hours</th>
                    <th class="col-location">location</th>
                    <th class="col-status">Status</th>
                    <th class="col-remarks">Remarks</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td class="col-sno">{{ $row[0] ?? '-' }}</td>
                <td class="col-section">{{ $row[1] ?? '-' }}</td>
                <td class="col-callid">{{ $row[2] ?? '-' }}</td>
                <td class="col-name">{{ $row[3] ?? '-' }}</td>
                <td class="col-desc">{{ $row[4] ?? '-' }}</td>
                <td class="col-attended">{{ $row[5] ?? '-' }}</td>
                <td class="col-date">{{ $row[6] ?? '-' }}</td>
                <td class="col-time">{{ $row[7] ?? '-' }}</td>
                <td class="col-cleared-date">{{ $row[8] ?? '-' }}</td>
                <td class="col-cleared-time">{{ $row[9] ?? '-' }}</td>
                <td class="col-taken">{{ $row[10] ?? '-' }}</td>
                <td class="col-location">{{ $row[11] ?? '-' }}</td>
                <td class="col-status">{{ $row[12] ?? '-' }}</td>
                <td class="col-remarks">{{ $row[13] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="14" style="text-align: center; padding: 20px;">No issues to export</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
